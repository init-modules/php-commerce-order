<?php

namespace Init\Commerce\Order\Http;

use Init\Commerce\Order\Models\Order;
use Init\Commerce\Order\Models\OrderItem;

class OrderPayload
{
    public static function make(Order $order): array
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
                    'meta' => $item->meta,
                ])
                ->values()
                ->all(),
        ];
    }
}
