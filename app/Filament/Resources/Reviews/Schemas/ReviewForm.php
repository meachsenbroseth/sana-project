<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Moderation')->schema([
                    Toggle::make('is_approved')
                    ->label('Approve Reviwe')
                    ->helperText('Approve to show on product page')
                        ->required(),
                ])

            ]);
    }
}
