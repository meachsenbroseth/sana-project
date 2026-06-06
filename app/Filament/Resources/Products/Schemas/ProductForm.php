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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; // Added for image deletion

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make(__('product.tabs.product_details'))
                    ->columnSpanFull()
                    ->tabs([

                        // --- BASIC INFORMATION ---
                        Tab::make(__('product.tabs.basic_information'))
                            ->icon(Heroicon::InformationCircle)
                            ->schema([
                                Section::make(__('product.sections.product_details'))->schema([
                                    TextInput::make('name')
                                        ->required(),
                                    TextInput::make('slug')
                                        ->unique(ignoreRecord: true)
                                        ->visible(fn (string $operation) => $operation === 'edit')
                                        ->disabled()
                                        ->dehydrated() // FIX: Ensures the disabled field is sent to the DB
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
                                Section::make(__('product.sections.product_description'))->schema([
                                    RichEditor::make('description')
                                        ->columnSpanFull(),
                                ]),
                            ]),

                        // --- PRICING & INVENTORY ---
                        Tab::make(__('product.tabs.pricing_inventory'))
                            ->icon(Heroicon::CurrencyDollar)
                            ->schema([
                                Section::make(__('product.sections.pricing'))->schema([
                                    TextInput::make('sku')
                                        ->label(__('product.sku'))
                                        ->disabled()
                                        ->dehydrated() // FIX: Ensures the disabled SKU is sent to the DB
                                        ->unique(ignoreRecord: true)
                                        ->default(fn () => 'SKU-'.strtoupper(Str::random(8)))
                                        ->helperText(__('product.help.sku'))
                                        ->required(),
                                    TextInput::make('price')
                                        ->required()
                                        ->numeric()
                                        ->helperText(__('product.help.price'))
                                        ->minValue(0)
                                        ->step(0.01)
                                        ->prefix('$'),
                                    TextInput::make('compare_price')
                                        ->numeric()
                                        ->helperText(__('product.help.compare_price'))
                                        ->minValue(0)
                                        ->step(0.01)
                                        ->prefix('$'),
                                    TextInput::make('cost_price')
                                        ->numeric()
                                        ->helperText(__('product.help.cost_price'))
                                        ->minValue(0)
                                        ->step(0.01)
                                        ->prefix('$'),
                                ])->columns(2),
                                Section::make(__('product.sections.inventory'))->schema([
                                    Toggle::make('manage_stock')
                                        ->label(__('product.manage_stock'))
                                        ->default(true)
                                        ->helperText(__('product.help.manage_stock'))
                                        ->live(),
                                    TextInput::make('stock_quantity')
                                        ->label(__('product.stock'))
                                        ->required(fn (callable $get) => $get('manage_stock'))
                                        ->disabled(fn (callable $get) => ! $get('manage_stock'))
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0),
                                    TextInput::make('low_stock_threshold')
                                        ->label(__('product.low_stock_threshold'))
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0)
                                        ->helperText(__('product.help.low_stock_threshold')),
                                    ToggleButtons::make('stock_status')
                                        ->label(__('product.stock_availability'))
                                        ->options([
                                            'in_stock' => __('product.stock_status.in_stock'),
                                            'out_of_stock' => __('product.stock_status.out_of_stock'),
                                            'pre_order' => __('product.stock_status.pre_order'),
                                        ])
                                        ->grouped()
                                        ->default('in_stock')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->helperText('Stock status is updated automatically from the stock quantity.')
                                        ->required(),
                                ])->columns(2),
                            ]),

                        // --- IMAGES ---
                        Tab::make(__('product.tabs.images'))
                            ->icon(Heroicon::Photo)
                            ->schema([
                                Section::make(__('product.sections.images'))
                                    ->description(__('product.help.images'))
                                    ->schema([
                                        FileUpload::make('images')
                                            ->label(__('product.images'))
                                            ->multiple()
                                            ->image()
                                            ->directory('products')
                                            ->imageEditor()
                                            ->maxSize(2048)
                                            ->disk('public')
                                            ->reorderable()
                                            ->columnSpanFull()
                                            ->helperText(__('product.help.reorder_images'))
                                            ->saveRelationshipsUsing(function ($component, $state, $record) {

                                                // FIX: Delete physical files from disk before wiping DB records
                                                $existingImages = $record->images()->get();
                                                foreach ($existingImages as $image) {
                                                    Storage::disk('public')->delete($image->image_path);
                                                }

                                                // Delete existing database records
                                                $record->images()->delete();

                                                // Save new images and relationships
                                                if (is_array($state)) {
                                                    foreach ($state as $index => $imagePath) {
                                                        $record->images()->create([
                                                            'image_path' => $imagePath,
                                                            'is_primary' => $index === 0, // First image is primary
                                                            'sort_order' => $index,
                                                        ]);
                                                    }
                                                }
                                            })
                                            ->dehydrated(false),
                                    ]),
                            ]),

                        // --- SETTINGS ---
                        Tab::make(__('product.tabs.setting'))
                            ->icon(Heroicon::Cog6Tooth)
                            ->schema([
                                Section::make(__('product.sections.status'))->schema([
                                    ToggleButtons::make('status')
                                        ->options([
                                            'new' => __('product.status.new'),
                                            'used' => __('product.status.used'),
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
                                Section::make(__('product.sections.statistics'))->schema([
                                    Placeholder::make('view_count')
                                        ->content(fn ($record) => $record?->view_count ?? 0),
                                    Placeholder::make('created_at')
                                        ->label(__('product.created'))
                                        ->content(fn ($record) => $record?->created_at?->diffForHumans() ?? '-'),
                                ]),
                            ]),

                        // --- SEO ---
                        Tab::make(__('product.tabs.seo'))
                            ->icon(Heroicon::MagnifyingGlass)
                            ->schema([
                                Section::make(__('product.sections.seo'))->schema([
                                    TextInput::make('meta_title'),
                                    Textarea::make('meta_description')
                                        ->columnSpanFull(),
                                ]),
                            ]),
                    ]),
            ]);
    }
}
