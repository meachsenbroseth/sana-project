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
                    ->label(__('table.product'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label(__('table.customer'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rating')
                    ->label(__('table.rating'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('title')
                    ->label(__('table.title'))
                    ->limit(80)
                    ->searchable(),
                TextColumn::make('comment')
                    ->label(__('table.comment'))
                    ->limit(50)
                    ->searchable(),
                IconColumn::make('is_verified_purchase')
                    ->label(__('review.verified_purchase'))
                    ->boolean(),
                IconColumn::make('is_approved')
                    ->label(__('review.approved'))
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
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_approved')
                    ->label(__('review.approval_status'))
                    ->boolean()
                    ->trueLabel(__('review.approved_only'))
                    ->falseLabel(__('review.pending_only'))
                    ->native(false),

                TernaryFilter::make('is_verified_purchase')
                    ->label(__('review.verified_purchase'))
                    ->boolean()
                    ->trueLabel(__('review.verified_only'))
                    ->falseLabel(__('review.unverified_only'))
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->label(__('review.approve'))
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_approved' => true])),
                    BulkAction::make('reject')
                        ->label(__('review.reject'))
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->delete()),
                ]),
            ]);
    }
}
