<?php

use App\Providers\Filament\AdminPanelProvider;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Tests\TestCase;

uses(TestCase::class);

it('evaluates admin navigation group labels using the current locale', function () {
    app()->setLocale('en');

    $panelProvider = new AdminPanelProvider(app());
    $panel = $panelProvider->panel(app(Panel::class));

    $groups = array_values($panel->getNavigationGroups());

    expect($groups)->each->toBeInstanceOf(NavigationGroup::class);

    $expectedEnglishLabels = [
        __('nav.analytics'),
        __('nav.catalog'),
        __('nav.sales'),
        __('nav.customer_management'),
        __('nav.system_management'),
        __('nav.settings'),
    ];

    expect(array_map(fn (NavigationGroup $group): ?string => $group->getLabel(), $groups))
        ->toBe($expectedEnglishLabels);

    app()->setLocale('km');

    $expectedKhmerLabels = [
        __('nav.analytics'),
        __('nav.catalog'),
        __('nav.sales'),
        __('nav.customer_management'),
        __('nav.system_management'),
        __('nav.settings'),
    ];

    expect(array_map(fn (NavigationGroup $group): ?string => $group->getLabel(), $groups))
        ->toBe($expectedKhmerLabels);
});
