<?php

namespace App\Filament\Resources\Brands\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('brand.sections.information'))->schema([
                    TextInput::make('name')
                        ->live(onBlur: true)
                        // FIX: Auto-generates the slug when typing the name (only on create)
                        ->afterStateUpdated(fn (string $operation, $state, callable $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null)
                        ->required(),

                    TextInput::make('slug')
                        ->disabled()
                        ->dehydrated() // FIX: Ensures the disabled field actually saves to the DB
                        ->unique(ignoreRecord: true)
                        ->required(),

                    FileUpload::make('image')
                        ->disk('public')
                        ->directory('brands') // FIX: Changed from 'categories' to 'brands'
                        ->imageEditor()
                        ->preserveFilenames()
                        ->downloadable()
                        ->image()
                        ->columnSpanFull(), // Optional: Makes the image uploader span the full width of its container for better UX
                ])->columnSpanFull()
                    ->columns(2),

                Section::make(__('brand.sections.display_settings'))->schema([
                    Toggle::make('is_active')
                        ->default(true)
                        ->required(),

                    TextInput::make('sort_order')
                        ->required()
                        ->numeric()
                        ->default(0),
                ])->columns(2),

            ]);
    }
}
