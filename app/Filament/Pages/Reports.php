<?php

namespace App\Filament\Pages;

use App\Filament\Exports\AnalyticsOrderReportExporter;
use App\Filament\Widgets\Reports\AnalyticsInsightsWidget;
use App\Filament\Widgets\Reports\AnalyticsStatsOverview;
use App\Filament\Widgets\Reports\CustomerGrowthChart;
use App\Filament\Widgets\Reports\CustomerReportTable;
use App\Filament\Widgets\Reports\OrderReportTable;
use App\Filament\Widgets\Reports\OrdersChart;
use App\Filament\Widgets\Reports\ProductPerformanceChart;
use App\Filament\Widgets\Reports\SalesRevenueChart;
use App\Filament\Widgets\Reports\TopSellingProductsTable;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Services\Analytics\AnalyticsFilters;
use App\Services\Analytics\AnalyticsService;
use App\Services\Analytics\AnalyticsTableResolver;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Reports extends BaseDashboard
{
    use HasFiltersForm;

    protected static string $routePath = 'reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 1;

    public function mount(): void
    {
        $this->mountHasFilters();

        $this->filters ??= [
            'date_preset' => AnalyticsFilters::PRESET_THIS_MONTH,
        ];
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('nav.analytics');
    }

    public static function getNavigationLabel(): string
    {
        return __('analytics.reports');
    }

    public function getTitle(): string
    {
        return __('analytics.page_title');
    }

    /**
     * @return int|array<string, int|null>
     */
    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 2,
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('analytics.filters.heading'))
                    ->schema([
                        Select::make('date_preset')
                            ->label(__('analytics.filters.date_preset'))
                            ->options([
                                AnalyticsFilters::PRESET_TODAY => __('analytics.filters.presets.today'),
                                AnalyticsFilters::PRESET_YESTERDAY => __('analytics.filters.presets.yesterday'),
                                AnalyticsFilters::PRESET_THIS_WEEK => __('analytics.filters.presets.this_week'),
                                AnalyticsFilters::PRESET_THIS_MONTH => __('analytics.filters.presets.this_month'),
                                AnalyticsFilters::PRESET_THIS_YEAR => __('analytics.filters.presets.this_year'),
                                AnalyticsFilters::PRESET_CUSTOM => __('analytics.filters.presets.custom'),
                            ])
                            ->default(AnalyticsFilters::PRESET_THIS_MONTH)
                            ->live()
                            ->required(),
                        DatePicker::make('start_date')
                            ->label(__('analytics.filters.start_date'))
                            ->visible(fn (callable $get): bool => $get('date_preset') === AnalyticsFilters::PRESET_CUSTOM),
                        DatePicker::make('end_date')
                            ->label(__('analytics.filters.end_date'))
                            ->visible(fn (callable $get): bool => $get('date_preset') === AnalyticsFilters::PRESET_CUSTOM),
                        Select::make('product_id')
                            ->label(__('analytics.filters.product'))
                            ->options(function (): array {
                                $tables = app(AnalyticsTableResolver::class);

                                return $tables->hasProducts()
                                    ? Product::query()->orderBy('name')->pluck('name', 'id')->all()
                                    : [];
                            })
                            ->visible(fn (): bool => app(AnalyticsTableResolver::class)->hasProducts())
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Select::make('category_id')
                            ->label(__('analytics.filters.category'))
                            ->options(function (): array {
                                $tables = app(AnalyticsTableResolver::class);

                                return $tables->hasCategories()
                                    ? Category::query()->orderBy('name')->pluck('name', 'id')->all()
                                    : [];
                            })
                            ->visible(fn (): bool => app(AnalyticsTableResolver::class)->hasCategories())
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Select::make('customer_id')
                            ->label(__('analytics.filters.customer'))
                            ->options(function (): array {
                                $tables = app(AnalyticsTableResolver::class);

                                return $tables->hasCustomers()
                                    ? Customer::query()->orderBy('name')->pluck('name', 'id')->all()
                                    : [];
                            })
                            ->visible(fn (): bool => app(AnalyticsTableResolver::class)->hasCustomers())
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Select::make('order_status')
                            ->label(__('analytics.filters.order_status'))
                            ->options(__('order.status'))
                            ->nullable(),
                        Select::make('payment_method')
                            ->label(__('analytics.filters.payment_method'))
                            ->options(__('analytics.payment_methods'))
                            ->nullable(),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            AnalyticsStatsOverview::class,
            AnalyticsInsightsWidget::class,
            SalesRevenueChart::class,
            OrdersChart::class,
            CustomerGrowthChart::class,
            ProductPerformanceChart::class,
            TopSellingProductsTable::class,
            CustomerReportTable::class,
            OrderReportTable::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label(__('analytics.export.pdf'))
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->action(function () {
                    $filters = AnalyticsFilters::fromPageFilters($this->filters);
                    $analytics = app(AnalyticsService::class);

                    $pdf = Pdf::loadView('pdf.analytics-report', [
                        'title' => __('analytics.page_title'),
                        'generatedAt' => now()->format('Y-m-d H:i'),
                        'kpis' => $analytics->kpiMetrics($filters),
                        'insights' => $analytics->insightMetrics($filters),
                        'topProducts' => $analytics->topSellingProductsQuery($filters)->limit(10)->get(),
                    ]);

                    return response()->streamDownload(
                        fn () => print ($pdf->output()),
                        'analytics-report-'.now()->format('Y-m-d').'.pdf',
                    );
                }),
            // ExportAction::make('exportExcel')
            //     ->label(__('analytics.export.excel'))
            //     ->icon(Heroicon::OutlinedTableCells)
            //     ->color('success')
            //     ->exporter(AnalyticsOrderReportExporter::class)
            //     ->formats([ExportFormat::Xlsx])
            //     ->modifyQueryUsing(fn () => app(AnalyticsService::class)->orderReportQuery(
            //         AnalyticsFilters::fromPageFilters($this->filters),
            //     )),
            ExportAction::make('exportCsv')
                ->label(__('analytics.export.csv'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('info')
                ->exporter(AnalyticsOrderReportExporter::class)
                ->formats([ExportFormat::Csv])
                ->modifyQueryUsing(fn () => app(AnalyticsService::class)->orderReportQuery(
                    AnalyticsFilters::fromPageFilters($this->filters),
                )),
        ];
    }
}
