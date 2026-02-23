<?php

namespace App\Filament\Resources\ShippingMethods\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ShippingMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Shipping Method Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('cost')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('$'),
                        ToggleButtons::make('status')
                            ->required()
                            ->inline()
                            ->default('active')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ]),
                    ])
                    ->columns(2),
            ]);
    }
}
