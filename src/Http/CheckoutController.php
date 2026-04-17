<?php

namespace Init\Commerce\Order\Http;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Init\Commerce\Order\Actions\PlaceOrderFromCart;
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
            'data' => OrderPayload::make($order->load('items')),
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

}
