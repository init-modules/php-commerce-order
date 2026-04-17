<?php

use Illuminate\Support\Str;
use Init\Commerce\Catalog\Enums\CatalogInventoryMode;
use Init\Commerce\Catalog\Enums\CatalogItemStatus;
use Init\Commerce\Catalog\Enums\CatalogItemType;
use Init\Commerce\Catalog\Enums\CatalogPriceMarkupType;
use Init\Commerce\Catalog\Enums\CatalogPricingSource;
use Init\Commerce\Catalog\Models\CatalogItem;
use Init\Commerce\Catalog\Models\CatalogPriceRule;
use Init\Commerce\Order\Models\Order;
use Init\Commerce\Stock\Actions\AdjustStock;
use Init\Commerce\Stock\Enums\StockMovementType;
use Init\Commerce\Stock\Enums\WarehouseStatus;
use Init\Commerce\Stock\Models\StockLevel;
use Init\Commerce\Stock\Models\StockMovement;
use Init\Commerce\Stock\Models\Warehouse;

function orderVisitorSessionHeaders(?string $sessionId = null): array
{
    return [
        'X-Visitor-Session' => $sessionId ?? (string) Str::uuid(),
    ];
}

function createOrderCatalogItem(array $attributes = []): CatalogItem
{
    return CatalogItem::query()->create([
        'type' => CatalogItemType::PRODUCT,
        'status' => CatalogItemStatus::ACTIVE,
        'sku' => 'ITEM-'.Str::upper(Str::random(8)),
        'name' => 'Commerce Item',
        'slug' => 'commerce-item-'.Str::lower(Str::random(8)),
        'base_price_amount' => 1000,
        'currency' => 'KZT',
        'inventory_mode' => CatalogInventoryMode::TRACKED,
        ...$attributes,
    ]);
}

function createOrderDefaultWarehouse(): Warehouse
{
    return Warehouse::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Warehouse',
        'status' => WarehouseStatus::ACTIVE,
        'is_default' => true,
    ]);
}

it('registers checkout route', function () {
    expect(route('commerce.order.api.checkout.store', [], false))->toBe('/api/commerce/order/v1/checkout');
});

it('lists orders for the current actor only', function () {
    $firstSessionHeaders = orderVisitorSessionHeaders();
    $secondSessionHeaders = orderVisitorSessionHeaders();

    $firstProduct = createOrderCatalogItem([
        'name' => 'First Buyer Item',
        'slug' => 'first-buyer-item',
        'inventory_mode' => CatalogInventoryMode::UNTRACKED,
    ]);
    $secondProduct = createOrderCatalogItem([
        'name' => 'Second Buyer Item',
        'slug' => 'second-buyer-item',
        'inventory_mode' => CatalogInventoryMode::UNTRACKED,
    ]);

    $this->withHeaders($firstSessionHeaders)
        ->postJson('/api/commerce/cart/v1/current/items', [
            'catalog_item_id' => (string) $firstProduct->getKey(),
            'quantity' => 1,
        ])
        ->assertSuccessful();

    $firstOrderNumber = $this->withHeaders($firstSessionHeaders)
        ->postJson('/api/commerce/order/v1/checkout', [])
        ->assertSuccessful()
        ->json('data.number');

    $this->withHeaders($secondSessionHeaders)
        ->postJson('/api/commerce/cart/v1/current/items', [
            'catalog_item_id' => (string) $secondProduct->getKey(),
            'quantity' => 1,
        ])
        ->assertSuccessful();

    $this->withHeaders($secondSessionHeaders)
        ->postJson('/api/commerce/order/v1/checkout', [])
        ->assertSuccessful();

    $this->withHeaders($firstSessionHeaders)
        ->getJson('/api/commerce/order/v1/orders')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.number', $firstOrderNumber)
        ->assertJsonPath('data.0.items.0.name', 'First Buyer Item');
});

