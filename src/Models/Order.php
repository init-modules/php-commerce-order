<?php

namespace Init\Commerce\Order\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Init\Commerce\Order\Enums\OrderStatus;

class Order extends Model
{
    use HasUuids;

    protected $table = 'commerce_orders';

    protected $fillable = [
        'number',
        'cart_id',
        'actor_type',
        'actor_id',
        'actor_authenticated',
        'status',
        'currency',
        'item_count',
        'items_quantity',
        'subtotal_amount',
        'total_amount',
        'customer_snapshot',
        'meta',
        'placed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'actor_authenticated' => 'boolean',
        'status' => OrderStatus::class,
        'item_count' => 'integer',
        'items_quantity' => 'integer',
        'subtotal_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'customer_snapshot' => 'array',
        'meta' => 'array',
        'placed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Order $order): void {
            if ($order->status === OrderStatus::PLACED && blank($order->placed_at)) {
                $order->placed_at = now();
            }
        });
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(\Init\Commerce\Cart\Models\Cart::class, 'cart_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            config('commerce_order.models.order_item', OrderItem::class),
            'order_id',
        );
    }
}
