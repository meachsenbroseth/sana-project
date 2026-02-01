<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                            ->required(),
                        TextInput::make('slug')
                            ->disabled()
                            ->unique(ignoreRecord: true)
                            ->visibleOn('edit'),
                        FileUpload::make('image')
                            ->disk('public')
                            ->directory('categories')
                            ->visibility('public')
                            ->downloadable()
                            ->imageEditor()
                            ->preserveFilenames()
                            ->image()
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
