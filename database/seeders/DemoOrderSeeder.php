<?php

namespace Init\Commerce\Order\Database\Seeders;

use Illuminate\Database\Seeder;
use Init\Commerce\Catalog\Models\CatalogItem;
use Init\Commerce\Order\Enums\OrderStatus;
use Init\Commerce\Order\Models\Order;

class DemoOrderSeeder extends Seeder
{
    public function run(): void
    {
        $items = CatalogItem::query()
            ->whereIn('sku', ['PHONE-001', 'CONSULT-001'])
            ->orderBy('sku')
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        $lines = $items
            ->map(function (CatalogItem $item): array {
                $quantity = $item->sku === 'PHONE-001' ? 1 : 2;
                $unitPrice = $this->publicPriceAmount($item);
                $lineTotal = $unitPrice * $quantity;

                return [
                    'catalog_item_type' => $item::class,
                    'catalog_item_id' => (string) $item->getKey(),
                    'item_name' => $item->name,
                    'item_sku' => $item->sku,
                    'item_type' => $item->type?->value,
                    'quantity' => $quantity,
                    'base_price' => number_format((float) $item->base_price_amount, 2, '.', ''),
                    'unit_price' => number_format((float) $unitPrice, 2, '.', ''),
                    'line_base_total' => number_format((float) $item->base_price_amount * $quantity, 2, '.', ''),
                    'line_total' => number_format((float) $lineTotal, 2, '.', ''),
                    'pricing_snapshot' => [
                        'source' => $item->pricing_source?->value,
                        'manual_price_amount' => $item->manual_price_amount,
                        'effective_price_amount' => $item->effective_price_amount,
                    ],
                    'catalog_snapshot' => [
                        'name' => $item->name,
                        'sku' => $item->sku,
                        'slug' => $item->slug,
                        'type' => $item->type?->value,
                        'tracked' => $item->tracked,
                    ],
                    'meta' => ['seeded' => true],
                ];
            })
            ->values();

        $subtotalAmount = $lines->sum(fn (array $line): float => (float) $line['line_base_total']);
        $totalAmount = $lines->sum(fn (array $line): float => (float) $line['line_total']);

        $order = Order::query()->updateOrCreate(
            ['number' => 'ORD-DEMO-000001'],
            [
                'cart_id' => null,
                'actor_type' => 'demo',
                'actor_id' => 'commerce-demo',
                'actor_authenticated' => false,
                'status' => OrderStatus::PLACED,
                'currency' => 'KZT',
                'item_count' => $lines->count(),
                'items_quantity' => $lines->sum(fn (array $line): int => (int) $line['quantity']),
                'subtotal_amount' => number_format($subtotalAmount, 2, '.', ''),
                'total_amount' => number_format($totalAmount, 2, '.', ''),
                'customer_snapshot' => [
                    'name' => 'Demo Buyer',
                    'email' => 'demo@example.test',
                ],
                'meta' => ['seeded' => true],
                'placed_at' => now()->subDay(),
            ],
        );

        foreach ($lines as $line) {
            $order->items()->updateOrCreate(
                [
                    'catalog_item_type' => $line['catalog_item_type'],
                    'catalog_item_id' => $line['catalog_item_id'],
                ],
                $line,
            );
        }
    }

    private function publicPriceAmount(CatalogItem $item): int
    {
        return (int) ($item->effective_price_amount ?: $item->manual_price_amount ?: $item->base_price_amount);
    }
}
