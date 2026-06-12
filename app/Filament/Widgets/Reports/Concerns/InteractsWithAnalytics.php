<?php

namespace App\Filament\Widgets\Reports\Concerns;

use App\Services\Analytics\AnalyticsFilters;
use App\Services\Analytics\AnalyticsService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

trait InteractsWithAnalytics
{
    use InteractsWithPageFilters;

    // protected static bool $isDiscovered = false;

    protected static bool $isLazy = true;

    protected function analytics(): AnalyticsService
    {
        return app(AnalyticsService::class);
    }

    protected function filters(): AnalyticsFilters
    {
        return AnalyticsFilters::fromPageFilters($this->pageFilters);
    }

    protected function analyticsOperational(): bool
    {
        return $this->analytics()->isOperational();
    }

    protected function productsAvailable(): bool
    {
        return $this->analytics()->productsAvailable();
    }
}
