<?php

namespace Init\Commerce\Order;

use Illuminate\Support\Facades\Route;
use Init\Commerce\Order\Database\Seeders\RootSeeder;
use Init\Commerce\Order\Filament\RootPlugin;
use Init\Core\Database\SeederRegistry;
use Init\Core\Filament\FilamentPluginRegistry;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class RootServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('commerce_order')
            ->hasConfigFile()
            ->hasMigrations([
                '0001_01_01_000000_create_commerce_order_orders_table',
                '0001_01_01_000001_create_commerce_order_order_items_table',
            ]);
    }

    public function packageRegistered(): void
    {
        if (! $this->app->environment('production')) {
            SeederRegistry::registerIfNotExists('init/commerce-order', [
                RootSeeder::class,
            ]);
        }

        if (config('commerce_order.filament.enabled', true)) {
            FilamentPluginRegistry::registerPlugin(
                RootPlugin::make(),
                config('commerce_order.filament.panel', 'admin'),
            );
        }

        if (class_exists(\Init\Documentation\Support\DocumentationRegistry::class)) {
            \Init\Documentation\Support\DocumentationRegistry::registerPath(
                package: 'init/commerce-order',
                slug: 'commerce/order',
                title: 'Commerce Order',
                path: dirname(__DIR__) . '/README.md',
                group: 'Commerce Foundation',
                sort: 40,
                summary: 'Checkout, immutable order snapshots and reserve-first ordering flow.',
            );
        }
    }

    public function packageBooted(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if (config('commerce_order.api.enabled', true)) {
            Route::prefix('api')
                ->middleware(config('commerce_order.api.middleware', ['api']))
                ->as(config('commerce_order.api.name_prefix', 'commerce.order.api.'))
                ->group(__DIR__ . '/../routes/api.php');
        }
    }
}
