<?php

use Illuminate\Support\Facades\Route;


Route::livewire('/', 'pages::homepage')->name('home');
Route::livewire('/products', 'pages::product-listing')->name('products.index');
Route::livewire('/products/{slug}', 'pages::product-details')->name('products.show');


Route::middleware('auth:customer')->group(function () {

    Route::livewire('my-account', 'pages::customer.dashboard')->name('customer.dashboard');
    Route::livewire('/my-account/orders', 'pages::orders')->name('customer.orders');
    Route::livewire('/my-account/orders/{id}', 'pages::customer.order-details')->name('customer.orders.show');
    Route::livewire('/my-account/profile', 'pages::customer.profile')->name('customer.profile');
    //logout
    Route::post('/logout', function () {
        auth('customer')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__ . '/settings.php';
