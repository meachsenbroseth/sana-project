<?php

namespace App\Filament\Resources\ShippingMethods\Schemas;

use App\Models\Province;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
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
                Section::make(__('shipping_method.sections.information'))
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        ToggleButtons::make('status')
                            ->required()
                            ->inline()
                            ->default('active')
                            ->options([
                                'active' => __('shipping_method.status.active'),
                                'inactive' => __('shipping_method.status.inactive'),
                            ]),
                    ])
                    ->columns(2),

                Section::make(__('shipping_method.sections.province_fees'))
                    ->description(__('shipping_method.sections.province_fees_description'))
                    ->schema([
                        Repeater::make('province_fees')
                            ->label('')
                            ->schema([
                                Select::make('province_id')
                                    ->label(__('shipping_method.province_fees.province'))
                                    ->options(fn () => Province::query()->active()->orderBy('name_en')->pluck('name_en', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->native(false)
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->columnSpan(1),
                                TextInput::make('fee')
                                    ->label(__('shipping_method.province_fees.fee'))
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->prefix('$')
                                    ->columnSpan(1),
                            ])
                            ->columns(2)
                            ->addActionLabel(__('shipping_method.province_fees.add'))
                            ->reorderable(false)
                            ->cloneable(false)
                            ->defaultItems(0),
                    ]),
            ]);
    }
}
