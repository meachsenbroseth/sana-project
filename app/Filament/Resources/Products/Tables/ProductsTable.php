<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
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
                    ->searchable()
                    ->weight('bold')
                    ->limit(30)
                    ->tooltip(fn ($state) => strlen($state) > 30 ? $state : null),

                TextColumn::make('sku')
                    ->label(__('table.sku'))
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('gray')
                    ->size('sm'),
                
                TextColumn::make('supplier.name')
                    ->label(__('product.supplier'))
                    ->sortable()
                    ->searchable()
                    ->size('sm')
                    ->default('—'),
                
                TextColumn::make('brand.name')
                    ->label(__('table.brand'))
                    ->sortable()
                    ->searchable(),

                // TextColumn::make('category.name')
                //     ->label(__('table.category'))
                //     ->sortable()
                //     ->searchable()
                //     ->badge()
                //     ->size('sm')
                //     ->color('info'),

                TextColumn::make('price')
                    ->label(__('table.price'))
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),

                // TextColumn::make('stock_quantity')
                //     ->label(__('table.stock_quantity'))
                //     ->numeric()
                //     ->sortable()
                //     ->badge()
                //     ->color(fn (int $state): string => match (true) {
                //         $state <= 0  => 'danger',
                //         $state <= 10 => 'warning',
                //         default      => 'success',
                //     })
                //     ->icon(fn (int $state): string => match (true) {
                //         $state <= 0  => 'heroicon-m-x-circle',
                //         $state <= 10 => 'heroicon-m-exclamation-triangle',
                //         default      => 'heroicon-m-check-circle',
                //     }),
                TextColumn::make('stock_quantity')
                    ->label(__('table.stock_quantity'))
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state, $record): string => match (true) {
                        $state <= 0 => 'danger',
                        $state <= ($record->low_stock_threshold ?? 10) => 'warning',
                        default => 'success',
                    })
                    ->icon(fn (int $state, $record): string => match (true) {
                        $state <= 0 => 'heroicon-m-x-circle',
                        $state <= ($record->low_stock_threshold ?? 10) => 'heroicon-m-exclamation-triangle',
                        default => 'heroicon-m-check-circle',
                    }),

                TextColumn::make('stock_status')
                    ->label(__('table.stock_status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in_stock'     => 'success',
                        'out_of_stock' => 'danger',
                        'pre_order'    => 'info',
                        default        => 'gray',
                    }),

                TextColumn::make('status')
                    ->label(__('table.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new'  => 'success',
                        'used' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('views_count')
                    ->label(__('table.views'))
                    ->numeric()
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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

                // TernaryFilter::make('low_stock')
                //     ->label(__('product.low_stock'))
                //     ->placeholder(__('product.all_products'))
                //     ->trueLabel(__('product.low_stock_only'))
                //     ->falseLabel(__('product.healthy_stock'))
                //     ->queries(
                //         true: fn (Builder $query) => $query->where('stock_quantity', '<=', 10)
                //             ->where('stock_quantity', '>', 0),
                //         false: fn (Builder $query) => $query->where('stock_quantity', '>', 10),
                //     ),
                TernaryFilter::make('low_stock')
                    ->label(__('product.low_stock'))
                    ->placeholder(__('product.all_products'))
                    ->trueLabel(__('product.low_stock_only'))
                    ->falseLabel(__('product.healthy_stock'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                            ->where('stock_quantity', '>', 0),
                        false: fn (Builder $query) => $query->whereColumn('stock_quantity', '>', 'low_stock_threshold'),
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-m-eye')
                        ->color('info'),
                    EditAction::make()
                        ->icon('heroicon-m-pencil-square')
                        ->color('warning'),
                    DeleteAction::make()
                        ->icon('heroicon-m-trash')
                        ->color('danger'),
                ]),
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