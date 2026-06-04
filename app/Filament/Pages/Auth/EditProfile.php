<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class EditProfile extends BaseEditProfile
{
    public function hasTopbar(): bool
    {
        return true;
    }

    public static function isSimple(): bool
    {
        return false;
    }

    public function getMaxWidth(): ?string
    {
        return '7xl'; // ✅ Tailwind max width
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('ProfileTabs')
                ->tabs([
                    Tab::make('Profile')
                        ->schema([
                            FileUpload::make('avatar')
                                ->image()
                                ->avatar()
                                ->directory('avatars')
                                ->disk('public')
                                ->visibility('public'),

                            TextInput::make('name')->required(),

                            TextInput::make('email')
                                ->email()
                                ->required()
                                ->unique(ignoreRecord: true),
                        ]),

                    Tab::make('Security')
                        ->schema([
                            TextInput::make('password')
                                ->password()
                                ->revealable()
                                ->dehydrateStateUsing(fn ($state) => \Illuminate\Support\Facades\Hash::make($state))
                                ->dehydrated(fn ($state) => filled($state))
                                ->same('passwordConfirmation'),

                            TextInput::make('passwordConfirmation')
                                ->password()
                                ->dehydrated(false),

                            TextInput::make('currentPassword')
                                ->password()
                                ->currentPassword()
                                ->required(fn ($get) => filled($get('password')) ||
                                    $get('email') !== $this->getUser()->email
                                )
                                ->dehydrated(false),
                        ]),
                ]),
        ]);
    }
}
