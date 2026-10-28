<?php

namespace App\Http\Controllers\Licenses;

use App\Actions\Licenses\SaveLicense;
use App\Actions\Licenses\SyncLicenseAlerts;
use App\Enums\AssetStatus;
use App\Enums\CategoryType;
use App\Enums\LicenseType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Licenses\SaveLicenseRequest;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Employee;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\Manufacturer;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LicenseController extends Controller
{
    private const array STATUS_FILTERS = ['expiring', 'expired', 'full'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', License::class);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $status = in_array($status, self::STATUS_FILTERS, true) ? $status : '';
        $warningDays = SyncLicenseAlerts::EXPIRY_WARNING_DAYS;

        $licenses = License::query()
            ->with(['manufacturer:id,name', 'category:id,name'])
            ->withCount(['seats as seats_used' => fn (Builder $query) => $query->whereNull('released_at')])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = '%'.$search.'%';
                $query->where(fn (Builder $query) => $query
                    ->where('name', 'ilike', $term)
                    ->orWhereHas('manufacturer', fn (Builder $query) => $query->where('name', 'ilike', $term)));
            })
            ->when($status === 'expiring', fn (Builder $query) => $query->expiringSoon($warningDays))
            ->when($status === 'expired', fn (Builder $query) => $query->whereDate('expires_at', '<', now()->toDateString()))
            ->when($status === 'full', fn (Builder $query) => $query->whereRaw(
                'seats_total <= (select count(*) from license_seats where license_seats.license_id = licenses.id and released_at is null)',
            ))
            ->orderByRaw('expires_at is null, expires_at')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (License $license): array => [
                'id' => $license->id,
                'name' => $license->name,
                'manufacturer' => $license->manufacturer?->name,
                'category' => $license->category?->name,
                'license_type' => $license->license_type->value,
                'license_type_label' => $license->license_type->label(),
                'seats_total' => $license->seats_total,
                'seats_used' => (int) $license->getAttribute('seats_used'),
                'annual_cost' => $license->annual_cost,
                'expires_at' => $license->expires_at?->toDateString(),
            ]);

        return Inertia::render('Licenses/Index', [
            'filters' => ['search' => $search, 'status' => $status],
            'licenses' => $licenses,
            'summary' => [
                'count' => License::query()->count(),
                'annual_cost' => (string) License::query()->sum('annual_cost'),
                'expiring' => License::query()->expiringSoon($warningDays)->count(),
                'seats_total' => (int) License::query()->sum('seats_total'),
                'seats_used' => LicenseSeat::query()->whereNull('released_at')->whereHas('license')->count(),
            ],
            'warningDays' => $warningDays,
            'can' => ['create' => $request->user()?->can('create', License::class) ?? false],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', License::class);

        return Inertia::render('Licenses/Form', [...$this->formOptions(), 'license' => null]);
    }

    public function store(SaveLicenseRequest $request, SaveLicense $saveLicense): RedirectResponse
    {
        $license = $saveLicense->handle(new License, $request->validated());

        return redirect()
            ->route('licenses.show', $license)
            ->with('success', "Lisensi {$license->name} ditambahkan.");
    }

    public function show(Request $request, License $license): Response
    {
        $this->authorize('view', $license);

        $license->load([
            'manufacturer:id,name',
            'category:id,name',
            'supplier:id,name',
            'seats' => fn ($query) => $query
                ->with(['employee.department:id,name', 'asset.assetModel:id,name'])
                ->orderByRaw('released_at is not null')
                ->latest('assigned_at')
                ->latest('id')
                ->limit(200),
        ]);

        $user = $request->user();

        return Inertia::render('Licenses/Show', [
            'license' => [
                'id' => $license->id,
                'name' => $license->name,
                'manufacturer' => $license->manufacturer?->name,
                'category' => $license->category?->name,
                'supplier' => $license->supplier?->name,
                'license_type' => $license->license_type->value,
                'license_type_label' => $license->license_type->label(),
                'product_key_masked' => $license->maskedProductKey(),
                'seats_total' => $license->seats_total,
                'seats_used' => $license->seatsUsed(),
                'purchase_order' => $license->purchase_order,
                'purchase_date' => $license->purchase_date?->toDateString(),
                'annual_cost' => $license->annual_cost,
                'expires_at' => $license->expires_at?->toDateString(),
                'notes' => $license->notes,
                'seats' => $license->seats->map(fn (LicenseSeat $seat): array => [
                    'id' => $seat->id,
                    'holder_type' => $seat->employee_id !== null ? 'employee' : 'asset',
                    'employee' => $seat->employee === null ? null : [
                        'id' => $seat->employee->id,
                        'name' => $seat->employee->name,
                        'employee_number' => $seat->employee->employee_number,
                        'department' => $seat->employee->department?->name,
                    ],
                    'asset' => $seat->asset === null ? null : [
                        'id' => $seat->asset->id,
                        'asset_tag' => $seat->asset->asset_tag,
                        'model' => $seat->asset->assetModel?->name,
                    ],
                    'assigned_at' => $seat->assigned_at->toIso8601String(),
                    'released_at' => $seat->released_at?->toIso8601String(),
                ])->all(),
            ],
            'warningDays' => SyncLicenseAlerts::EXPIRY_WARNING_DAYS,
            'can' => [
                'update' => $user?->can('update', $license) ?? false,
                'assignSeat' => $user?->can('assignSeat', $license) ?? false,
            ],
            // Both lists load only when the assign dialog opens (partial reload).
            'employees' => Inertia::optional(fn (): array => Employee::query()
                ->active()
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
            'assets' => Inertia::optional(fn (): array => Asset::query()
                ->whereNot('status', AssetStatus::Retired)
                ->with('assetModel:id,name')
                ->orderBy('asset_tag')
                ->get(['id', 'asset_tag', 'asset_model_id'])
                ->map(fn (Asset $asset): array => [
                    'id' => $asset->id,
                    'asset_tag' => $asset->asset_tag,
                    'model' => $asset->assetModel?->name,
                ])
                ->all()),
        ]);
    }

    public function edit(License $license): Response
    {
        $this->authorize('update', $license);

        return Inertia::render('Licenses/Form', [
            ...$this->formOptions(),
            'license' => [
                'id' => $license->id,
                'name' => $license->name,
                'manufacturer_id' => $license->manufacturer_id,
                'category_id' => $license->category_id,
                'license_type' => $license->license_type->value,
                'product_key_masked' => $license->maskedProductKey(),
                'seats_total' => $license->seats_total,
                'seats_used' => $license->seatsUsed(),
                'supplier_id' => $license->supplier_id,
                'purchase_order' => $license->purchase_order,
                'purchase_date' => $license->purchase_date?->toDateString(),
                'annual_cost' => $license->annual_cost === null ? null : (int) round((float) $license->annual_cost),
                'expires_at' => $license->expires_at?->toDateString(),
                'notes' => $license->notes,
            ],
        ]);
    }

    public function update(SaveLicenseRequest $request, License $license, SaveLicense $saveLicense): RedirectResponse
    {
        $saveLicense->handle($license, $request->validated());

        return redirect()
            ->route('licenses.show', $license)
            ->with('success', "Lisensi {$license->name} diperbarui.");
    }

    /**
     * @return array{categories: array<int, array{id: int, name: string}>, manufacturers: array<int, array{id: int, name: string}>, suppliers: array<int, array{id: int, name: string}>, licenseTypes: array<int, array{value: string, label: string}>}
     */
    private function formOptions(): array
    {
        return [
            'categories' => Category::query()
                ->where('type', CategoryType::License)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Category $category): array => ['id' => $category->id, 'name' => $category->name])
                ->all(),
            'manufacturers' => Manufacturer::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Manufacturer $manufacturer): array => ['id' => $manufacturer->id, 'name' => $manufacturer->name])
                ->all(),
            'suppliers' => Supplier::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Supplier $supplier): array => ['id' => $supplier->id, 'name' => $supplier->name])
                ->all(),
            'licenseTypes' => array_map(
                fn (LicenseType $type): array => ['value' => $type->value, 'label' => $type->label()],
                LicenseType::cases(),
            ),
        ];
    }
}
