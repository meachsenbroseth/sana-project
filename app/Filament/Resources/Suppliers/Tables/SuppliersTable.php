<?php

namespace App\Filament\Resources\Suppliers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('table.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('contact_name')
                    ->label(__('supplier.contact_name'))
                    ->searchable(),
                TextColumn::make('phone')
                    ->label(__('supplier.phone')),
                TextColumn::make('email')
                    ->label(__('supplier.email'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('products_count')
                    ->label(__('supplier.products_count'))
                    ->counts('products')
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('table.status'))
                    ->boolean(),
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
                //
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
                ]),
            ]);
    }
}
