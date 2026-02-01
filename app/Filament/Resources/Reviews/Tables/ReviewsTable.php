<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

use function Livewire\str;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->url(fn($record) => ProductResource::getUrl('edit', [$record->product]))
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->url(fn($record) => CustomerResource::getUrl('edit', [$record->customer]))
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('rating')
                    ->formatStateUsing(fn($state) => str_repeat('⭐', $state))
                    ->color('warning')
                    ->sortable(),
                TextColumn::make('title')
                    ->limit(150)
                    ->wrap()
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
                Action::make('approve')
                ->icon(Heroicon::CheckCircle)
                ->color('success')
                ->action(fn($record) => $record->update(['is_approved'=> true]))
                ->visible(fn($record)=>!$record->is_approved)
                ->requiresConfirmation(),
                  Action::make('reject')
                ->icon(Heroicon::XCircle)
                ->color('denger')
                ->action(fn($record) => $record->update(['is_approved'=> false]))
                ->visible(fn($record)=>$record->is_approved)
                ->requiresConfirmation(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
