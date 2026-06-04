<?php

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Route::middleware('web')
        ->get('/__test-locale', fn () => response(app()->getLocale()))
        ->name('test.locale');
});

it('stores the selected locale in session', function () {
    $this->get(route('locale.switch', ['locale' => 'km']))
        ->assertRedirect()
        ->assertSessionHas('locale', 'km');
});

it('applies the session locale via middleware', function () {
    $this->withSession(['locale' => 'km'])
        ->get('/__test-locale')
        ->assertOk()
        ->assertSee('km');
});

it('defaults to config locale when no session locale exists', function () {
    config(['app.locale' => 'km']);

    $this->get('/__test-locale')
        ->assertOk()
        ->assertSee('km');
});

it('only allows en and km locales', function () {
    $this->get('/language/fr')->assertNotFound();
});
