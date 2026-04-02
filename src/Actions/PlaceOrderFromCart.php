<?php

namespace Init\Commerce\Order\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Init\Commerce\Cart\Actions\ResolveActiveCart;
use Init\Commerce\Cart\Enums\CartStatus;
use Init\Commerce\Cart\Models\Cart;
use Init\Commerce\Order\Enums\OrderStatus;
use Init\Commerce\Order\Models\Order;
use Init\Commerce\Order\Support\OrderNumberGenerator;
use Init\VisitorSession\Support\ResolvedActor;

class PlaceOrderFromCart
{
    public function __construct(
        private readonly BuildOrderSnapshot $buildOrderSnapshot,
        private readonly ResolveActiveCart $resolveActiveCart,
        private readonly OrderNumberGenerator $orderNumberGenerator,
    ) {}

    public function execute(
        Cart|string|null $cart = null,
        ?ResolvedActor $actor = null,
        ?array $customerSnapshot = null,
    ): Order {
        $resolvedCart = $this->resolveCart($cart, $actor);

        /** @var class-string<Order> $orderModel */
        $orderModel = config('commerce_order.models.order', Order::class);

        /** @var Order $order */
        $order = DB::transaction(function () use ($resolvedCart, $customerSnapshot, $orderModel): Order {
            $lockedCart = Cart::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($resolvedCart->getKey());

            if ($lockedCart->status !== CartStatus::ACTIVE) {
                throw ValidationException::withMessages([
                    'cart' => ['Only active carts can be checked out.'],
                ]);
            }

            $snapshot = $this->buildOrderSnapshot->execute($lockedCart);

            if (($snapshot['item_count'] ?? 0) === 0) {
                throw ValidationException::withMessages([
                    'cart' => ['Cannot place an order from an empty cart.'],
                ]);
            }

            $order = $orderModel::query()->create([
                'number' => $this->orderNumberGenerator->generate(),
                'cart_id' => $lockedCart->getKey(),
                'actor_type' => $lockedCart->actor_type,
                'actor_id' => $lockedCart->actor_id,
                'actor_authenticated' => $lockedCart->actor_authenticated,
                'status' => OrderStatus::PLACED,
                'currency' => $snapshot['currency'],
                'item_count' => $snapshot['item_count'],
                'items_quantity' => $snapshot['items_quantity'],
                'subtotal_amount' => $snapshot['subtotal_amount'],
                'total_amount' => $snapshot['total_amount'],
                'customer_snapshot' => $customerSnapshot ?: $this->defaultCustomerSnapshot($lockedCart),
                'placed_at' => now(),
            ]);

            foreach ($snapshot['items'] as $item) {
                $order->items()->create($item);
            }

            $lockedCart->forceFill([
                'status' => CartStatus::CONVERTED,
                'converted_order_id' => $order->getKey(),
                'checked_out_at' => now(),
                'active_actor_key' => null,
            ])->save();

            $this->allocateStockIfNeeded($order->fresh('items'));

            return $order->fresh('items');
        });

        return $order;
    }

    private function resolveCart(Cart|string|null $cart, ?ResolvedActor $actor): Cart
    {
        if ($cart instanceof Cart) {
            if ($actor instanceof ResolvedActor && ! $cart->isOwnedBy($actor)) {
                throw ValidationException::withMessages([
                    'cart' => ['The selected cart does not belong to the current actor.'],
                ]);
            }

            return $cart->loadMissing('items');
        }

        if (is_string($cart) && $cart !== '') {
            $resolvedCart = Cart::query()->with('items')->findOrFail($cart);

            if ($actor instanceof ResolvedActor && ! $resolvedCart->isOwnedBy($actor)) {
                throw ValidationException::withMessages([
                    'cart' => ['The selected cart does not belong to the current actor.'],
                ]);
            }

            return $resolvedCart;
        }

        if ($actor instanceof ResolvedActor) {
            $activeCart = $this->resolveActiveCart->execute($actor, createIfMissing: false);

            if ($activeCart instanceof Cart) {
                return $activeCart->loadMissing('items');
            }
        }

        throw ValidationException::withMessages([
            'cart' => ['Active cart is required for checkout.'],
        ]);
    }

    private function defaultCustomerSnapshot(Cart $cart): array
    {
        return [
            'actor_type' => $cart->actor_type,
            'actor_id' => $cart->actor_id,
            'authenticated' => $cart->actor_authenticated,
        ];
    }

    private function allocateStockIfNeeded(Order $order): void
    {
        if (! config('commerce_order.stock.allocate_on_place', true)) {
            return;
        }

        if (! class_exists(\Init\Commerce\Stock\Actions\AllocateStockForOrder::class)) {
            return;
        }

        $hasTrackedItems = $order->items->contains(
            fn ($item): bool => (bool) data_get($item->catalog_snapshot, 'tracked', false)
        );

        if (! $hasTrackedItems) {
            return;
        }

        app(\Init\Commerce\Stock\Actions\AllocateStockForOrder::class)->execute($order);
    }
}
