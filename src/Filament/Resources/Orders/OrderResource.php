<?php

namespace Init\Commerce\Order\Filament\Resources\Orders;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Init\Commerce\Catalog\Filament\Cluster\CommerceCluster;
use Init\Commerce\Order\Filament\Resources\Orders\Pages\EditOrder;
use Init\Commerce\Order\Filament\Resources\Orders\Pages\ListOrders;
use Init\Commerce\Order\Filament\Resources\Orders\RelationManagers\OrderItemsRelationManager;
use Init\Commerce\Order\Filament\Resources\Orders\Schemas\OrderForm;
use Init\Commerce\Order\Filament\Resources\Orders\Tables\OrdersTable;
use Init\Commerce\Order\Models\Order;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?string $cluster = CommerceCluster::class;

    protected static ?string $slug = 'orders';

    protected static ?string $navigationLabel = 'Заказы';

    protected static ?string $modelLabel = 'Заказ';

    protected static ?string $pluralModelLabel = 'Заказы';

    protected static ?int $navigationSort = 41;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            OrderItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
