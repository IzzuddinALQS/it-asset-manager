<?php

namespace App\Http\Controllers\Settings;

use App\Enums\EmploymentStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Local application users: which employees may sign in and with which role.
 */
class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/Users', [
            'users' => User::query()
                ->with('employee.department:id,name')
                ->get()
                ->sortBy(fn (User $user): string => $user->employee->name ?? '')
                ->values()
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'role' => $user->role->value,
                    'role_label' => $user->role->label(),
                    'last_login_at' => $user->last_login_at?->toIso8601String(),
                    'employee' => $user->employee === null ? null : [
                        'id' => $user->employee->id,
                        'name' => $user->employee->name,
                        'employee_number' => $user->employee->employee_number,
                        'department' => $user->employee->department?->name,
                    ],
                ])
                ->all(),
            'roles' => array_map(
                fn (Role $role): array => ['value' => $role->value, 'label' => $role->label()],
                Role::cases(),
            ),
            // Loaded only when the add-user dialog opens (partial reload).
            'employees' => Inertia::optional(fn (): array => Employee::query()
                ->active()
                ->whereDoesntHave('user')
                ->with('department:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'employee_number', 'department_id'])
                ->map(fn (Employee $employee): array => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'employee_number' => $employee->employee_number,
                    'department' => $employee->department?->name,
                ])
                ->all()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where(fn ($query) => $query->where('employment_status', EmploymentStatus::Active->value)),
                Rule::unique('users', 'employee_id'),
            ],
            'role' => ['required', Rule::enum(Role::class)],
        ], [
            'employee_id.required' => 'Pilih karyawan.',
            'employee_id.exists' => 'Hanya karyawan aktif yang bisa diberi akses.',
            'employee_id.unique' => 'Karyawan ini sudah punya akses.',
        ]);

        $user = User::query()->create($validated);

        return back()->with('success', "Akses {$user->employee?->name} ditambahkan sebagai {$user->role->label()}.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate(['role' => ['required', Rule::enum(Role::class)]]);

        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak bisa mengubah peran akun sendiri.');
        }

        $user->update($validated);

        return back()->with('success', "Peran {$user->employee?->name} diubah menjadi {$user->role->label()}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak bisa menghapus akses akun sendiri.');
        }

        $user->delete();

        return back()->with('success', "Akses {$user->employee?->name} dicabut.");
    }
}
