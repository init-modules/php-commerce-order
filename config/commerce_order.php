<?php

return [
    'models' => [
        'order' => Init\Commerce\Order\Models\Order::class,
        'order_item' => Init\Commerce\Order\Models\OrderItem::class,
    ],

    'api' => [
        'enabled' => true,
        'prefix' => 'commerce/order/v1',
        'name_prefix' => 'commerce.order.api.',
        'middleware' => ['api'],
    ],

    'filament' => [
        'enabled' => true,
        'panel' => 'admin',
    ],

    'order_numbers' => [
        'prefix' => 'ORD',
        'date_format' => 'Ymd',
        'random_digits' => 6,
    ],

    'stock' => [
        'allocate_on_place' => true,
    ],
];
