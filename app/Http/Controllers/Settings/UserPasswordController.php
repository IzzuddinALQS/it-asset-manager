<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * Sets or clears a user's local password (the fallback when HERD SSO is unavailable).
 */
class UserPasswordController extends Controller
{
    public function __invoke(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)],
        ], [
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        $user->forceFill(['password' => $validated['password'] ?? null])->save();

        return back()->with('success', $user->hasLocalPassword()
            ? "Password lokal {$user->employee?->name} disimpan."
            : "Password lokal {$user->employee?->name} dihapus; pengguna hanya bisa masuk lewat HERD.");
    }
}
