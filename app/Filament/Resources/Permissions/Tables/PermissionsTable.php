<?php

namespace App\Filament\Resources\Permissions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PermissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('table.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('guard_name')
                    ->label(__('table.guard_name'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('roles_count')
                    ->counts('roles')
                    ->label(__('permission.roles'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('table.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
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
