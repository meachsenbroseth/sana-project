<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            // NOTE: Remove stateless() if this is a standard Blade web application
            $googleUser = Socialite::driver('google')->stateless()->user();

            if (empty($googleUser->email)) {
                return redirect()->route('login')
                    ->withErrors('No email returned from Google account.');
            }

            // Find existing customer by Google ID or matching Email
            $user = Customer::where('google_id', $googleUser->id)
                ->orWhere('email', $googleUser->email)
                ->first();

            if (!$user) {
                $user = Customer::create([
                    'name'      => $googleUser->name,
                    'email'     => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'provider'  => 'google',
                    'avatar'    => $googleUser->avatar,
                    'password'  => bcrypt(Str::random(24)),
                    'role_name' => 'User',
                    'status'    => 'Active',
                    'join_date' => now(),
                ]);
            } else {
                // Link Google account if they previously signed up via standard email/password
                if (!$user->google_id) {
                    $user->update([
                        'google_id' => $googleUser->id,
                        'provider'  => 'google',
                    ]);
                }
            }

            $user->update(['last_login' => now()]);

            // Specify your custom guard if 'web' still points to the default User model
            Auth::guard('customer')->login($user); 

            return redirect()->route('home');

        } catch (\Exception $e) {
            Log::error('Google OAuth Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(), // Added trace for better debugging
            ]);

            return redirect()->route('login')
                ->withErrors('Authentication failed. Please try again.');
        }
    }
}