<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AuthenticateUser;
use App\Actions\Auth\LogoutUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @throws ValidationException
     */
    public function store(LoginRequest $request, AuthenticateUser $authenticateUser): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        try {
            $user = $authenticateUser->execute(
                email: $request->string('email'),
                password: $request->string('password'),
                remember: $request->boolean('remember'),
                request: $request
            );

            $request->clearRateLimiter();

            if ($user->isPlatformAdmin()) {
                return redirect()->intended('/admin');
            }

            return redirect()->intended('/');
        } catch (ValidationException $e) {
            $request->hitRateLimiter();

            throw $e;
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request, LogoutUser $logoutUser): RedirectResponse
    {
        $logoutUser->execute($request);

        return redirect()->to('/login');
    }
}
