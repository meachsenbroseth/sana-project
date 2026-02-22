<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Order Management')
                    ->columnSpanFull()
                    ->tabs([

                        // ==========================================
                        // TAB 1: ORDER DETAILS & ITEMS (READ-ONLY)
                        // ==========================================
                        Tab::make('Order Details')
                            ->icon('heroicon-m-shopping-cart')
                            ->schema([

                                // --- 1. CUSTOMER & BASIC INFO ---
                                Section::make('Customer Information')->schema([
                                    Select::make('customer_id')
                                        ->relationship('customer', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->disabled() // LOCKED
                                        ->required(),
                                    TextInput::make('order_number')
                                        ->disabled() // LOCKED
                                        ->dehydrated()
                                        ->required(),
                                ])->columns(2),

                                // --- 1.5 SHIPPING INFORMATION ---
                                Section::make('Shipping Information')->schema([
                                    TextInput::make('shipping_full_name')
                                        ->label('Recipient Name')
                                        ->disabled(),
                                    TextInput::make('shipping_phone')
                                        ->label('Phone Number')
                                        ->disabled(),
                                    TextInput::make('shipping_address_line_1')
                                        ->label('Address Line 1')
                                        ->columnSpanFull()
                                        ->disabled(),
                                    TextInput::make('shipping_address_line_2')
                                        ->label('Address Line 2')
                                        ->columnSpanFull()
                                        ->disabled(),
                                    TextInput::make('shipping_city')
                                        ->label('City')
                                        ->disabled(),
                                    TextInput::make('shipping_state')
                                        ->label('State/Province')
                                        ->disabled(),
                                    TextInput::make('shipping_country')
                                        ->label('Country')
                                        ->disabled(),
                                ])->columns(2),

                                // --- 2. ORDER ITEMS ---
                                Section::make('Order Items')->schema([
                                    Repeater::make('items')
                                        ->relationship()
                                        ->disabled() // LOCKED
                                        ->schema([
                                            Select::make('product_id')
                                                ->relationship('product', 'name')
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->columnSpan(3),

                                            // ADDED: Product SKU
                                            TextInput::make('product_sku')
                                                ->label('Product SKU')
                                                ->copyable()
                                                // ->extraInputAttributes([
                                                //     'x-on:click' => '$clipboard($event.target.value); $tooltip(\'Copied!\')',
                                                //     'style' => 'cursor: pointer;',
                                                //     'title' => 'Highlight to copy',
                                                // ])
                                                ->columnSpan(3),

                                            TextInput::make('quantity')
                                                ->numeric()
                                                ->required()
                                                ->columnSpan(2),

                                            TextInput::make('unit_amount')
                                                ->label('Unit Price')
                                                ->numeric()
                                                ->required()
                                                ->prefix('$')
                                                ->columnSpan(2),

                                            TextInput::make('total_amount')
                                                ->label('Item Total')
                                                ->numeric()
                                                ->required()
                                                ->prefix('$')
                                                ->columnSpan(2),
                                        ])
                                        ->columns(12) // UPGRADED: Expanded to 12 columns so the new SKU fits perfectly
                                ]),

                                // --- 3. FINANCIALS ---
                                Section::make('Order Totals')->schema([
                                    TextInput::make('subtotal')
                                        ->numeric()
                                        ->prefix('$')
                                        ->disabled() // LOCKED
                                        ->default(0),
                                    TextInput::make('discount_amount')
                                        ->numeric()
                                        ->prefix('$')
                                        ->disabled() // LOCKED
                                        ->default(0),
                                    TextInput::make('shipping_cost')
                                        ->numeric()
                                        ->prefix('$')
                                        ->disabled() // LOCKED
                                        ->default(0),
                                    TextInput::make('total')
                                        ->numeric()
                                        ->prefix('$')
                                        ->disabled() // LOCKED
                                        ->required(),
                                ])->columns(4),
                            ]),

                        // ==========================================
                        // TAB 2: STATUS & FULFILLMENT (EDITABLE)
                        // ==========================================
                        Tab::make('Status & Fulfillment')
                            ->icon('heroicon-m-truck')
                            ->schema([

                                // --- 4. ORDER STATUS ---
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

                                    Select::make('payment_status')
                                        ->options([
                                            'pending' => 'Pending',
                                            'paid' => 'Paid',
                                            'failed' => 'Failed',
                                        ])
                                        ->native(false)
                                        ->required()
                                        ->default('pending'),

                                    TextInput::make('tracking_number')
                                        ->helperText('Shipping tracking number'),

                                    Textarea::make('admin_notes')
                                        ->columnSpanFull(),
                                ])->columns(2),
                            ]),
                    ]),
            ]);
    }
}
