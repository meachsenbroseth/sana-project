<?php

use App\Models\Customer;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

beforeEach(function () {
    config([
        'app.url' => 'http://localhost',
        'fortify.features' => [Features::resetPasswords()],
    ]);
});

test('reset password link screen can be rendered', function () {
    $response = $this->get('http://localhost/forgot-password');

    $response->assertOk();
});

test('reset password link can be requested', function () {
    Notification::fake();

    $customer = Customer::create([
        'name' => 'Test Customer',
        'email' => 'customer@example.com',
        'password' => 'password',
        'is_active' => true,
    ]);

    $this->post('http://localhost/forgot-password', ['email' => $customer->email]);

    Notification::assertSentTo($customer, ResetPassword::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $customer = Customer::create([
        'name' => 'Reset Screen Customer',
        'email' => 'reset-screen@example.com',
        'password' => 'password',
        'is_active' => true,
    ]);

    $this->post('http://localhost/forgot-password', ['email' => $customer->email]);

    Notification::assertSentTo($customer, ResetPassword::class, function ($notification) {
        $response = $this->get('http://localhost/reset-password/'.$notification->token);

        $response->assertOk();

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $customer = Customer::create([
        'name' => 'Resettable Customer',
        'email' => 'resettable@example.com',
        'password' => 'password',
        'is_active' => true,
    ]);

    $this->post('http://localhost/forgot-password', ['email' => $customer->email]);

    Notification::assertSentTo($customer, ResetPassword::class, function ($notification) use ($customer) {
        $response = $this->post('http://localhost/reset-password', [
            'token' => $notification->token,
            'email' => $customer->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login', absolute: false));

        expect(Hash::check('new-password', $customer->fresh()->password))->toBeTrue();

        return true;
    });
});
