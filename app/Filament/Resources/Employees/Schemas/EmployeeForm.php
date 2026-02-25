<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Filament\Resources\Employees\EmployeeResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Info')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Toggle::make('is_active')
                            ->label('Status')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Password')
                    ->schema([
                        Toggle::make('change_password')
                            ->label('Change password')
                            ->live()
                            ->default(false)
                            ->hidden(fn (string $operation): bool => $operation === 'create'),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn (mixed $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->hidden(fn (Get $get, string $operation): bool => $operation === 'edit' && ! $get('change_password')),
                    ])
                    ->columns(2),
                Section::make('Role Assignment')
                    ->schema([
                        Select::make('roles')
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query): Builder {
                                    $query->orderBy('name');

                                    if (! Auth::user()?->hasRole(config('filament-shield.super_admin.name', 'super_admin'))) {
                                        $query->where('name', '!=', config('filament-shield.super_admin.name', 'super_admin'));
                                    }

                                    return $query;
                                }
                            )
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required(fn (): bool => EmployeeResource::canManageRoles())
                            ->dehydrated(fn (): bool => EmployeeResource::canManageRoles())
                            ->visible(fn (): bool => EmployeeResource::canManageRoles()),
                    ]),
            ]);
    }
}
