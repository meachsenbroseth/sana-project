<?php

namespace App\Filament\Resources\Reviews\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rating')
                    ->badge()
                    ->sortable(),
                TextColumn::make('title')
                    ->limit(80)
                    ->searchable(),
                TextColumn::make('comment')
                    ->limit(50)
                    ->searchable(),
                IconColumn::make('is_verified_purchase')
                    ->boolean(),
                IconColumn::make('is_approved')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_approved')
                    ->label('Approval Status')
                    ->boolean()
                    ->trueLabel('Approved only')
                    ->falseLabel('Pending only')
                    ->native(false),

                TernaryFilter::make('is_verified_purchase')
                    ->label('Verified Purchase')
                    ->boolean()
                    ->trueLabel('Verified only')
                    ->falseLabel('Unverified only')
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->label('Approve')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_approved' => true])),
                    BulkAction::make('reject')
                        ->label('Reject')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->delete()),
                ]),
            ]);
    }
}
