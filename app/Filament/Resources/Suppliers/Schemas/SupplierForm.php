<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('supplier.sections.information'))->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('contact_name')
                        ->label(__('supplier.contact_name'))
                        ->maxLength(255),
                    TextInput::make('email')
                        ->email()
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->tel()
                        ->maxLength(50),
                    TextInput::make('address')
                        ->maxLength(500)
                        ->columnSpanFull(),
                ])->columns(2),

                Section::make(__('supplier.sections.status'))->schema([
                    Toggle::make('is_active')
                        ->default(true)
                        ->required(),
                ])->columns(2),
            ]);
    }
}
