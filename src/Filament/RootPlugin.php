<?php

namespace Init\Commerce\Order\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class RootPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'init-commerce-order';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(
            in: __DIR__ . '/Resources',
            for: 'Init\\Commerce\\Order\\Filament\\Resources',
        );
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
