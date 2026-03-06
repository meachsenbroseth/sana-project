<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class FacebookAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('facebook')
            ->scopes(['email', 'public_profile'])
            ->redirect();
    }

    public function callback()
    {
        try {
            $facebookUser = Socialite::driver('facebook')->stateless()->user();

            $email = $facebookUser->email 
                ?? $facebookUser->id . '@facebook-user.local';

            // CHANGED: provider_id to facebook_id
            $user = Customer::where('facebook_id', $facebookUser->id)
                ->orWhere('email', $email)
                ->first();

            if (!$user) {
                $user = Customer::create([
                    'name'        => $facebookUser->name ?? 'Facebook User',
                    'email'       => $email,
                    'facebook_id' => $facebookUser->id, // CHANGED here
                    'provider'    => 'facebook',
                    'avatar'      => $facebookUser->avatar,
                    'password'    => bcrypt(Str::random(24)),
                    'role_name'   => 'User',
                    'status'      => 'Active',
                    'join_date'   => now(),
                ]);
            } else {
                if (!$user->facebook_id) { // CHANGED here
                    $user->update([
                        'facebook_id' => $facebookUser->id, // CHANGED here
                        'provider'    => 'facebook',
                    ]);
                }
            }

            $user->update([
                'last_login' => now(),
            ]);

            Auth::guard('customer')->login($user);

            return redirect()->route('home');

        } catch (\Exception $e) {

            Log::error('Facebook OAuth Error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')
                ->withErrors('Facebook authentication failed. Please try again.');
        }
    }
}