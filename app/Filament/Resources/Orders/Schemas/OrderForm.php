<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Status')->schema([
                    Select::make('status')
                        ->label('Order Status')
                        ->options([
                            'pending' => 'Pending',
                            'processing' => 'Processing',
                            'shipped' => 'Shipped',
                            'delivered' => 'Delivered',
                            'cancelled' => 'Cancelled'
                        ])
                        ->native(false)
                        ->required()
                        ->default('pending'),
                    TextInput::make('tracking_number')
                        ->helperText('Shipping tracking number'),
                    Select::make('payment_status')
                        ->options([
                            'pending' => 'Pending',
                            'paid' => 'Paid',
                            'failed' => 'Failed',
                        ])
                        ->native(false)
                        ->required()
                        ->default('pending'),
                    Textarea::make('admin_notes')
                        ->columnSpanFull(),
                ])->columnSpanFull()
                ->columns(2),
            ]);
    }
}
