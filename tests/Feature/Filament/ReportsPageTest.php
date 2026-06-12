<?php

use App\Filament\Pages\Reports;
use App\Models\User;

it('renders the analytics reports page for authenticated admin users', function (): void {
    $user = User::factory()->create([
        'email' => 'reports-admin@example.com',
    ]);

    $this->actingAs($user)
        ->get(Reports::getUrl())
        ->assertOk();
});

it('localizes the reports navigation label in khmer', function (): void {
    app()->setLocale('km');

    expect(Reports::getNavigationLabel())->toBe('របាយការណ៍')
        ->and(Reports::getNavigationGroup())->toBe('វិភាគ');
});

it('localizes the reports navigation label in english', function (): void {
    app()->setLocale('en');

    expect(Reports::getNavigationLabel())->toBe('Reports')
        ->and(Reports::getNavigationGroup())->toBe('Analytics');
});
