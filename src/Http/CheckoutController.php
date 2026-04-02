<?php

namespace Init\Commerce\Order\Http;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Init\Commerce\Order\Actions\PlaceOrderFromCart;
use Init\Commerce\Order\Models\Order;
use Init\Commerce\Order\Models\OrderItem;
use Init\VisitorSession\Support\RequestActorResolver;
use Init\VisitorSession\Support\ResolvedActor;

class CheckoutController
{
    public function __construct(
        private readonly RequestActorResolver $requestActorResolver,
        private readonly PlaceOrderFromCart $placeOrderFromCart,
    ) {}

    public function store(Request $request): array
    {
        $actor = $this->resolveActor($request);
        $payload = $request->validate([
            'cart_id' => ['nullable', 'string'],
            'customer_snapshot' => ['nullable', 'array'],
        ]);

        $order = $this->placeOrderFromCart->execute(
            cart: $payload['cart_id'] ?? null,
            actor: $actor,
            customerSnapshot: $payload['customer_snapshot'] ?? $this->defaultCustomerSnapshot($request, $actor),
        );

        return [
            'data' => $this->transformOrder($order->load('items')),
        ];
    }

    private function resolveActor(Request $request): ResolvedActor
    {
        $actor = $this->requestActorResolver->resolve($request);

        if ($actor instanceof ResolvedActor) {
            return $actor;
        }

        throw ValidationException::withMessages([
            'actor' => ['Authenticated user or X-Visitor-Session header is required.'],
        ]);
    }

    private function defaultCustomerSnapshot(Request $request, ResolvedActor $actor): array
    {
        $user = $request->user();

        return array_filter([
            'actor_type' => $actor->type,
            'actor_id' => $actor->id,
            'authenticated' => $actor->authenticated,
            'name' => is_object($user) ? ($user->name ?? null) : null,
            'email' => is_object($user) ? ($user->email ?? null) : null,
        ], fn ($value): bool => $value !== null);
    }

    private function transformOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status?->value,
            'currency' => $order->currency,
            'item_count' => $order->item_count,
            'items_quantity' => $order->items_quantity,
            'subtotal_amount' => $order->subtotal_amount,
            'total_amount' => $order->total_amount,
            'customer_snapshot' => $order->customer_snapshot,
            'placed_at' => $order->placed_at?->toIso8601String(),
            'items' => $order->items
                ->map(fn (OrderItem $item): array => [
                    'id' => $item->id,
                    'catalog_item_type' => $item->catalog_item_type,
                    'catalog_item_id' => $item->catalog_item_id,
                    'name' => $item->item_name,
                    'sku' => $item->item_sku,
                    'type' => $item->item_type,
                    'quantity' => $item->quantity,
                    'base_price' => $item->base_price,
                    'unit_price' => $item->unit_price,
                    'line_base_total' => $item->line_base_total,
                    'line_total' => $item->line_total,
                    'pricing_snapshot' => $item->pricing_snapshot,
                    'catalog_snapshot' => $item->catalog_snapshot,
                ])
                ->values()
                ->all(),
        ];
    }
}
