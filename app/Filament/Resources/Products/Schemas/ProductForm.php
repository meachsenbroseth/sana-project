<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Product Details')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Basic Information')
                            ->icon(Heroicon::InformationCircle)
                            ->schema([
                                Section::make('Product Details')->schema([
                                    TextInput::make('name')
                                        ->required(),
                                    TextInput::make('slug')
                                        ->unique(ignoreRecord: true)
                                        ->visible(fn (string $operation) => $operation === 'edit')
                                        ->disabled()
                                        ->required(),
                                    Select::make('category_id')
                                        ->relationship('category', 'name')
                                        ->preload()
                                        ->searchable()
                                        ->required(),
                                    Select::make('brand_id')
                                        ->relationship('brand', 'name')
                                        ->preload()
                                        ->searchable()
                                        ->required(),
                                ])->columns(2),
                                Section::make('Product Description')->schema([
                                    RichEditor::make('description')
                                        ->columnSpanFull(),
                                ]),
                            ]),
                        Tab::make('Pricing & Inventory')
                            ->icon(Heroicon::CurrencyDollar)
                            ->schema([
                                Section::make('Pricing')->schema([
                                    TextInput::make('sku')
                                        ->label('SKU')
                                        ->disabled()
                                        ->unique(ignoreRecord: true)
                                        ->default(fn () => 'SKU-'.strtoupper(Str::random(8)))
                                        ->helperText('Stock Keeping Unit - unique identifier')
                                        ->required(),
                                    TextInput::make('price')
                                        ->required()
                                        ->numeric()
                                        ->helperText('Selling price')
                                        ->minValue(0)
                                        ->step(0.01)
                                        ->prefix('$'),
                                    TextInput::make('compare_price')
                                        ->numeric()
                                        ->helperText('Oreginal price before discount')
                                        ->minValue(0)
                                        ->step(0.01)
                                        ->prefix('$'),
                                    TextInput::make('cost_price')
                                        ->numeric()
                                        ->helperText('cost from supplier (for profit calculation)')
                                        ->minValue(0)
                                        ->step(0.01)
                                        ->prefix('$'),
                                ])->columns(2),
                                Section::make('Inventory')->schema([
                                    Toggle::make('manage_stock')
                                        ->label('Manage Stock')
                                        ->default(true)
                                        ->helperText('Enable to stock management for this product')
                                        ->live(),
                                    TextInput::make('stock_quantity')
                                        ->label('Stock Quantity')
                                        ->required(fn (callable $get) => $get('manage_stock'))
                                        ->disabled(fn (callable $get) => ! $get('manage_stock'))
                                        ->numeric()
                                        ->default(0),
                                    TextInput::make('low_stock_threshold')
                                        ->label('Low stock Alert Threshold')
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0)
                                        ->helperText('Get notified when stock falls below this number'),
                                    ToggleButtons::make('stock_status')
                                        ->options([
                                            'in_stock' => 'In Stock',
                                            'out_of_stock' => 'Out of Stock',
                                            'pre_order' => 'Pre Order',
                                        ])
                                        ->grouped()
                                        ->default('in_stock')
                                        ->required(),
                                ])->columns(2),
                            ]),
                        Tab::make('Images')
                            ->icon(Heroicon::Photo)
                            ->schema([
                                Section::make('Product Images')
                                    ->description('Upload mutiple images, The first image will be pimery imgage.')
                                    ->schema([
                                        FileUpload::make('images')
                                            ->label('Product Images')
                                            ->multiple()
                                            ->image()
                                            ->directory('products')
                                            ->imageEditor()
                                            ->maxSize(2048)
                                            ->disk('public')
                                            ->reorderable()
                                            ->columnSpanFull()
                                            ->helperText('You can drag and drop to reorder images')
                                            ->saveRelationshipsUsing(function ($component, $state, $record) {
                                                // delete exisiting images
                                                $record->images()->delete();

                                                if (is_array($state)) {
                                                    foreach ($state as $index => $imagePath) {
                                                        $record->images()->create([
                                                            'image_path' => $imagePath,
                                                            'is_primary' => $index === 0,
                                                            'sort_order' => $index,
                                                        ]);
                                                    }
                                                }
                                            })
                                            ->dehydrated(false),
                                    ]),
                            ]),
                        Tab::make('Setting')
                            ->icon(Heroicon::Cog6Tooth)
                            ->schema([
                                Section::make('Product status')->schema([
                                    ToggleButtons::make('status')
                                        ->options([
                                            'new' => 'New',
                                            'used' => 'Used',
                                        ])
                                        ->grouped()
                                        ->default('new')
                                        ->required(),
                                    Toggle::make('is_active')
                                        ->default(true)
                                        ->required(),
                                    Toggle::make('is_featured')
                                        ->required(),
                                ])->columns(2),
                                Section::make('statistics')->schema([
                                    Placeholder::make('view_count')
                                        ->content(fn ($record) => $record?->view_count ?? 0),
                                    Placeholder::make('created_at')
                                        ->label('Created')
                                        ->content(fn ($record) => $record?->created_at?->diffForHumans() ?? '-'),
                                ]),
                            ]),
                        Tab::make('SEO')
                            ->icon(Heroicon::MagnifyingGlass)
                            ->schema([
                                Section::make('Search Engine Optimization')->schema([
                                    TextInput::make('meta_title'),
                                    Textarea::make('meta_description')
                                        ->columnSpanFull(),
                                ]),
                            ]),
                    ]),

            ]);
    }
}
