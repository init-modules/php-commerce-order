<?php

namespace Init\Commerce\Order\Filament\Resources\Orders\Pages;

use Filament\Resources\Pages\ListRecords;
use Init\Commerce\Order\Filament\Resources\Orders\OrderResource;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
}
