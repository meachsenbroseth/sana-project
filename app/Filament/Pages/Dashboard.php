<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public static function getNavigationLabel(): string
    {
        return __('messages.dashboard.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('View:' . class_basename(static::class)) ?? false;
    }

    public function getTitle(): string
    {
        return __('messages.dashboard.title');
    }
}
