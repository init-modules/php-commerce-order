<?php

namespace Init\Commerce\Order\Http;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Init\Commerce\Order\Models\Order;
use Init\VisitorSession\Support\RequestActorResolver;
use Init\VisitorSession\Support\ResolvedActor;

class CurrentOrdersController
{
    public function __construct(
        private readonly RequestActorResolver $requestActorResolver,
    ) {}

    public function index(Request $request): array
    {
        $actor = $this->resolveActor($request);
        $limit = max(1, min((int) $request->integer('limit', 20), 50));

        $orders = Order::query()
            ->with('items')
            ->where('actor_type', $actor->type)
            ->where('actor_id', $actor->id)
            ->latest('placed_at')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (Order $order): array => OrderPayload::make($order))
            ->values()
            ->all();

        return [
            'data' => $orders,
        ];
    }

    private function resolveActor(Request $request): ResolvedActor
    {
        $actor = $this->requestActorResolver->resolve($request, allowVisitorSession: false);

        if ($actor instanceof ResolvedActor) {
            return $actor;
        }

        throw ValidationException::withMessages([
            'actor' => ['Authenticated user is required to view orders.'],
        ]);
    }
}
