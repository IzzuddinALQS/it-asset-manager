<?php

namespace App\Http\Controllers\Maintenance;

use App\Actions\Maintenance\GeneratePlanTickets;
use App\Actions\Maintenance\SaveMaintenancePlan;
use App\Actions\Maintenance\SyncMaintenancePlanAlert;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\SaveMaintenancePlanRequest;
use App\Models\MaintenancePlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaintenancePlanController extends Controller
{
    public function index(Request $request, GeneratePlanTickets $generatePlanTickets): Response
    {
        $this->authorize('viewAny', MaintenancePlan::class);

        $plans = MaintenancePlan::query()
            ->with(['category:id,name', 'location:id,name', 'supplier:id,name'])
            ->withCount(['maintenances as open_tickets' => fn ($query) => $query->open()])
            ->orderByDesc('is_active')
            ->orderBy('next_due_date')
            ->get()
            ->map(fn (MaintenancePlan $plan): array => [
                'id' => $plan->id,
                'name' => $plan->name,
                'category_id' => $plan->category_id,
                'category' => $plan->category?->name,
                'location_id' => $plan->location_id,
                'location' => $plan->location?->name,
                'interval_months' => $plan->interval_months,
                'next_due_date' => $plan->next_due_date->toDateString(),
                'pic' => $plan->pic,
                'supplier_id' => $plan->supplier_id,
                'supplier' => $plan->supplier?->name,
                'is_active' => $plan->is_active,
                'open_tickets' => (int) $plan->getAttribute('open_tickets'),
                'covered_assets' => $generatePlanTickets->matchingAssets($plan)->count(),
            ])
            ->all();

        $user = $request->user();

        return Inertia::render('Maintenance/Plans', [
            'plans' => $plans,
            'warningDays' => SyncMaintenancePlanAlert::DUE_WARNING_DAYS,
            'can' => [
                'manage' => $user?->can('create', MaintenancePlan::class) ?? false,
            ],
        ]);
    }

    public function store(SaveMaintenancePlanRequest $request, SaveMaintenancePlan $savePlan): RedirectResponse
    {
        $plan = $savePlan->handle(new MaintenancePlan, $request->validated());

        return back()->with('success', "Jadwal {$plan->name} ditambahkan.");
    }

    public function update(SaveMaintenancePlanRequest $request, MaintenancePlan $plan, SaveMaintenancePlan $savePlan): RedirectResponse
    {
        $savePlan->handle($plan, $request->validated());

        return back()->with('success', "Jadwal {$plan->name} diperbarui.");
    }
}
