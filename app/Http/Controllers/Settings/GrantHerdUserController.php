<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Herd\SyncEmployeeFromHerd;
use App\Enums\Role;
use App\Exceptions\HerdException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Herd\HerdApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Gives a HERD-granted employee a local role, creating the employee record from HERD when needed.
 */
class GrantHerdUserController extends Controller
{
    public function __invoke(Request $request, HerdApiClient $herd, SyncEmployeeFromHerd $syncEmployee): RedirectResponse
    {
        $validated = $request->validate([
            'nik' => ['required', 'string', 'max:64'],
            'role' => ['required', Rule::enum(Role::class)],
        ]);

        try {
            $grantedUser = $herd->grantedUserByNik($validated['nik']);
        } catch (HerdException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        if ($grantedUser === null) {
            return back()->with('error', "NIK {$validated['nik']} tidak terdaftar sebagai pengguna IT Asset Management di HERD.");
        }

        $employee = $syncEmployee->handle(SyncEmployeeFromHerd::fromGrantedUser($grantedUser));

        if ($employee->user !== null) {
            return back()->with('error', "{$employee->name} sudah punya akses sebagai {$employee->user->role->label()}.");
        }

        $user = User::query()->create(['employee_id' => $employee->id, 'role' => $validated['role']]);

        return back()->with('success', "Akses {$employee->name} ditambahkan sebagai {$user->role->label()}.");
    }
}
