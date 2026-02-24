<?php

namespace App\Filament\Exports;

use App\Models\Order;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;
use Illuminate\Support\Carbon;

class OrderExporter extends Exporter
{
    protected static ?string $model = Order::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('order_number'),
            ExportColumn::make('shipping_full_name')
                ->label('Customer Name')
                ->state(fn(Order $record): string => $record->shipping_full_name), // Explicitly retrieve the value
            ExportColumn::make('discount_amount')
                ->label('Discount')
                ->prefix('$'),
            ExportColumn::make('shipping_cost')
                ->prefix('$')
                ->label('Shipping Cost'),
            ExportColumn::make('total')
                ->prefix('$')
                ->label('Total'),
            ExportColumn::make('shipping_method')
                ->label('Shipping Method'),
            ExportColumn::make('transaction_id')
                ->label('Transaction ID'),
            ExportColumn::make('tracking_number'),
            ExportColumn::make('created_at')
                ->label('Order Date')
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
        $body = 'Your order export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
