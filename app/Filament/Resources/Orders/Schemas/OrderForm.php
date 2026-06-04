<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make(__('order.tabs.management'))
                    ->columnSpanFull()
                    ->tabs([

                        // ==========================================
                        // TAB 1: ORDER DETAILS & ITEMS (READ-ONLY)
                        // ==========================================
                        Tab::make(__('order.tabs.details'))
                            ->icon('heroicon-m-shopping-cart')
                            ->schema([

                                // --- 1. CUSTOMER & BASIC INFO ---
                                Section::make(__('order.sections.customer_information'))->schema([
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
                                Section::make(__('order.sections.shipping_information'))->schema([
                                    TextInput::make('shipping_full_name')
                                        ->label(__('order.recipient_name'))
                                        ->disabled(),
                                    TextInput::make('shipping_phone')
                                        ->label(__('order.phone_number'))
                                        ->disabled(),
                                    TextInput::make('shipping_address_line_1')
                                        ->label(__('order.address_line_1'))
                                        ->columnSpanFull()
                                        ->disabled(),
                                    TextInput::make('shipping_address_line_2')
                                        ->label(__('order.address_line_2'))
                                        ->columnSpanFull()
                                        ->disabled(),
                                    TextInput::make('shipping_city')
                                        ->label(__('order.city'))
                                        ->disabled(),
                                    TextInput::make('shipping_state')
                                        ->label(__('order.state'))
                                        ->disabled(),
                                    TextInput::make('shipping_country')
                                        ->label(__('order.country'))
                                        ->disabled(),
                                ])->columns(2),

                                // --- 2. ORDER ITEMS ---
                                Section::make(__('order.sections.items'))->schema([
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
                                                ->label(__('order.product_sku'))
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
                                                ->label(__('order.unit_price'))
                                                ->numeric()
                                                ->required()
                                                ->prefix('$')
                                                ->columnSpan(2),

                                            TextInput::make('total_amount')
                                                ->label(__('order.item_total'))
                                                ->numeric()
                                                ->required()
                                                ->prefix('$')
                                                ->columnSpan(2),
                                        ])
                                        ->columns(12), // UPGRADED: Expanded to 12 columns so the new SKU fits perfectly
                                ]),

                                // --- 3. FINANCIALS ---
                                Section::make(__('order.sections.totals'))->schema([
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
                        Tab::make(__('order.tabs.status_fulfillment'))
                            ->icon('heroicon-m-truck')
                            ->schema([

                                // --- 4. ORDER STATUS ---
                                Section::make(__('order.sections.order_status'))->schema([
                                    Select::make('status')
                                        ->label(__('order.status_label'))
                                        ->options([
                                            'pending' => __('order.status.pending'),
                                            'processing' => __('order.status.processing'),
                                            'shipped' => __('order.status.shipped'),
                                            'delivered' => __('order.status.delivered'),
                                            'cancelled' => __('order.status.cancelled'),
                                        ])
                                        ->native(false)
                                        ->required()
                                        ->default('pending'),

                                    Select::make('payment_status')
                                        ->options([
                                            'pending' => __('order.payment_status.pending'),
                                            'paid' => __('order.payment_status.paid'),
                                            'failed' => __('order.payment_status.failed'),
                                        ])
                                        ->native(false)
                                        ->required()
                                        ->default('pending'),

                                    TextInput::make('tracking_number')
                                        ->helperText(__('order.tracking_help')),

                                    Textarea::make('admin_notes')
                                        ->columnSpanFull(),
                                ])->columns(2),
                            ]),
                    ]),
            ]);
    }
}
