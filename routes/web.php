<?php

use App\Http\Controllers\Auth\FacebookAuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::homepage')->name('home');
Route::livewire('/about', 'pages::about-page')->name('about');
Route::livewire('/products', 'pages::product-listing')->name('products.index');
Route::livewire('/products/{slug}', 'pages::product-details')->name('products.show');
Route::livewire('/chatbot', 'pages::chatbot')->name('chatbot');
Route::livewire('/cart', 'pages::cart')->name('cart.index');

Route::middleware('auth:customer')->group(function () {

    Route::livewire('/checkout', 'pages::checkout')->name('checkout');
    Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/cancel/{order}', [CheckoutController::class, 'cancel'])->name('checkout.cancel');

    Route::livewire('my-account', 'pages::customer.dashboard')->name('customer.dashboard');
    Route::livewire('/my-account/orders', 'pages::orders')->name('customer.orders');
    Route::livewire('/my-account/orders/{id}', 'pages::customer.order-details')->name('customer.orders.show');
    Route::livewire('/my-account/profile', 'pages::customer.profile')->name('customer.profile');
    // logout
    Route::post('/logout', function () {
        auth('customer')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    })->name('logout');
});

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])
    ->name('google.login');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

Route::get('/auth/facebook', [FacebookAuthController::class, 'redirect'])
    ->name('facebook.login');

Route::get('/auth/facebook/callback', [FacebookAuthController::class, 'callback']);

// Route::view('dashboard', 'dashboard')
//     ->middleware(['auth', 'verified'])
//     ->name('dashboard');

require __DIR__.'/settings.php';
