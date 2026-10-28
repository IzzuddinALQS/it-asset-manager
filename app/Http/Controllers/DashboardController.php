<?php

namespace App\Http\Controllers;

use App\Actions\Licenses\SyncLicenseAlerts;
use App\Actions\Maintenance\SyncMaintenancePlanAlert;
use App\Enums\AssetStatus;
use App\Http\Controllers\Alerts\AlertController;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\License;
use App\Models\Maintenance;
use App\Models\MaintenancePlan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const int LIST_LIMIT = 6;

    public function __invoke(): Response
    {
        $statusCounts = Asset::query()->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $count = fn (AssetStatus $status): int => (int) ($statusCounts[$status->value] ?? 0);
        $today = Carbon::today();

        return Inertia::render('Dashboard', [
            'kpis' => [
                'total' => $statusCounts->sum() - $count(AssetStatus::Retired),
                'retired' => $count(AssetStatus::Retired),
                'deployed' => $count(AssetStatus::Deployed),
                'ready' => $count(AssetStatus::Ready),
                'maintenance' => $count(AssetStatus::Maintenance),
                'acquisition_value' => (string) Asset::query()
                    ->whereNot('status', AssetStatus::Retired)
                    ->sum('purchase_cost'),
            ],
            'statusDistribution' => collect(AssetStatus::cases())
                ->map(fn (AssetStatus $status): array => [
                    'key' => $status->value,
                    'label' => $status->label(),
                    'total' => $count($status),
                ])
                ->all(),
            'categoryDistribution' => $this->categoryDistribution(),
            'expiringLicenses' => License::query()
                ->expiringSoon(SyncLicenseAlerts::EXPIRY_WARNING_DAYS)
                ->withCount('activeSeats')
                ->orderBy('expires_at')
                ->limit(self::LIST_LIMIT)
                ->get()
                ->map(fn (License $license): array => [
                    'id' => $license->id,
                    'name' => $license->name,
                    'expires_at' => $license->expires_at?->toDateString(),
                    'days_left' => (int) $today->diffInDays($license->expires_at, false),
                    'seats_used' => $license->active_seats_count,
                    'seats_total' => $license->seats_total,
                ])
                ->all(),
            'maintenanceDue' => MaintenancePlan::query()
                ->where('is_active', true)
                ->whereDate('next_due_date', '<=', $today->copy()->addDays(SyncMaintenancePlanAlert::DUE_WARNING_DAYS))
                ->with(['category:id,name', 'location:id,name'])
                ->orderBy('next_due_date')
                ->limit(self::LIST_LIMIT)
                ->get()
                ->map(fn (MaintenancePlan $plan): array => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'scope' => collect([$plan->category?->name, $plan->location?->name])->filter()->join(' · '),
                    'next_due_date' => $plan->next_due_date->toDateString(),
                    'days_left' => (int) $today->diffInDays($plan->next_due_date, false),
                ])
                ->all(),
            'overdueTickets' => Maintenance::query()->open()->whereDate('target_date', '<', $today)->count(),
            'recentAlerts' => Alert::query()
                ->open()
                ->with('subject')
                ->latest()
                ->latest('id')
                ->limit(self::LIST_LIMIT)
                ->get()
                ->map(fn (Alert $alert): array => AlertController::present($alert))
                ->all(),
        ]);
    }

    /**
     * Assets in service per category, largest first.
     *
     * @return list<array{key: string, label: string, total: int}>
     */
    private function categoryDistribution(): array
    {
        return DB::table('assets')
            ->join('asset_models', 'asset_models.id', '=', 'assets.asset_model_id')
            ->join('categories', 'categories.id', '=', 'asset_models.category_id')
            ->whereNull('assets.deleted_at')
            ->where('assets.status', '<>', AssetStatus::Retired->value)
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->orderBy('categories.name')
            ->get(['categories.id', 'categories.name', DB::raw('count(*) as total')])
            ->map(fn (object $row): array => [
                'key' => (string) $row->id,
                'label' => $row->name,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }
}
