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
        $this->call([
            //
        ]);
    }
}
