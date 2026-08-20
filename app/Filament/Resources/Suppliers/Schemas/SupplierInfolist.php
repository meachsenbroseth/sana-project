<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('supplier.sections.information'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('table.name'))
                            ->weight('bold'),

                        TextEntry::make('contact_name')
                            ->label(__('supplier.contact_name'))
                            ->placeholder('—'),

                        TextEntry::make('email')
                            ->label(__('supplier.email'))
                            ->placeholder('—')
                            ->copyable(),

                        TextEntry::make('phone')
                            ->label(__('supplier.phone'))
                            ->placeholder('—'),

                        TextEntry::make('address')
                            ->label(__('supplier.address'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(__('supplier.sections.status'))
                    ->schema([
                        IconEntry::make('is_active')
                            ->label(__('table.status'))
                            ->boolean(),

                        TextEntry::make('products_count')
                            ->label(__('supplier.products_count'))
                            ->state(fn ($record) => $record->products()->count())
                            ->badge()
                            ->color('info'),
                    ])
                    ->columns(2),
            ]);
    }
}
