<?php

namespace Init\Commerce\Order\Database\Seeders;

use Init\Core\Database\PackageSeeder;

class RootSeeder extends PackageSeeder
{
    public static function dependencies(): array
    {
        return ['init/commerce-cart', 'init/commerce-catalog', 'init/commerce-stock'];
    }

    public function run(): void
    {
        if (app()->isProduction() || ! config('commerce_order.seed_demo_data', true)) {
            return;
        }

        if (class_exists(\Init\Commerce\Catalog\Database\Seeders\RootSeeder::class)) {
            $this->call(\Init\Commerce\Catalog\Database\Seeders\RootSeeder::class);
        }

        $this->call([
            DemoOrderSeeder::class,
        ]);
    }
}
