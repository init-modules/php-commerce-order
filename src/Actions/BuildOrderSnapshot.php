<?php

namespace Init\Commerce\Order\Actions;

use Init\Commerce\Cart\Models\Cart;
use Init\Commerce\Cart\Models\CartItem;

class BuildOrderSnapshot
{
    public function execute(Cart $cart): array
    {
        $cart->loadMissing('items');

        $items = $cart->items
            ->map(fn (CartItem $item): array => [
                'cart_item_id' => $item->getKey(),
                'catalog_item_type' => $item->catalog_item_type,
                'catalog_item_id' => $item->catalog_item_id,
                'item_name' => $item->item_name,
                'item_sku' => $item->item_sku,
                'item_type' => $item->item_type,
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
            ->all();

        return [
            'currency' => $cart->currency,
            'item_count' => count($items),
            'items_quantity' => (int) $cart->items->sum('quantity'),
            'subtotal_amount' => number_format(
                $cart->items->sum(fn (CartItem $item): float => (float) $item->line_base_total),
                2,
                '.',
                '',
            ),
            'total_amount' => number_format(
                $cart->items->sum(fn (CartItem $item): float => (float) $item->line_total),
                2,
                '.',
                '',
            ),
            'items' => $items,
        ];
    }
}
