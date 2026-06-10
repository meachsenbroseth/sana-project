<?php

namespace App\Services\Analytics;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class AnalyticsFilters
{
    public const PRESET_TODAY = 'today';

    public const PRESET_YESTERDAY = 'yesterday';

    public const PRESET_THIS_WEEK = 'this_week';

    public const PRESET_THIS_MONTH = 'this_month';

    public const PRESET_THIS_YEAR = 'this_year';

    public const PRESET_CUSTOM = 'custom';

    public function __construct(
        public ?CarbonInterface $startDate = null,
        public ?CarbonInterface $endDate = null,
        public ?int $productId = null,
        public ?int $categoryId = null,
        public ?int $customerId = null,
        public ?string $orderStatus = null,
        public ?string $paymentMethod = null,
        public string $datePreset = self::PRESET_THIS_MONTH,
    ) {}

    /**
     * @param  array<string, mixed>|null  $filters
     */
    public static function fromPageFilters(?array $filters): self
    {
        $filters ??= [];
        $preset = (string) ($filters['date_preset'] ?? self::PRESET_THIS_MONTH);

        [$startDate, $endDate] = self::resolveDateRange(
            $preset,
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null,
        );

        return new self(
            startDate: $startDate,
            endDate: $endDate,
            productId: filled($filters['product_id'] ?? null) ? (int) $filters['product_id'] : null,
            categoryId: filled($filters['category_id'] ?? null) ? (int) $filters['category_id'] : null,
            customerId: filled($filters['customer_id'] ?? null) ? (int) $filters['customer_id'] : null,
            orderStatus: filled($filters['order_status'] ?? null) ? (string) $filters['order_status'] : null,
            paymentMethod: filled($filters['payment_method'] ?? null) ? (string) $filters['payment_method'] : null,
            datePreset: $preset,
        );
    }

    /**
     * @return array{0: ?CarbonInterface, 1: ?CarbonInterface}
     */
    public static function resolveDateRange(string $preset, mixed $startDate, mixed $endDate): array
    {
        return match ($preset) {
            self::PRESET_TODAY => [today()->startOfDay(), today()->endOfDay()],
            self::PRESET_YESTERDAY => [today()->subDay()->startOfDay(), today()->subDay()->endOfDay()],
            self::PRESET_THIS_WEEK => [now()->startOfWeek(), now()->endOfWeek()],
            self::PRESET_THIS_MONTH => [now()->startOfMonth(), now()->endOfMonth()],
            self::PRESET_THIS_YEAR => [now()->startOfYear(), now()->endOfYear()],
            self::PRESET_CUSTOM => [
                filled($startDate) ? Carbon::parse($startDate)->startOfDay() : null,
                filled($endDate) ? Carbon::parse($endDate)->endOfDay() : null,
            ],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    public function hasOrderScopedFilters(): bool
    {
        return $this->productId !== null
            || $this->categoryId !== null
            || $this->orderStatus !== null
            || $this->paymentMethod !== null;
    }

    public function cacheKey(): string
    {
        return md5(json_encode([
            $this->startDate?->toDateTimeString(),
            $this->endDate?->toDateTimeString(),
            $this->productId,
            $this->categoryId,
            $this->customerId,
            $this->orderStatus,
            $this->paymentMethod,
            $this->datePreset,
        ]));
    }
}
