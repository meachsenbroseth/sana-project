<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('auth:customer')->group(function(){
    //logout
    Route::post('/logout', function(){
        auth('customer')->logout();
        request()->session()->logout();
        request()->session()->regenerateToken();
        return redirect('/');
    })->name('logout');

});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
