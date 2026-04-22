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
                Section::make(__('review.sections.details'))->schema([
                    Placeholder::make('product_name')
                        ->label(__('nav.product'))
                        ->content(fn (?Review $record): string => $record?->product?->name ?? '-'),
                    Placeholder::make('customer_name')
                        ->label(__('nav.customer'))
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
                        ->label(__('review.verified_purchase'))
                        ->content(fn (?Review $record): HtmlString => new HtmlString(
                            $record?->is_verified_purchase
                                ? '<span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">'.__('review.verified').'</span>'
                                : '<span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">'.__('review.not_verified').'</span>'
                        )),
                    Toggle::make('is_approved')
                        ->label(__('review.approved'))
                        ->helperText(__('review.approved_help'))
                        ->required(),
                ])->columns(2),
            ]);
    }
}
