<?php

namespace App\Http\Controllers\Settings;

use App\Enums\EmploymentStatus;
use App\Enums\Role;
use App\Exceptions\HerdException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Herd\HerdApiClient;
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
    private const int HERD_PER_PAGE = 15;

    public function index(Request $request, HerdApiClient $herd): Response
    {
        $filters = [
            'herd_search' => trim((string) $request->query('herd_search', '')),
            'herd_page' => max(1, (int) $request->query('herd_page', 1)),
        ];

        return Inertia::render('Settings/Users', [
            'herdFilters' => $filters,
            'herdUsers' => Inertia::defer(fn (): array => $this->herdUsers($herd, $filters['herd_search'], $filters['herd_page'])),
            'users' => User::query()
                ->with('employee.department:id,name')
                ->get()
                ->sortBy(fn (User $user): string => $user->employee->name ?? '')
                ->values()
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'role' => $user->role->value,
                    'role_label' => $user->role->label(),
                    'has_password' => $user->hasLocalPassword(),
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
        ]);
    }

    /**
     * Employees granted SSO access in HERD, marked with the local role they hold here (if any).
     *
     * @return array{configured: bool, error: ?string, data: list<array<string, mixed>>, meta: ?array{total: int, page: int, perPage: int, lastPage: int}}
     */
    private function herdUsers(HerdApiClient $herd, string $search, int $page): array
    {
        if (! $herd->isConfigured()) {
            return ['configured' => false, 'error' => null, 'data' => [], 'meta' => null];
        }

        try {
            $result = $herd->applicationUsers($search, $page, self::HERD_PER_PAGE);
        } catch (HerdException $exception) {
            return ['configured' => true, 'error' => $exception->getMessage(), 'data' => [], 'meta' => null];
        }

        $localRoles = User::query()
            ->join('employees', 'employees.id', '=', 'users.employee_id')
            ->whereIn('employees.employee_number', array_column($result['data'], 'employeeNik'))
            ->pluck('users.role', 'employees.employee_number');

        return [
            'configured' => true,
            'error' => null,
            'data' => array_map(fn (array $user): array => [
                'nik' => $user['employeeNik'],
                'name' => $user['name'],
                'email' => $user['upn'] ?? $user['email'] ?? null,
                'department' => $user['department'] ?? null,
                'job_title' => $user['jobTitle'] ?? null,
                'nik_functional' => $user['nikFunctional'] ?? null,
                'is_active' => (bool) ($user['isActive'] ?? false),
                'granted_at' => $user['grantedAt'] ?? null,
                'local_role_label' => $localRoles->get($user['employeeNik'])?->label(),
            ], $result['data']),
            'meta' => $result['meta'],
        ];
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
