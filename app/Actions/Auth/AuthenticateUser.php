<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticateUser
{
    /**
     * Authenticate a user with email and password, enforcing account status and session safety.
     *
     * @throws ValidationException
     */
    public function execute(string $email, string $password, bool $remember, Request $request): User
    {
        $normalizedEmail = strtolower(trim($email));

        $user = User::query()->where('email', $normalizedEmail)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (! $user->status->canAuthenticate()) {
            throw ValidationException::withMessages([
                'email' => 'This account is not permitted to log in. Please contact your administrator.',
            ]);
        }

        Auth::login($user, $remember);

        $user->forceFill([
            'last_login_at' => now(),
        ])->saveQuietly();

        $request->session()->regenerate();

        return $user;
    }
}
