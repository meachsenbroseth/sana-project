<?php

use App\Filament\Resources\ShippingMethods\Pages\CreateShippingMethod;
use App\Filament\Resources\ShippingMethods\Pages\EditShippingMethod;
use App\Filament\Resources\ShippingMethods\Pages\ListShippingMethods;
use App\Filament\Resources\ShippingMethods\ShippingMethodResource;
use App\Models\Province;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

// ---------------------------------------------------------------------------
// Resource registration
// ---------------------------------------------------------------------------

test('shipping method resource registers index, create, and edit pages', function (): void {
    $pages = ShippingMethodResource::getPages();

    expect($pages)
        ->toHaveKey('index')
        ->toHaveKey('create')
        ->toHaveKey('edit');

    expect($pages['index']->getPage())->toBe(ListShippingMethods::class);
    expect($pages['create']->getPage())->toBe(CreateShippingMethod::class);
    expect($pages['edit']->getPage())->toBe(EditShippingMethod::class);
});

// ---------------------------------------------------------------------------
// Pivot sync via page hooks
// ---------------------------------------------------------------------------

test('creating a shipping method with province fees syncs pivot rows', function (): void {
    $user = User::factory()->create(['email' => 'admin@phanna.com']);
    $province1 = Province::factory()->create(['name_en' => 'Phnom Penh', 'code' => 'T-PP']);
    $province2 = Province::factory()->create(['name_en' => 'Siem Reap', 'code' => 'T-SR']);

    $this->actingAs($user);
    Gate::before(fn () => true);
    Gate::before(fn () => true);

    Livewire::test(CreateShippingMethod::class)
        ->fillForm([
            'name' => 'Test Method',
            'status' => 'active',
            'province_fees' => [
                ['province_id' => $province1->id, 'fee' => '2.50'],
                ['province_id' => $province2->id, 'fee' => '4.00'],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $method = ShippingMethod::query()->where('name', 'Test Method')->first();

    expect($method)->not->toBeNull();

    $pivotRows = $method->provinces()->orderBy('name_en')->get();

    expect($pivotRows)->toHaveCount(2)
        ->and((float) $pivotRows->firstWhere('name_en', 'Phnom Penh')->pivot->fee)->toBe(2.50)
        ->and((float) $pivotRows->firstWhere('name_en', 'Siem Reap')->pivot->fee)->toBe(4.00);

    // Assert no Province model was mutated — only pivot rows were created
    expect(Province::query()->count())->toBe(2);
});

test('creating a shipping method with no province fees saves fine', function (): void {
    $user = User::factory()->create(['email' => 'admin@phanna.com']);

    $this->actingAs($user);
    Gate::before(fn () => true);

    Livewire::test(CreateShippingMethod::class)
        ->fillForm([
            'name' => 'No Province Method',
            'note' => 'Courier payment is arranged directly.',
            'requires_direct_arrangement' => true,
            'status' => 'active',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $method = ShippingMethod::query()->where('name', 'No Province Method')->first();

    expect($method)->not->toBeNull()
        ->and($method->note)->toBe('Courier payment is arranged directly.')
        ->and($method->requires_direct_arrangement)->toBeTrue()
        ->and($method->provinces()->count())->toBe(0);
});

test('editing a shipping method saves its note and direct courier arrangement setting', function (): void {
    $user = User::factory()->create(['email' => 'admin@phanna.com']);
    $method = ShippingMethod::factory()->create([
        'note' => null,
        'requires_direct_arrangement' => false,
    ]);

    $this->actingAs($user);
    Gate::before(fn () => true);

    Livewire::test(EditShippingMethod::class, ['record' => $method->getRouteKey()])
        ->fillForm([
            'note' => 'Pay the courier when your delivery arrives.',
            'requires_direct_arrangement' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($method->fresh())
        ->note->toBe('Pay the courier when your delivery arrives.')
        ->requires_direct_arrangement->toBeTrue();
});

test('editing a shipping method loads existing province fees into the repeater', function (): void {
    $user = User::factory()->create(['email' => 'admin@phanna.com']);
    $method = ShippingMethod::factory()->create(['name' => 'Loaded Method']);
    $province = Province::factory()->create(['name_en' => 'Kandal', 'code' => 'T-KD']);

    $method->provinces()->attach($province->id, ['fee' => 6.75]);

    $this->actingAs($user);
    Gate::before(fn () => true);

    $component = Livewire::test(EditShippingMethod::class, ['record' => $method->getRouteKey()]);

    $formState = $component->get('data')['province_fees'] ?? [];

    expect($formState)->toHaveCount(1)
        ->and((int) $formState[array_key_first($formState)]['province_id'])->toBe($province->id)
        ->and((float) $formState[array_key_first($formState)]['fee'])->toBe(6.75);
});

test('editing a shipping method saves updated province fees to the pivot table', function (): void {
    $user = User::factory()->create(['email' => 'admin@phanna.com']);
    $method = ShippingMethod::factory()->create();
    $province1 = Province::factory()->create(['name_en' => 'Battambang', 'code' => 'T-BB']);
    $province2 = Province::factory()->create(['name_en' => 'Kampot', 'code' => 'T-KP']);

    $method->provinces()->attach($province1->id, ['fee' => 1.00]);

    $this->actingAs($user);
    Gate::before(fn () => true);

    Livewire::test(EditShippingMethod::class, ['record' => $method->getRouteKey()])
        ->fillForm([
            'province_fees' => [
                ['province_id' => $province1->id, 'fee' => '9.99'],
                ['province_id' => $province2->id, 'fee' => '3.50'],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $pivotRows = $method->fresh()->provinces()->orderBy('name_en')->get();

    expect($pivotRows)->toHaveCount(2)
        ->and((float) $pivotRows->firstWhere('name_en', 'Battambang')->pivot->fee)->toBe(9.99)
        ->and((float) $pivotRows->firstWhere('name_en', 'Kampot')->pivot->fee)->toBe(3.50);
});

test('removing a province row from the repeater detaches it from the pivot table', function (): void {
    $user = User::factory()->create(['email' => 'admin@phanna.com']);
    $method = ShippingMethod::factory()->create();
    $province1 = Province::factory()->create(['code' => 'T-R1']);
    $province2 = Province::factory()->create(['code' => 'T-R2']);

    $method->provinces()->attach($province1->id, ['fee' => 2.00]);
    $method->provinces()->attach($province2->id, ['fee' => 3.00]);

    $this->actingAs($user);
    Gate::before(fn () => true);

    // Save with only province1 — province2 should be synced away
    Livewire::test(EditShippingMethod::class, ['record' => $method->getRouteKey()])
        ->fillForm([
            'province_fees' => [
                ['province_id' => $province1->id, 'fee' => '2.00'],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $remaining = $method->fresh()->provinces()->get();

    expect($remaining)->toHaveCount(1)
        ->and($remaining->first()->id)->toBe($province1->id);
});

test('submitting duplicate provinces in the repeater returns a validation error', function (): void {
    $user = User::factory()->create(['email' => 'admin@phanna.com']);
    $method = ShippingMethod::factory()->create();
    $province = Province::factory()->create(['name_en' => 'Kratié', 'code' => 'T-KR']);

    $this->actingAs($user);
    Gate::before(fn () => true);

    Livewire::test(EditShippingMethod::class, ['record' => $method->getRouteKey()])
        ->fillForm([
            'province_fees' => [
                ['province_id' => $province->id, 'fee' => '2.00'],
                ['province_id' => $province->id, 'fee' => '5.00'],
            ],
        ])
        ->call('save')
        ->assertHasFormErrors(['province_fees.0.province_id']);

    // No pivot row should have been written
    expect($method->fresh()->provinces()->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Existing province model integrity
// ---------------------------------------------------------------------------

test('province model rows are never created or mutated by saving province fees', function (): void {
    $user = User::factory()->create(['email' => 'admin@phanna.com']);
    $province = Province::factory()->create(['code' => 'T-INT']);

    $beforeCount = Province::query()->count();

    $this->actingAs($user);
    Gate::before(fn () => true);

    Livewire::test(CreateShippingMethod::class)
        ->fillForm([
            'name' => 'Integrity Check',
            'status' => 'active',
            'province_fees' => [
                ['province_id' => $province->id, 'fee' => '1.50'],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Province::query()->count())->toBe($beforeCount);
});

// ---------------------------------------------------------------------------
// Cascade / relationship integrity
// ---------------------------------------------------------------------------

test('deleting a shipping method cascades and removes its province fees', function (): void {
    $method = ShippingMethod::factory()->create();
    $province = Province::factory()->create(['code' => 'T-CAS']);

    $method->provinces()->attach($province->id, ['fee' => 4.00]);

    expect(DB::table('shipping_method_province')->count())->toBe(1);

    $method->delete();

    expect(DB::table('shipping_method_province')->count())->toBe(0);
});

test('provinces count is available on shipping method list query', function (): void {
    $method = ShippingMethod::factory()->create();
    $province = Province::factory()->create(['code' => 'T-CNT']);
    $method->provinces()->attach($province->id, ['fee' => 1.00]);

    $result = ShippingMethod::query()->withCount('provinces')->find($method->id);

    expect($result->provinces_count)->toBe(1);
});
