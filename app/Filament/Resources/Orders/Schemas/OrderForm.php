<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

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

                                Section::make(__('order.sections.customer_information'))->schema([
                                    Select::make('customer_id')
                                        ->relationship('customer', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->disabled()
                                        ->required(),
                                    TextInput::make('order_number')
                                        ->disabled()
                                        ->dehydrated()
                                        ->required(),
                                ])->columns(2),

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

                                Section::make(__('order.sections.items'))->schema([
                                    Repeater::make('items')
                                        ->relationship()
                                        ->disabled()
                                        ->schema([
                                            Select::make('product_id')
                                                ->relationship('product', 'name')
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->columnSpan(3),

                                            TextInput::make('product_sku')
                                                ->label(__('order.product_sku'))
                                                ->copyable()
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
                                        ->columns(12),
                                ]),

                                Section::make(__('order.sections.totals'))->schema([
                                    TextInput::make('subtotal')
                                        ->numeric()
                                        ->prefix('$')
                                        ->disabled()
                                        ->default(0),
                                    TextInput::make('discount_amount')
                                        ->numeric()
                                        ->prefix('$')
                                        ->disabled()
                                        ->default(0),
                                    TextInput::make('shipping_cost')
                                        ->numeric()
                                        ->prefix('$')
                                        ->disabled()
                                        ->default(0),
                                    TextInput::make('total')
                                        ->numeric()
                                        ->prefix('$')
                                        ->disabled()
                                        ->required(),
                                ])->columns(4),
                            ]),

                        // ==========================================
                        // TAB 2: STATUS & FULFILLMENT (EDITABLE)
                        // ==========================================
                        Tab::make(__('order.tabs.status_fulfillment'))
                            ->icon('heroicon-m-truck')
                            ->schema([

                                Section::make(__('order.sections.order_status'))->schema([
                                    ToggleButtons::make('status')
                                        ->label(__('order.status_label'))
                                        ->options([
                                            'pending' => __('order.status.pending'),
                                            'processing' => __('order.status.processing'),
                                            'shipped' => __('order.status.shipped'),
                                            'delivered' => __('order.status.delivered'),
                                            'cancelled' => __('order.status.cancelled'),
                                        ])
                                        ->grouped()
                                        ->icons([
                                            'pending' => 'heroicon-o-clock',
                                            'processing' => 'heroicon-o-arrow-path',
                                            'shipped' => 'heroicon-o-truck',
                                            'delivered' => 'heroicon-o-check-circle',
                                            'cancelled' => 'heroicon-o-x-circle',
                                        ])
                                        ->colors([
                                            'pending' => 'warning',
                                            'processing' => 'info',
                                            'shipped' => 'primary',
                                            'delivered' => 'success',
                                            'cancelled' => 'danger',
                                        ])
                                        ->required()
                                        ->default('pending')
                                        ->columnSpanFull(),

                                    ToggleButtons::make('payment_status')
                                        ->options([
                                            'pending' => __('order.payment_status.pending'),
                                            'paid' => __('order.payment_status.paid'),
                                            'failed' => __('order.payment_status.failed'),
                                        ])
                                        ->grouped()
                                        ->icons([
                                            'pending' => 'heroicon-o-clock',
                                            'paid' => 'heroicon-o-check-circle',
                                            'failed' => 'heroicon-o-x-circle',
                                        ])
                                        ->colors([
                                            'pending' => 'warning',
                                            'paid' => 'success',
                                            'failed' => 'danger',
                                        ])
                                        ->required()
                                        ->default('pending')
                                        ->columnSpanFull(),

                                    TextInput::make('tracking_number')
                                        ->helperText(__('order.tracking_help')),

                                    Textarea::make('admin_notes')
                                        ->columnSpanFull(),
                                ])->columns(2),

                                // --- DONE TRACKING (READ-ONLY, AUTO-SET) ---
                                Section::make(__('order.sections.completion'))
                                    ->schema([
                                        TextInput::make('done_by_name')
                                            ->label(__('order.done_by'))
                                            ->formatStateUsing(fn ($record) => $record?->doneBy?->name)
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->placeholder('—'),

                                        DateTimePicker::make('done_at')
                                            ->label(__('order.done_at'))
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->native(false),
                                    ])
                                    ->columns(2)
                                    ->visible(fn ($record) => $record?->done_at !== null),
                            ]),

                        // ==========================================
                        // TAB 3: ORDER ITEM (READ-ONLY, VISUAL)
                        // ==========================================
                        Tab::make(__('order.tabs.order_item'))
                            ->icon('heroicon-m-photo')
                            ->schema([

                                Section::make(__('order.sections.order_item'))->schema([
                                    Repeater::make('items')
                                        ->relationship()
                                        ->disabled()
                                        ->reorderable(false)
                                        ->addable(false)
                                        ->deletable(false)
                                        ->schema([
                                            Placeholder::make('product_image')
                                                ->label(__('order.product_image'))
                                                ->content(function ($record): HtmlString {
                                                    $imageUrl = $record?->product?->primeImage?->url;

                                                    if ($imageUrl) {
                                                        return new HtmlString(
                                                            '<img src="'.e($imageUrl).'"'
                                                            .' alt="'.e($record->product_name ?? '').'"'
                                                            .' class="h-28 w-28 rounded-lg object-contain bg-gray-50 p-1 shadow">'
                                                        );
                                                    }

                                                    return new HtmlString(
                                                        '<div class="flex h-28 w-28 items-center justify-center rounded-lg bg-gray-100 text-gray-400">'
                                                        .'<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-10 w-10">'
                                                        .'<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 20.25h18M3.75 4.5h16.5a.75.75 0 01.75.75v13.5a.75.75 0 01-.75.75H3.75a.75.75 0 01-.75-.75V5.25a.75.75 0 01.75-.75z" />'
                                                        .'</svg>'
                                                        .'</div>'
                                                    );
                                                })
                                                ->columnSpan(2),

                                            TextInput::make('product_name')
                                                ->label(__('table.name'))
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->columnSpan(2),

                                            TextInput::make('product_sku')
                                                ->label(__('order.product_sku'))
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->copyable()
                                                ->columnSpan(2),

                                            TextInput::make('quantity')
                                                ->numeric()
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->columnSpan(1),

                                            TextInput::make('unit_amount')
                                                ->label(__('order.unit_price'))
                                                ->numeric()
                                                ->prefix('$')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->columnSpan(2),

                                            TextInput::make('total_amount')
                                                ->label(__('order.item_total'))
                                                ->numeric()
                                                ->prefix('$')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->columnSpan(3),
                                        ])
                                        ->columns(12),
                                ]),

                            ]),
                    ]),
            ]);
    }
}