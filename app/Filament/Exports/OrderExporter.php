<?php

namespace App\Filament\Exports;

use App\Models\Order;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

class OrderExporter extends Exporter
{
    protected static ?string $model = Order::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('order_number'),
            ExportColumn::make('shipping_full_name')
                ->label(__('table.customer_name'))
                ->state(fn (Order $record): string => $record->shipping_full_name), // Explicitly retrieve the value
            ExportColumn::make('discount_amount')
                ->label(__('table.discount'))
                ->prefix('$'),
            ExportColumn::make('shipping_cost')
                ->prefix('$')
                ->label(__('table.shipping_cost')),
            ExportColumn::make('total')
                ->prefix('$')
                ->label(__('table.total')),
            ExportColumn::make('shipping_method')
                ->label(__('table.shipping_method')),
            ExportColumn::make('transaction_id')
                ->label(__('table.transaction_id')),
            ExportColumn::make('tracking_number'),
            ExportColumn::make('created_at')
                ->label(__('table.order_date'))
                ->formatStateUsing(function ($state) {
                    if (empty($state)) {
                        return null;
                    }

                    // Safely parse the date, whether it's a string or object
                    return Carbon::parse($state)->format('Y-m-d H:i');
                }),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your order export has completed and '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
