<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
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

                TextColumn::make('price')
                    ->label(__('table.price'))
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),

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
                        'in_stock' => 'success',
                        'out_of_stock' => 'danger',
                        'pre_order' => 'info',
                        default => 'gray',
                    }),

                IconColumn::make('is_active')
                    ->label(__('table.status'))
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([])
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
                ]),
            ]);
    }
}
