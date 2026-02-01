<?php

namespace App\Filament\Resources\Brands\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Brand Information')->schema([
                    TextInput::make('name')
                        ->required(),
                    TextInput::make('slug')
                        ->visibleOn('edit')
                        ->disabled()
                        ->unique(ignoreRecord: true)
                        ->required(),
                    FileUpload::make('image')
                        ->disk('public')
                        ->directory('brands')
                        ->visibility('public')
                        ->downloadable()
                        ->imageEditor()
                        ->preserveFilenames()
                        ->image(),
                ])->columnSpanFull()
                ->columns(2),
                Section::make('Display Settings')->schema([
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
