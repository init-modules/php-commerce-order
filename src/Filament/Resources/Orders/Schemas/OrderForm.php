<?php

namespace Init\Commerce\Order\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Init\Commerce\Order\Enums\OrderStatus;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)
                ->schema([
                    TextInput::make('number')
                        ->label('Номер')
                        ->disabled(),

                    Select::make('status')
                        ->label('Статус')
                        ->options(OrderStatus::options())
                        ->required()
                        ->native(false),

                    TextInput::make('actor_type')
                        ->label('Тип актёра')
                        ->disabled(),

                    TextInput::make('actor_id')
                        ->label('Actor ID')
                        ->disabled(),

                    TextInput::make('currency')
                        ->label('Валюта')
                        ->maxLength(3),

                    TextInput::make('items_quantity')
                        ->label('Qty')
                        ->disabled(),

                    TextInput::make('subtotal_amount')
                        ->label('Subtotal')
                        ->disabled(),

                    TextInput::make('total_amount')
                        ->label('Total')
                        ->disabled(),

                    DateTimePicker::make('placed_at')
                        ->label('Размещён')
                        ->seconds(false),

                    DateTimePicker::make('cancelled_at')
                        ->label('Отменён')
                        ->seconds(false),

                    KeyValue::make('customer_snapshot')
                        ->label('Customer snapshot')
                        ->columnSpanFull(),

                    KeyValue::make('meta')
                        ->label('Meta')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
