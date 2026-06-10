<?php

namespace App\Filament\Exports;

use App\Models\Order;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

class AnalyticsOrderReportExporter extends Exporter
{
    protected static ?string $model = Order::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('order_number')
                ->label(__('analytics.columns.order_number')),
            ExportColumn::make('customer.name')
                ->label(__('analytics.columns.customer')),
            ExportColumn::make('status')
                ->label(__('analytics.columns.status'))
                ->formatStateUsing(fn (string $state): string => __('order.status.'.$state, [], app()->getLocale()) !== 'order.status.'.$state
                    ? __('order.status.'.$state)
                    : ucfirst($state)),
            ExportColumn::make('payment_method')
                ->label(__('analytics.columns.payment_method'))
                ->formatStateUsing(fn (string $state): string => __('analytics.payment_methods.'.$state, [], app()->getLocale()) !== 'analytics.payment_methods.'.$state
                    ? __('analytics.payment_methods.'.$state)
                    : $state),
            ExportColumn::make('total')
                ->label(__('analytics.columns.total'))
                ->prefix('$'),
            ExportColumn::make('created_at')
                ->label(__('analytics.columns.created_date'))
                ->formatStateUsing(fn ($state) => filled($state) ? Carbon::parse($state)->format('Y-m-d H:i') : null),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = __('analytics.export.completed', [
            'count' => Number::format($export->successful_rows),
        ]);

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.__('analytics.export.failed', [
                'count' => Number::format($failedRowsCount),
            ]);
        }

        return $body;
    }
}
