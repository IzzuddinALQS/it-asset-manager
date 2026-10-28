<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The signed-in user's own account; personal data comes from HERD and is read-only.
 */
class ProfileController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $employee = $user->employee?->loadCount([
            'assets',
            'accessoryCheckouts as accessories_count' => fn (Builder $query) => $query->whereNull('checked_in_at'),
            'licenseSeats as licenses_count' => fn (Builder $query) => $query->whereNull('released_at'),
        ]);

        return Inertia::render('Settings/Profile', [
            'profile' => [
                'role_label' => $user->role->label(),
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'employee_id' => $employee?->id,
                'job_title' => $employee?->job_title,
                'synced_at' => $employee?->synced_at?->toIso8601String(),
                'holdings' => [
                    'assets' => (int) $employee?->getAttribute('assets_count'),
                    'accessories' => (int) $employee?->getAttribute('accessories_count'),
                    'licenses' => (int) $employee?->getAttribute('licenses_count'),
                ],
            ],
        ]);
    }
}
