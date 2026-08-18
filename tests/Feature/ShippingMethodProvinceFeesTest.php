<?php

use App\Models\District;
use App\Models\Province;
use App\Models\ShippingMethod;
use App\Services\ShippingFeeService;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;

// ---------------------------------------------------------------------------
// Province model
// ---------------------------------------------------------------------------

test('province has many districts', function (): void {
    $province = Province::factory()->create();

    expect($province->districts())->toBeInstanceOf(HasMany::class);
});

test('province belongs to many shipping methods', function (): void {
    $province = Province::factory()->create();

    expect($province->shippingMethods())->toBeInstanceOf(BelongsToMany::class);
});

test('province active scope filters inactive provinces', function (): void {
    Province::factory()->create(['is_active' => true]);
    Province::factory()->inactive()->create();

    expect(Province::query()->active()->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// District model
// ---------------------------------------------------------------------------

test('district belongs to province', function (): void {
    $district = District::factory()->create();

    expect($district->province()->getRelated())->toBeInstanceOf(Province::class);
});

test('district active scope filters inactive districts', function (): void {
    $province = Province::factory()->create();
    District::factory()->create(['province_id' => $province->id, 'is_active' => true]);
    District::factory()->inactive()->create(['province_id' => $province->id]);

    expect(District::query()->active()->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// ShippingMethod → Province pivot
// ---------------------------------------------------------------------------

test('shipping method can attach a province with a fee', function (): void {
    $method = ShippingMethod::factory()->create();
    $province = Province::factory()->create();

    $method->provinces()->attach($province->id, ['fee' => 5.50]);

    $pivot = $method->provinces()->first()?->pivot;

    expect($pivot)->not->toBeNull()
        ->and((float) $pivot->fee)->toBe(5.50);
});

test('shipping method unique province constraint prevents duplicates', function (): void {
    $method = ShippingMethod::factory()->create();
    $province = Province::factory()->create();

    $method->provinces()->attach($province->id, ['fee' => 2.00]);

    expect(fn () => $method->provinces()->attach($province->id, ['fee' => 3.00]))
        ->toThrow(QueryException::class);
});

test('shipping method provinces count relationship returns correct count', function (): void {
    $method = ShippingMethod::factory()->create();
    $provinces = Province::factory()->count(3)->create();

    foreach ($provinces as $province) {
        $method->provinces()->attach($province->id, ['fee' => 1.00]);
    }

    $fresh = ShippingMethod::query()->withCount('provinces')->find($method->id);

    expect($fresh->provinces_count)->toBe(3);
});

// ---------------------------------------------------------------------------
// ShippingFeeService
// ---------------------------------------------------------------------------

test('ShippingFeeService::feeFor returns the correct pivot fee', function (): void {
    $method = ShippingMethod::factory()->create();
    $province = Province::factory()->create();

    $method->provinces()->attach($province->id, ['fee' => 3.75]);

    $fee = app(ShippingFeeService::class)->feeFor($method, $province);

    expect($fee)->toBe(3.75);
});

test('ShippingFeeService::feeFor returns null when province not covered', function (): void {
    $method = ShippingMethod::factory()->create();
    $uncovered = Province::factory()->create();

    $fee = app(ShippingFeeService::class)->feeFor($method, $uncovered);

    expect($fee)->toBeNull();
});

test('ShippingFeeService::feeFor returns zero for direct courier arrangement without a province fee', function (): void {
    $method = ShippingMethod::factory()->create([
        'requires_direct_arrangement' => true,
    ]);
    $province = Province::factory()->create();

    $fee = app(ShippingFeeService::class)->feeFor($method, $province);

    expect($method->provinces()->count())->toBe(0)
        ->and($fee)->toBe(0.0);
});
