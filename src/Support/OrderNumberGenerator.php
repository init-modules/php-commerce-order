<?php

namespace Init\Commerce\Order\Support;

use Init\Commerce\Order\Models\Order;

class OrderNumberGenerator
{
    public function generate(): string
    {
        $prefix = (string) config('commerce_order.order_numbers.prefix', 'ORD');
        $date = now()->format((string) config('commerce_order.order_numbers.date_format', 'Ymd'));
        $digits = max(3, (int) config('commerce_order.order_numbers.random_digits', 6));
        $max = (10 ** $digits) - 1;

        do {
            $random = str_pad((string) random_int(0, $max), $digits, '0', STR_PAD_LEFT);
            $number = "{$prefix}-{$date}-{$random}";
        } while (Order::query()->where('number', $number)->exists());

        return $number;
    }
}
