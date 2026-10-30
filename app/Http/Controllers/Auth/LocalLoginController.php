<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LocalLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Sign-in stored in this app's database, so access does not depend on HERD being reachable.
 */
class LocalLoginController extends Controller
{
    public function __invoke(LocalLoginRequest $request): RedirectResponse
    {
        $user = $request->authenticatedUser();

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('dashboard'));
    }
}
