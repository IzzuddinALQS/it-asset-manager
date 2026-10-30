<?php

namespace App\Http\Middleware;

use App\Models\Alert;
use App\Models\Asset;
use App\Models\Component;
use App\Models\Employee;
use App\Models\License;
use App\Models\Maintenance;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $user?->loadMissing('employee.department', 'employee.location');

        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'auth' => [
                'user' => $user === null ? null : [
                    'id' => $user->id,
                    'role' => $user->role,
                    'role_label' => $user->role->label(),
                    'employee' => $user->employee === null ? null : [
                        'id' => $user->employee->id,
                        'herd_id' => $user->employee->herd_id,
                        'employee_number' => $user->employee->employee_number,
                        'name' => $user->employee->name,
                        'email' => $user->employee->email,
                        'job_title' => $user->employee->job_title,
                        'department' => $user->employee->department === null ? null : [
                            'id' => $user->employee->department->id,
                            'name' => $user->employee->department->name,
                        ],
                        'location' => $user->employee->location === null ? null : [
                            'id' => $user->employee->location->id,
                            'name' => $user->employee->location->name,
                        ],
                    ],
                ],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'alerts' => [
                'open' => fn () => $user === null ? 0 : Alert::query()->open()->count(),
            ],
            'permissions' => fn () => $user === null ? [] : [
                'manage' => $user->role->canManage(),
                'operate' => $user->role->canOperate(),
            ],
            'navigation' => fn () => $user === null ? [] : $this->navigation($user),
        ];
    }

    /**
     * Sidebar sections visible to the user; items use the same checks as their routes.
     *
     * @return list<array{group: string, items: list<array{label: string, href: string, icon: string}>}>
     */
    private function navigation(User $user): array
    {
        $sections = [
            'Utama' => [
                ['Dashboard', 'dashboard', 'dashboard', true],
                ['Inventaris', 'assets.index', 'assets', $user->can('viewAny', Asset::class)],
                ['Lisensi', 'licenses.index', 'licenses', $user->can('viewAny', License::class)],
                ['Maintenance', 'maintenance.index', 'maintenance', $user->can('viewAny', Maintenance::class)],
                ['Komponen & Aksesori', 'inventory.index', 'inventory', $user->can('viewAny', Component::class)],
            ],
            'Sistem' => [
                ['Direktori Karyawan', 'directory.index', 'directory', $user->can('viewAny', Employee::class)],
                ['Laporan', 'reports.index', 'reports', true],
                ['Peringatan', 'alerts.index', 'alerts', $user->can('viewAny', Alert::class)],
                ['Master Data', 'settings.index', 'settings', $user->role->canManage()],
            ],
        ];

        $navigation = [];

        foreach ($sections as $group => $items) {
            $visible = array_values(array_map(
                fn (array $item): array => [
                    'label' => $item[0],
                    'href' => route($item[1], absolute: false),
                    'icon' => $item[2],
                ],
                array_filter($items, fn (array $item): bool => $item[3]),
            ));

            if ($visible !== []) {
                $navigation[] = ['group' => $group, 'items' => $visible];
            }
        }

        return $navigation;
    }
}
