<?php

namespace App\Http\Middleware;

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
        ];
    }
}
