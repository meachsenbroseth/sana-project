<?php

namespace App\Filament\Widgets;

use App\Services\Ai\BusinessIntelligenceContextService;
use Filament\Widgets\ChartWidget;

class AiProductDemandChart extends ChartWidget
{

    public static function canView(): bool
    {
        return false;
    }

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return __('analytics.ai_assistant.demand_chart_heading');
    }


    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $products = collect(data_get(app(BusinessIntelligenceContextService::class)->snapshot(), 'products.fast_moving', []));

        return [
            'datasets' => [
                [
                    'label' => __('analytics.ai_assistant.demand_chart_label'),
                    'data' => $products->pluck('quantity_sold_30_days')->values()->all(),
                    'backgroundColor' => ['#2563eb', '#059669', '#d97706', '#dc2626', '#7c3aed', '#0891b2', '#4b5563', '#65a30d'],
                ],
            ],
            'labels' => $products->pluck('name')->values()->all(),
        ];
    }
}
