<?php

namespace Init\Commerce\Order\Filament\Resources\Orders\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Init\Commerce\Order\Enums\OrderStatus;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('placed_at', 'desc')
            ->columns([
                TextColumn::make('number')
                    ->label('Номер')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('actor_type')
                    ->label('Тип актёра')
                    ->toggleable(),

                TextColumn::make('actor_id')
                    ->label('Actor ID')
                    ->copyable()
                    ->toggleable(),

                IconColumn::make('actor_authenticated')
                    ->label('Auth')
                    ->boolean(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge(),

                TextColumn::make('items_quantity')
                    ->label('Qty')
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->sortable(),

                TextColumn::make('placed_at')
                    ->label('Размещён')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(OrderStatus::options()),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
