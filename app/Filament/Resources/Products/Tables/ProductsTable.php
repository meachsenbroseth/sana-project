<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
// use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
// use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('table.name'))
                    ->searchable(),
                TextColumn::make('sku')
                    ->label(__('table.sku'))
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label(__('table.category'))
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->size('sm')
                    ->color('info'),
                TextColumn::make('brand.name')
                    ->label(__('table.brand'))
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->badge(),
                TextColumn::make('price')
                    ->label(__('table.price'))
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('stock_quantity')
                    ->label(__('table.stock_quantity'))
                    ->numeric()
                    ->badge()
                    ->sortable(),
                TextColumn::make('stock_status')
                    ->label(__('table.stock_status'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('table.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'success',
                        'used' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('views_count')
                    ->label(__('table.views'))
                    ->numeric()
                    ->badge()
                    ->sortable(),
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
                TextColumn::make('deleted_at')
                    ->label(__('table.deleted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('stock_status')
                    ->label(__('product.stock_availability'))
                    ->placeholder(__('product.all_products'))
                    ->trueLabel(__('product.stock_status.in_stock'))
                    ->falseLabel(__('product.stock_status.out_of_stock'))
                    ->queries(
                        true: fn (Builder $query) => $query->where('stock_quantity', '>', 0),
                        false: fn (Builder $query) => $query->where('stock_quantity', '<=', 0),
                    ),
            ])
            ->recordActions([
                ViewAction::make()
                    ->button()
                    ->color('info'),
                DeleteAction::make()
                    ->button()
                    ->color('danger'),
                EditAction::make()
                    ->button()
                    ->color('warning'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
