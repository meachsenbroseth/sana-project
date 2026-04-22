<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public static function getNavigationLabel(): string
    {
        return __('messages.dashboard.title');
    }

    public function getTitle(): string
    {
        return __('messages.dashboard.title');
    }
}
