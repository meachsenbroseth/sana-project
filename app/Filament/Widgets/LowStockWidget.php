<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockWidget extends BaseWidget
{
    protected static ?string $heading = 'Low Stock Alert';

    protected static ?int $sort = 4;

    // public static function canView(): bool
    // {
    //     return false;
    // }

    protected int|string|array $columnSpan = 'full';

    // Centralized threshold (easy to maintain later)
    protected const LOW_STOCK_THRESHOLD = 10;
    protected const CRITICAL_STOCK_THRESHOLD = 5;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getQuery())
            ->columns($this->getColumns())
            ->defaultSort('stock_quantity', 'asc')
            ->paginated(false)
            ->striped()
            ->emptyStateHeading('All products are well stocked 🎉')
            ->emptyStateDescription('No products are currently below the stock threshold.');
    }

    protected function getQuery(): Builder
    {
        return Product::query()
            ->where('stock_quantity', '<=', self::LOW_STOCK_THRESHOLD);
    }

    protected function getColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label('Product')
                ->searchable()
                ->limit(30)
                ->tooltip(fn($record) => $record->name),

            Tables\Columns\TextColumn::make('sku')
                ->label('SKU')
                ->searchable()
                ->copyable(),

            Tables\Columns\TextColumn::make('stock_quantity')
                ->label('Stock')
                ->badge()
                ->color(fn(int $state): string => $this->getStockColor($state))
                ->formatStateUsing(fn(int $state) => $state === 0 ? 'OUT' : $state),
        ];
    }

    protected function getStockColor(int $state): string
    {
        return match (true) {
            $state === 0 => 'danger',
            $state <= self::CRITICAL_STOCK_THRESHOLD => 'warning',
            default => 'success',
        };
    }
}
