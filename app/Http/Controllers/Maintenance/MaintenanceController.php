<?php

namespace App\Http\Controllers\Maintenance;

use App\Actions\Maintenance\ReleaseAssetFromMaintenance;
use App\Actions\Maintenance\SendToMaintenance;
use App\Actions\Maintenance\SyncMaintenancePlanAlert;
use App\Enums\AssetStatus;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Exceptions\MaintenanceException;
use App\Http\Controllers\Concerns\ProvidesMaintenanceOptions;
use App\Http\Controllers\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\StoreMaintenanceRequest;
use App\Models\Asset;
use App\Models\ComponentInstallation;
use App\Models\Maintenance;
use App\Models\MaintenancePlan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceController extends Controller
{
    use ProvidesMaintenanceOptions;
    use ResolvesActor;

    /** Closed tickets stay on the board for this many days. */
    private const int CLOSED_VISIBLE_DAYS = 30;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Maintenance::class);

        $search = $request->string('search')->trim()->toString();
        $type = MaintenanceType::tryFrom($request->string('type')->toString());
        $priority = MaintenancePriority::tryFrom($request->string('priority')->toString());
        $showCancelled = $request->boolean('show_cancelled');
        $closedSince = Carbon::now()->subDays(self::CLOSED_VISIBLE_DAYS);

        $tickets = Maintenance::query()
            ->with(['asset.assetModel:id,name', 'asset.assignedEmployee:id,name', 'supplier:id,name'])
            ->where(function (Builder $query) use ($closedSince, $showCancelled): void {
                $query->open()
                    ->orWhere(fn (Builder $query) => $query
                        ->where('status', MaintenanceStatus::Done)
                        ->where('completed_at', '>=', $closedSince))
                    ->when($showCancelled, fn (Builder $query) => $query->orWhere(fn (Builder $query) => $query
                        ->where('status', MaintenanceStatus::Cancelled)
                        ->where('updated_at', '>=', $closedSince)));
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = '%'.$search.'%';
                $query->where(fn (Builder $query) => $query
                    ->where('ticket_number', 'ilike', $term)
                    ->orWhere('title', 'ilike', $term)
                    ->orWhereHas('asset', fn (Builder $query) => $query->where('asset_tag', 'ilike', $term)));
            })
            ->when($type !== null, fn (Builder $query) => $query->where('type', $type))
            ->when($priority !== null, fn (Builder $query) => $query->where('priority', $priority))
            ->orderByRaw("case priority when 'high' then 0 when 'medium' then 1 else 2 end")
            ->orderByRaw('target_date is null, target_date')
            ->latest('id')
            ->limit(300)
            ->get()
            ->map(fn (Maintenance $maintenance): array => [
                'id' => $maintenance->id,
                'ticket_number' => $maintenance->ticket_number,
                'title' => $maintenance->title,
                'status' => $maintenance->status->value,
                'type' => $maintenance->type->value,
                'type_label' => $maintenance->type->label(),
                'priority' => $maintenance->priority->value,
                'priority_label' => $maintenance->priority->label(),
                'handled_by_label' => $maintenance->handled_by->label(),
                'supplier' => $maintenance->supplier?->name,
                'asset' => [
                    'id' => $maintenance->asset_id,
                    'asset_tag' => $maintenance->asset?->asset_tag,
                    'model' => $maintenance->asset?->assetModel?->name,
                    'holder' => $maintenance->asset?->assignedEmployee?->name,
                ],
                'target_date' => $maintenance->target_date?->toDateString(),
                'is_overdue' => $maintenance->status->isOpen()
                    && $maintenance->target_date !== null
                    && $maintenance->target_date->lt(Carbon::today()),
                'completed_at' => $maintenance->completed_at?->toIso8601String(),
            ])
            ->all();

        $user = $request->user();

        return Inertia::render('Maintenance/Index', [
            'filters' => [
                'search' => $search,
                'type' => $type?->value ?? '',
                'priority' => $priority?->value ?? '',
                'show_cancelled' => $showCancelled,
            ],
            'tickets' => $tickets,
            'summary' => [
                'open' => Maintenance::query()->open()->count(),
                'overdue' => Maintenance::query()->open()->whereDate('target_date', '<', Carbon::today())->count(),
                'cost_this_year' => (string) Maintenance::query()
                    ->where('status', MaintenanceStatus::Done)
                    ->whereYear('completed_at', Carbon::now()->year)
                    ->sum('actual_cost'),
                'plans_due' => MaintenancePlan::query()
                    ->where('is_active', true)
                    ->whereDate('next_due_date', '<=', Carbon::today()->addDays(SyncMaintenancePlanAlert::DUE_WARNING_DAYS))
                    ->count(),
            ],
            'closedVisibleDays' => self::CLOSED_VISIBLE_DAYS,
            ...$this->maintenanceEnumOptions(),
            'can' => [
                'create' => $user?->can('create', Maintenance::class) ?? false,
                'update' => $user?->can('update', new Maintenance) ?? false,
            ],
        ]);
    }

    public function store(StoreMaintenanceRequest $request, SendToMaintenance $sendToMaintenance): RedirectResponse
    {
        $asset = Asset::query()->findOrFail($request->integer('asset_id'));

        try {
            $maintenance = $sendToMaintenance->handle($asset, $request->safe()->except('asset_id'), $this->actor($request));
        } catch (MaintenanceException $exception) {
            return back()->withErrors(['asset_id' => $exception->getMessage()]);
        }

        return back()->with('success', "Tiket {$maintenance->ticket_number} dibuat, {$asset->asset_tag} masuk maintenance.");
    }

    public function show(Request $request, Maintenance $maintenance): Response
    {
        $this->authorize('view', $maintenance);

        $maintenance->load([
            'asset.assetModel.category',
            'asset.assignedEmployee.department',
            'asset.assignedLocation',
            'supplier:id,name',
            'plan:id,name',
            'reportedBy:id,name',
            'creator.employee:id,name',
            'componentInstallations.component:id,name,spec',
        ]);

        $asset = $maintenance->asset;
        $isOpen = $maintenance->status->isOpen();
        $canUpdate = $request->user()?->can('update', $maintenance) ?? false;

        return Inertia::render('Maintenance/Show', [
            'maintenance' => [
                'id' => $maintenance->id,
                'ticket_number' => $maintenance->ticket_number,
                'title' => $maintenance->title,
                'description' => $maintenance->description,
                'status' => $maintenance->status->value,
                'status_label' => $maintenance->status->label(),
                'type_label' => $maintenance->type->label(),
                'priority' => $maintenance->priority->value,
                'priority_label' => $maintenance->priority->label(),
                'handled_by_label' => $maintenance->handled_by->label(),
                'supplier' => $maintenance->supplier?->name,
                'plan' => $maintenance->plan?->name,
                'reported_by' => $maintenance->reportedBy?->name,
                'created_by' => $maintenance->creator?->employee?->name,
                'estimated_cost' => $maintenance->estimated_cost,
                'actual_cost' => $maintenance->actual_cost,
                'start_date' => $maintenance->start_date?->toDateString(),
                'target_date' => $maintenance->target_date?->toDateString(),
                'completed_at' => $maintenance->completed_at?->toIso8601String(),
                'created_at' => $maintenance->created_at?->toIso8601String(),
                'asset_status_before' => $maintenance->asset_status_before?->value,
                'asset_status_before_label' => $maintenance->asset_status_before?->label(),
                'asset' => [
                    'id' => $asset->id,
                    'asset_tag' => $asset->asset_tag,
                    'status' => $asset->status->value,
                    'status_label' => $asset->status->label(),
                    'model' => $asset->assetModel?->name,
                    'category' => $asset->assetModel?->category?->name,
                    'warranty_until' => $asset->warranty_until?->toDateString(),
                    'holder' => $asset->assignedEmployee?->name ?? $asset->assignedLocation?->name,
                ],
                'components' => $maintenance->componentInstallations->map(fn (ComponentInstallation $installation): array => [
                    'id' => $installation->id,
                    'name' => $installation->component?->name,
                    'spec' => $installation->component?->spec,
                    'qty' => $installation->qty,
                    'installed_at' => $installation->installed_at?->toIso8601String(),
                ])->all(),
            ],
            'canReturnToHolder' => $asset->status === AssetStatus::Maintenance
                && ReleaseAssetFromMaintenance::canReturnToHolder($maintenance, $asset),
            'can' => [
                'move' => $canUpdate && $isOpen,
                'complete' => $canUpdate && $isOpen,
                'cancel' => $canUpdate && $isOpen,
            ],
            // Loaded only when the completion dialog opens (partial reload).
        ]);
    }
}