it('builds cart and order snapshots from pricing rules and allocates stock', function () {
    CatalogPriceRule::query()->create([
        'name' => 'Products 0-5000',
        'is_active' => true,
        'priority' => 100,
        'item_type' => CatalogItemType::PRODUCT,
        'currency' => 'KZT',
        'min_base_price_amount' => 0,
        'max_base_price_amount' => 5000,
        'markup_type' => CatalogPriceMarkupType::PERCENTAGE,
        'markup_value' => 5000,
    ]);

    $product = createOrderCatalogItem([
        'sku' => 'PHONE-TEST-001',
        'name' => 'Phone Test',
        'slug' => 'phone-test',
        'base_price_amount' => 1000,
    ])->fresh();

    expect($product->pricing_source)->toBe(CatalogPricingSource::RULE)
        ->and($product->effective_price_amount)->toBe(1500);

    $warehouse = createOrderDefaultWarehouse();

    app(AdjustStock::class)->execute(
        catalogItem: $product,
        warehouse: $warehouse,
        quantityDelta: 10,
        note: 'Opening balance',
    );

    $headers = orderVisitorSessionHeaders();

    $this->withHeaders($headers)
        ->postJson('/api/commerce/cart/v1/current/items', [
            'catalog_item_id' => (string) $product->getKey(),
            'quantity' => 2,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.item_count', 1)
        ->assertJsonPath('data.items_quantity', 2)
        ->assertJsonPath('data.items.0.base_price', '1000.00')
        ->assertJsonPath('data.items.0.unit_price', '1500.00')
        ->assertJsonPath('data.items.0.catalog_snapshot.tracked', true);

    $checkoutResponse = $this->withHeaders($headers)
        ->postJson('/api/commerce/order/v1/checkout', []);

    $checkoutResponse
        ->assertSuccessful()
        ->assertJsonPath('data.item_count', 1)
        ->assertJsonPath('data.items_quantity', 2)
        ->assertJsonPath('data.items.0.base_price', '1000.00')
        ->assertJsonPath('data.items.0.unit_price', '1500.00')
        ->assertJsonPath('data.items.0.line_total', '3000.00');

    $order = Order::query()->with('items')->sole();
    $orderItem = $order->items->sole();
    $stockLevel = StockLevel::query()
        ->where('warehouse_id', $warehouse->getKey())
        ->where('catalog_item_id', $product->getKey())
        ->sole();

    expect($stockLevel->on_hand_quantity)->toBe(10)
        ->and($stockLevel->allocated_quantity)->toBe(2)
        ->and($stockLevel->available_quantity)->toBe(8)
        ->and(
            StockMovement::query()
                ->where('type', StockMovementType::ALLOCATION)
                ->count()
        )->toBe(1);

    $product->update([
        'manual_price_amount' => 2100,
    ]);

    expect($product->fresh()->pricing_source)->toBe(CatalogPricingSource::MANUAL)
        ->and($product->fresh()->effective_price_amount)->toBe(2100)
        ->and($orderItem->fresh()->unit_price)->toBe('1500.00')
        ->and($orderItem->fresh()->line_total)->toBe('3000.00');
});

it('supports service orders with manual public pricing and no stock allocation', function () {
    $service = createOrderCatalogItem([
        'type' => CatalogItemType::SERVICE,
        'sku' => 'CONSULT-TEST-001',
        'name' => 'Consultation Test',
        'slug' => 'consultation-test',
        'base_price_amount' => 15000,
        'manual_price_amount' => 19990,
        'inventory_mode' => CatalogInventoryMode::UNTRACKED,
        'service_duration_minutes' => 60,
    ])->fresh();

    expect($service->pricing_source)->toBe(CatalogPricingSource::MANUAL)
        ->and($service->effective_price_amount)->toBe(19990);

    $headers = orderVisitorSessionHeaders();

    $this->withHeaders($headers)
        ->postJson('/api/commerce/cart/v1/current/items', [
            'catalog_item_id' => (string) $service->getKey(),
            'quantity' => 1,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.items.0.type', CatalogItemType::SERVICE->value)
        ->assertJsonPath('data.items.0.unit_price', '19990.00')
        ->assertJsonPath('data.items.0.catalog_snapshot.tracked', false);

    $this->withHeaders($headers)
        ->postJson('/api/commerce/order/v1/checkout', [])
        ->assertSuccessful()
        ->assertJsonPath('data.items.0.type', CatalogItemType::SERVICE->value)
        ->assertJsonPath('data.items.0.unit_price', '19990.00')
        ->assertJsonPath('data.items.0.catalog_snapshot.tracked', false);

    expect(Order::query()->with('items')->sole()->items->sole()->unit_price)->toBe('19990.00')
        ->and(StockMovement::query()->count())->toBe(0);
});
