<?php

namespace App\Http\Controllers\Assets;

use App\Actions\Assets\CreateAsset;
use App\Actions\Assets\UpdateAsset;
use App\Enums\AssetStatus;
use App\Enums\CategoryType;
use App\Enums\RetireReason;
use App\Http\Controllers\Concerns\ProvidesMaintenanceOptions;
use App\Http\Controllers\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assets\StoreAssetRequest;
use App\Http\Requests\Assets\UpdateAssetRequest;
use App\Models\Asset;
use App\Models\AssetLog;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\Employee;
use App\Models\LicenseSeat;
use App\Models\Location;
use App\Models\Maintenance;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssetController extends Controller
{
    use ProvidesMaintenanceOptions;
    use ResolvesActor;

    /**
     * @var list<string>
     */
    private const array SORTABLE = ['asset_tag', 'status', 'purchase_date', 'warranty_until', 'created_at'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Asset::class);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $categoryId = $request->integer('category_id');
        $locationId = $request->integer('location_id');
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->lower()->toString() === 'desc' ? 'desc' : 'asc';

        if (! in_array($sort, self::SORTABLE, true)) {
            $sort = 'asset_tag';
        }

        $assets = Asset::query()
            ->with(['assetModel.category', 'assetModel.manufacturer', 'assignedEmployee', 'assignedLocation', 'location'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = '%'.$search.'%';

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('asset_tag', 'ilike', $term)
                        ->orWhere('serial_number', 'ilike', $term)
                        ->orWhereHas('assetModel', fn (Builder $model) => $model->where('name', 'ilike', $term))
                        ->orWhereHas('assignedEmployee', fn (Builder $employee) => $employee->where('name', 'ilike', $term));
                });
            })
            ->when(
                AssetStatus::tryFrom($status) !== null,
                fn (Builder $query) => $query->where('status', AssetStatus::from($status)),
            )
            ->when(
                $categoryId > 0,
                fn (Builder $query) => $query->whereHas('assetModel', fn (Builder $model) => $model->where('category_id', $categoryId)),
            )
            ->when($locationId > 0, fn (Builder $query) => $query->where('location_id', $locationId))
            ->orderBy($sort, $direction)
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Asset $asset): array => [
                'id' => $asset->id,
                'asset_tag' => $asset->asset_tag,
                'serial_number' => $asset->serial_number,
                'status' => $asset->status->value,
                'status_label' => $asset->status->label(),
                'model' => $asset->assetModel?->name,
                'manufacturer' => $asset->assetModel?->manufacturer?->name,
                'category' => $asset->assetModel?->category?->name,
                'holder' => $asset->assignedEmployee->name ?? $asset->assignedLocation?->name,
                'holder_type' => $asset->assignedEmployee !== null
                    ? 'employee'
                    : ($asset->assignedLocation !== null ? 'location' : null),
                'location' => $asset->location?->name,
                'purchase_date' => $asset->purchase_date?->toDateString(),
                'purchase_cost' => $asset->purchase_cost,
                'warranty_until' => $asset->warranty_until?->toDateString(),
            ]);

        $statusCounts = Asset::query()
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('Assets/Index', [
            'assets' => $assets,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'category_id' => $categoryId > 0 ? $categoryId : null,
                'location_id' => $locationId > 0 ? $locationId : null,
                'sort' => $sort,
                'direction' => $direction,
            ],
            'statusOptions' => collect(AssetStatus::cases())
                ->map(fn (AssetStatus $case): array => ['value' => $case->value, 'label' => $case->label()])
                ->all(),
            'statusCounts' => collect(AssetStatus::cases())
                ->mapWithKeys(fn (AssetStatus $case): array => [
                    $case->value => (int) ($statusCounts[$case->value] ?? 0),
                ])
                ->all(),
            'categories' => $this->assetCategories(),
            'locations' => $this->locationOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Asset::class);

        return Inertia::render('Assets/Create', $this->formOptions());
    }

    public function store(StoreAssetRequest $request, CreateAsset $createAsset): RedirectResponse
    {
        $asset = $createAsset->handle($request->validated(), $this->actor($request));

        return redirect()
            ->route('assets.show', $asset)
            ->with('success', "Aset {$asset->asset_tag} berhasil dicatat sebagai menunggu.");
    }

    public function show(Request $request, Asset $asset): Response
    {
        $this->authorize('view', $asset);

        $asset->load([
            'assetModel.category',
            'assetModel.manufacturer',
            'assignedEmployee.department',
            'assignedLocation',
            'location',
            'supplier',
            'creator.employee',
            'logs.performedBy.employee',
            'logs.employee',
            'logs.location',
            'componentInstallations' => fn ($query) => $query->whereNull('removed_at')->with('component.category'),
            'licenseSeats' => fn ($query) => $query->whereNull('released_at')->with('license'),
            'maintenances' => fn ($query) => $query->latest('created_at')->limit(5),
        ]);

        $user = $request->user();
        /** @var Maintenance|null $openMaintenance */
        $openMaintenance = $asset->maintenances()->open()->first(['id', 'ticket_number']);
        $canOpenTicket = $openMaintenance === null
            && in_array($asset->status, [AssetStatus::Ready, AssetStatus::Deployed, AssetStatus::Maintenance], true);

        return Inertia::render('Assets/Show', [
            'asset' => [
                'id' => $asset->id,
                'asset_tag' => $asset->asset_tag,
                'serial_number' => $asset->serial_number,
                'status' => $asset->status->value,
                'status_label' => $asset->status->label(),
                'status_note' => $this->statusNote($asset->status),
                'model' => $asset->assetModel?->name,
                'model_number' => $asset->assetModel?->model_number,
                'category' => $asset->assetModel?->category?->name,
                'manufacturer' => $asset->assetModel?->manufacturer?->name,
                'holder' => $asset->assignedEmployee === null ? null : [
                    'type' => 'employee',
                    'id' => $asset->assignedEmployee->id,
                    'name' => $asset->assignedEmployee->name,
                    'employee_number' => $asset->assignedEmployee->employee_number,
                    'job_title' => $asset->assignedEmployee->job_title,
                    'department' => $asset->assignedEmployee->department?->name,
                ],
                'assigned_location' => $asset->assignedLocation === null ? null : [
                    'type' => 'location',
                    'id' => $asset->assignedLocation->id,
                    'name' => $asset->assignedLocation->name,
                ],
                'assigned_at' => $asset->assigned_at?->toIso8601String(),
                'expected_return_date' => $asset->expected_return_date?->toDateString(),
                'location' => $asset->location?->name,
                'supplier' => $asset->supplier?->name,
                'purchase_order' => $asset->purchase_order,
                'purchase_date' => $asset->purchase_date?->toDateString(),
                'purchase_cost' => $asset->purchase_cost,
                'warranty_until' => $asset->warranty_until?->toDateString(),
                'last_audited_at' => $asset->last_audited_at?->toDateString(),
                'notes' => $asset->notes,
                'retired_at' => $asset->retired_at?->toIso8601String(),
                'retire_reason' => $asset->retire_reason?->value,
                'retire_reason_label' => $asset->retire_reason?->label(),
                'created_at' => $asset->created_at?->toIso8601String(),
                'created_by' => $asset->creator?->employee?->name,
                'logs' => $asset->logs->map(fn (AssetLog $log): array => [
                    'id' => $log->id,
                    'action' => $log->action->value,
                    'action_label' => $log->action->label(),
                    'from_status' => $log->from_status?->value,
                    'from_status_label' => $log->from_status?->label(),
                    'to_status' => $log->to_status?->value,
                    'to_status_label' => $log->to_status?->label(),
                    'note' => $log->note,
                    'employee' => $log->employee?->name,
                    'location' => $log->location?->name,
                    'performed_by' => $log->performedBy?->employee?->name,
                    'created_at' => $log->created_at?->toIso8601String(),
                ])->all(),
                'components' => $asset->componentInstallations->map(fn (ComponentInstallation $installation): array => [
                    'id' => $installation->id,
                    'name' => $installation->component?->name,
                    'spec' => $installation->component?->spec,
                    'category' => $installation->component?->category?->name,
                    'qty' => $installation->qty,
                    'is_factory' => $installation->is_factory,
                    'installed_at' => $installation->installed_at?->toIso8601String(),
                ])->all(),
                'licenses' => $asset->licenseSeats->map(fn (LicenseSeat $seat): array => [
                    'id' => $seat->id,
                    'name' => $seat->license?->name,
                    'assigned_at' => $seat->assigned_at?->toIso8601String(),
                    'expires_at' => $seat->license?->expires_at?->toDateString(),
                ])->all(),
                'maintenances' => $asset->maintenances->map(fn (Maintenance $maintenance): array => [
                    'id' => $maintenance->id,
                    'ticket_number' => $maintenance->ticket_number,
                    'title' => $maintenance->title,
                    'status' => $maintenance->status->value,
                    'status_label' => $maintenance->status->label(),
                    'type_label' => $maintenance->type->label(),
                    'created_at' => $maintenance->created_at?->toIso8601String(),
                ])->all(),
                'open_maintenance' => $openMaintenance === null ? null : [
                    'id' => $openMaintenance->id,
                    'ticket_number' => $openMaintenance->ticket_number,
                ],
            ],
            // Role permission combined with the current lifecycle state.
            'can' => [
                'update' => ($user?->can('update', $asset) ?? false) && ! $asset->isRetired(),
                'tag' => ($user?->can('tag', $asset) ?? false) && $asset->status === AssetStatus::Pending,
                'checkout' => ($user?->can('checkout', $asset) ?? false) && $asset->status === AssetStatus::Ready,
                'checkin' => ($user?->can('checkin', $asset) ?? false) && $asset->status === AssetStatus::Deployed,
                'retire' => ($user?->can('retire', $asset) ?? false) && $asset->status->canTransitionTo(AssetStatus::Retired),
                'printLabel' => $asset->status !== AssetStatus::Pending,
                'installComponent' => ($user?->can('install', Component::class) ?? false) && ! $asset->isRetired(),
                'sendToMaintenance' => ($user?->can('create', Maintenance::class) ?? false) && $canOpenTicket,
            ],
            ...$this->maintenanceEnumOptions(),
            // Loaded only when the maintenance dialog opens (partial reload with `only: ['suppliers']`).
            'suppliers' => Inertia::optional(fn (): array => $this->supplierOptions()),
            // Loaded only when the checkout dialog opens (partial reload with `only: ['employees']`).
            'employees' => Inertia::optional(fn (): array => $this->activeEmployeeOptions()),
            // Loaded only when the install dialog opens (partial reload with `only: ['componentOptions']`).
            'componentOptions' => Inertia::optional(fn (): array => $this->componentOptions()),
            'locations' => $this->locationOptions(),
            'retireReasons' => collect(RetireReason::cases())
                ->map(fn (RetireReason $reason): array => [
                    'value' => $reason->value,
                    'label' => $reason->label(),
                ])
                ->all(),
        ]);
    }

    public function edit(Asset $asset): Response
    {
        $this->authorize('update', $asset);

        return Inertia::render('Assets/Edit', [
            ...$this->formOptions(),
            'asset' => [
                'id' => $asset->id,
                'asset_tag' => $asset->asset_tag,
                'status_label' => $asset->status->label(),
                'asset_model_id' => $asset->asset_model_id,
                'serial_number' => $asset->serial_number,
                'location_id' => $asset->location_id,
                'supplier_id' => $asset->supplier_id,
                'purchase_order' => $asset->purchase_order,
                'purchase_date' => $asset->purchase_date?->toDateString(),
                'purchase_cost' => $asset->purchase_cost === null ? null : (int) round((float) $asset->purchase_cost),
                'warranty_until' => $asset->warranty_until?->toDateString(),
                'notes' => $asset->notes,
            ],
        ]);
    }

    public function update(UpdateAssetRequest $request, Asset $asset, UpdateAsset $updateAsset): RedirectResponse
    {
        $updateAsset->handle($asset, $request->validated(), $this->actor($request));

        return redirect()
            ->route('assets.show', $asset)
            ->with('success', 'Data aset diperbarui.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'models' => AssetModel::query()
                ->with(['category:id,name', 'manufacturer:id,name'])
                ->whereHas('category', fn (Builder $query) => $query->where('type', CategoryType::Asset))
                ->orderBy('name')
                ->get()
                ->map(fn (AssetModel $model): array => [
                    'id' => $model->id,
                    'name' => $model->name,
                    'model_number' => $model->model_number,
                    'category' => $model->category?->name,
                    'manufacturer' => $model->manufacturer?->name,
                ])
                ->all(),
            'locations' => $this->locationOptions(),
            'suppliers' => Supplier::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Supplier $supplier): array => ['id' => $supplier->id, 'name' => $supplier->name])
                ->all(),
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function locationOptions(): array
    {
        return Location::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Location $location): array => ['id' => $location->id, 'name' => $location->name])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function assetCategories(): array
    {
        return Category::query()
            ->where('type', CategoryType::Asset)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Category $category): array => ['id' => $category->id, 'name' => $category->name])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string, employee_number: string, department: string|null}>
     */
    private function activeEmployeeOptions(): array
    {
        return Employee::query()
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
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string, spec: string|null, qty_available: int}>
     */
    private function componentOptions(): array
    {
        return Component::query()
            ->with('stock')
            ->orderBy('name')
            ->get(['id', 'name', 'spec', 'qty_total'])
            ->map(fn (Component $component): array => [
                'id' => $component->id,
                'name' => $component->name,
                'spec' => $component->spec,
                'qty_available' => $component->stock->qty_available ?? $component->qty_total,
            ])
            ->all();
    }

    private function statusNote(AssetStatus $status): string
    {
        return match ($status) {
            AssetStatus::Pending => 'Aset baru dicatat. Cetak label dan tandai siap pakai sebelum dipinjamkan.',
            AssetStatus::Ready => 'Aset ada di gudang IT dan siap dipinjamkan ke karyawan atau lokasi.',
            AssetStatus::Deployed => 'Aset sedang dipegang karyawan atau ditempatkan di lokasi.',
            AssetStatus::Maintenance => 'Aset sedang diperbaiki. Selesaikan maintenance untuk mengembalikannya.',
            AssetStatus::Retired => 'Aset sudah dipensiunkan dan tidak bisa dipinjamkan lagi.',
        };
    }
}
