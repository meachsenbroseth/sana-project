<?php

namespace App\Filament\Resources\Reviews\Schemas;

use App\Models\Review;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Review Details')->schema([
                    Placeholder::make('product_name')
                        ->label('Product')
                        ->content(fn (?Review $record): string => $record?->product?->name ?? '-'),
                    Placeholder::make('customer_name')
                        ->label('Customer')
                        ->content(fn (?Review $record): string => $record?->customer?->name ?? '-'),
                    Select::make('rating')
                        ->options([
                            1 => '1',
                            2 => '2',
                            3 => '3',
                            4 => '4',
                            5 => '5',
                        ])
                        ->native(false)
                        ->required(),
                    TextInput::make('title')
                        ->maxLength(255),
                    Textarea::make('comment')
                        ->rows(5),
                    Placeholder::make('is_verified_purchase')
                        ->label('Verified Purchase')
                        ->content(fn (?Review $record): HtmlString => new HtmlString(
                            $record?->is_verified_purchase
                                ? '<span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Verified</span>'
                                : '<span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">Not Verified</span>'
                        )),
                    Toggle::make('is_approved')
                        ->label('Approved')
                        ->helperText('Only approved reviews are publicly visible.')
                        ->required(),
                ])->columns(2),
            ]);
    }
}
