<?php

use Illuminate\Support\Facades\Route;
use Init\Commerce\Order\Http\CheckoutController;

Route::prefix(config('commerce_order.api.prefix', 'commerce/order/v1'))->group(function (): void {
    Route::post('checkout', [CheckoutController::class, 'store'])
        ->name('checkout.store');
});
