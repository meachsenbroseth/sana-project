<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Common Information')->schema([
                    TextInput::make('name')
                        ->required(),
                    TextInput::make('email')
                        ->label('Email address')
                        ->unique(ignoreRecord: true)
                        ->email()
                        ->required(),
                    DateTimePicker::make('email_verified_at')
                    ->native(false)
                    ->native()
                    ->displayFormat('M d, Y')
                    ->required(),
                    TextInput::make('phone')
                        ->tel(),
                    DatePicker::make('date_of_birth'),
                    Select::make('gender')
                        ->options([
                            'male' => 'Male',
                            'female' => 'Female',
                            'other' => 'Other',
                        ])
                        ->native(false)
                        ->required(),
                    Toggle::make('is_active')
                        ->default(true)
                        ->required(),
                ])->columns(2),
                Section::make('Password Infos')->schema([
                    TextInput::make('password')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn($state) =>filled($state) ? Hash::make($state) : null)
                    ->required(fn (string $operation) => $operation === 'create')
                    ->revealable()
                    ->required(),
                    TextInput::make('password confirmation')
                    ->password()
                    ->same('password')
                    ->dehydrated(false)
                    ->revealable()
                    ->required(fn (string $operation) => $operation === 'create'),
                ])
            ]);
    }
}
