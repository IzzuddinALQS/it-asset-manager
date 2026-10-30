<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Herd\HerdOidcClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DevLoginController extends Controller
{
    public function index(Request $request, HerdOidcClient $oidc): Response|RedirectResponse
    {
        if ($request->user() !== null) {
            return redirect()->route('dashboard');
        }

        $users = ! app()->environment(['local', 'testing']) ? [] : User::query()
            ->with('employee.department', 'employee.location')
            ->orderBy('role')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'name' => $user->employee->name,
                'employee_number' => $user->employee->employee_number,
                'job_title' => $user->employee->job_title,
                'department' => $user->employee->department?->name,
            ]);

        return Inertia::render('Auth/DevLogin', [
            'users' => $users,
            'ssoEnabled' => $oidc->isConfigured(),
        ]);
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        Auth::login($user);
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
