<?php

namespace App\Http\Controllers\Directory;

use App\Enums\EmploymentStatus;
use App\Http\Controllers\Controller;
use App\Models\AccessoryCheckout;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\HerdSyncRun;
use App\Models\LicenseSeat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only employee directory; employees are synced from HERD.
 */
class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Employee::class);

        $search = $request->string('search')->trim()->toString();
        $status = EmploymentStatus::tryFrom($request->string('status')->toString());
        $departmentId = $request->integer('department_id') ?: null;

        $employees = Employee::query()
            ->with(['department:id,name', 'location:id,name'])
            ->withCount([
                'assets',
                'accessoryCheckouts as accessories_count' => fn (Builder $query) => $query->whereNull('checked_in_at'),
                'licenseSeats as licenses_count' => fn (Builder $query) => $query->whereNull('released_at'),
            ])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = '%'.$search.'%';
                $query->where(fn (Builder $query) => $query
                    ->where('name', 'ilike', $term)
                    ->orWhere('employee_number', 'ilike', $term)
                    ->orWhere('email', 'ilike', $term));
            })
            ->when($status !== null, fn (Builder $query) => $query->where('employment_status', $status))
            ->when($departmentId !== null, fn (Builder $query) => $query->where('department_id', $departmentId))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Employee $employee): array => [
                'id' => $employee->id,
                'name' => $employee->name,
                'employee_number' => $employee->employee_number,
                'job_title' => $employee->job_title,
                'department' => $employee->department?->name,
                'location' => $employee->location?->name,
                'employment_status' => $employee->employment_status->value,
                'assets_count' => (int) $employee->getAttribute('assets_count'),
                'accessories_count' => (int) $employee->getAttribute('accessories_count'),
                'licenses_count' => (int) $employee->getAttribute('licenses_count'),
            ]);

        $lastSync = HerdSyncRun::query()->latest('started_at')->first();

        return Inertia::render('Directory/Index', [
            'lastSync' => $lastSync === null ? null : [
                'status' => $lastSync->status,
                'started_at' => $lastSync->started_at->toIso8601String(),
                'created_count' => $lastSync->created_count,
                'updated_count' => $lastSync->updated_count,
                'offboarding_count' => $lastSync->offboarding_count,
                'error_message' => $lastSync->error_message,
            ],
            'canSync' => $request->user()->isAdmin(),
            'filters' => [
                'search' => $search,
                'status' => $status->value ?? '',
                'department_id' => $departmentId === null ? '' : (string) $departmentId,
            ],
            'employees' => $employees,
            'statuses' => array_map(
                fn (EmploymentStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                EmploymentStatus::cases(),
            ),
        ]);
    }

    public function show(Employee $employee): Response
    {
        $this->authorize('view', $employee);

        $employee->load([
            'department:id,name',
            'location:id,name',
            'manager:id,name,employee_number',
            'assets' => fn ($query) => $query->with('assetModel.category:id,name')->orderBy('asset_tag'),
            'accessoryCheckouts' => fn ($query) => $query
                ->whereNull('checked_in_at')
                ->with('accessory:id,name,model')
                ->latest('checked_out_at'),
            'licenseSeats' => fn ($query) => $query
                ->whereNull('released_at')
                ->with('license:id,name,expires_at')
                ->latest('assigned_at'),
        ]);

        return Inertia::render('Directory/Show', [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'employee_number' => $employee->employee_number,
                'email' => $employee->email,
                'job_title' => $employee->job_title,
                'department' => $employee->department?->name,
                'location' => $employee->location?->name,
                'manager' => $employee->manager === null ? null : [
                    'id' => $employee->manager->id,
                    'name' => $employee->manager->name,
                ],
                'employment_status' => $employee->employment_status->value,
                'status_effective_date' => $employee->status_effective_date?->toDateString(),
                'synced_at' => $employee->synced_at?->toIso8601String(),
            ],
            'assets' => $employee->assets->map(fn (Asset $asset): array => [
                'id' => $asset->id,
                'asset_tag' => $asset->asset_tag,
                'model' => $asset->assetModel?->name,
                'category' => $asset->category_name,
                'serial_number' => $asset->serial_number,
                'status' => $asset->status->value,
                'assigned_at' => $asset->assigned_at?->toIso8601String(),
            ])->all(),
            'accessories' => $employee->accessoryCheckouts->map(fn (AccessoryCheckout $checkout): array => [
                'id' => $checkout->id,
                'accessory_id' => $checkout->accessory_id,
                'name' => $checkout->accessory?->name,
                'model' => $checkout->accessory?->model,
                'qty' => $checkout->qty,
                'checked_out_at' => $checkout->checked_out_at->toIso8601String(),
            ])->all(),
            'licenses' => $employee->licenseSeats->map(fn (LicenseSeat $seat): array => [
                'id' => $seat->id,
                'license_id' => $seat->license_id,
                'name' => $seat->license?->name,
                'expires_at' => $seat->license?->expires_at?->toDateString(),
                'assigned_at' => $seat->assigned_at->toIso8601String(),
            ])->all(),
        ]);
    }
}
