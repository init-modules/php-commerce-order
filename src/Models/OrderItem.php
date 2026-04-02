<?php

namespace Init\Commerce\Order\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasUuids;

    protected $table = 'commerce_order_items';

    protected $fillable = [
        'order_id',
        'cart_item_id',
        'catalog_item_type',
        'catalog_item_id',
        'item_name',
        'item_sku',
        'item_type',
        'quantity',
        'base_price',
        'unit_price',
        'line_base_total',
        'line_total',
        'pricing_snapshot',
        'catalog_snapshot',
        'meta',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'base_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_base_total' => 'decimal:2',
        'line_total' => 'decimal:2',
        'pricing_snapshot' => 'array',
        'catalog_snapshot' => 'array',
        'meta' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(
            config('commerce_order.models.order', Order::class),
            'order_id',
        );
    }
}
