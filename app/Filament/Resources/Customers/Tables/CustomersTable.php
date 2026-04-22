<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('table.name'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('customer.email'))
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->label(__('table.email_verified_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label(__('table.phone'))
                    ->searchable(),
                TextColumn::make('date_of_birth')
                    ->label(__('table.date_of_birth'))
                    ->date()
                    ->sortable(),
                TextColumn::make('gender')
                    ->label(__('table.gender'))
                    ->searchable(),
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
                TextColumn::make('deleted_at')
                    ->label(__('table.deleted_at'))
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
