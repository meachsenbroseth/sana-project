<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str; // FIX: Imported Str helper for slug generation

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category Information')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->live(onBlur: true)
                            // FIX: Auto-generates the slug when typing the name (only on create)
                            ->afterStateUpdated(fn (string $operation, $state, callable $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null)
                            ->required(),

                        TextInput::make('slug')
                            ->disabled()
                            ->dehydrated() // FIX: Ensures the disabled field actually saves to the DB
                            ->unique(ignoreRecord: true)
                            ->required(), // Added required since slugs shouldn't be null

                        FileUpload::make('image')
                            ->disk('public')
                            ->directory('categories')
                            ->visibility('public')
                            ->downloadable()
                            ->imageEditor()
                            ->preserveFilenames()
                            ->image()
                            ->columnSpanFull() // Optional: Makes it look a bit cleaner across the bottom
                            ->required(),
                    ]),

                Section::make('Display Settings')->schema([
                    Toggle::make('is_active')
                        ->default(true)
                        ->required(),

                    TextInput::make('sort_order')
                        ->required()
                        ->numeric()
                        ->default(0),
                ])->columns(2),

                Section::make('SEO')->schema([
                    TextInput::make('meta_title'),
                    Textarea::make('meta_description')
                        ->columnSpanFull(),
                ]),
            ]);
    }
}
