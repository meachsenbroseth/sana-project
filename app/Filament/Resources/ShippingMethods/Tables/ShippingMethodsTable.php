<?php

namespace App\Filament\Resources\ShippingMethods\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ShippingMethodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('table.name'))
                    ->searchable(),

                TextColumn::make('provinces_count')
                    ->label(__('shipping_method.province_fees.covered_provinces'))
                    ->counts('provinces')
                    ->badge()
                    ->color('info')
                    ->suffix(' '.__('shipping_method.province_fees.provinces_suffix')),
                TextColumn::make('status')
                    ->badge()
                    ->label(__('table.status')),
                TextColumn::make('created_at')
                    ->label(__('table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('table.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => __('shipping_method.status.active'),
                        'inactive' => __('shipping_method.status.inactive'),
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->button()
                    ->color('warning'),
                DeleteAction::make()
                    ->button()
                    ->color('danger'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
