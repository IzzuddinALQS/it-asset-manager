<?php

namespace App\Http\Controllers\Maintenance;

use App\Actions\Maintenance\GeneratePlanTickets;
use App\Http\Controllers\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Models\MaintenancePlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RunMaintenancePlanController extends Controller
{
    use ResolvesActor;

    public function __invoke(Request $request, MaintenancePlan $plan, GeneratePlanTickets $generatePlanTickets): RedirectResponse
    {
        $this->authorize('run', $plan);

        if (! $plan->is_active) {
            return back()->with('error', "Jadwal {$plan->name} sedang nonaktif.");
        }

        $count = $generatePlanTickets->handle($plan, $this->actor($request));
        $nextDue = $plan->fresh()?->next_due_date->translatedFormat('j M Y');

        return back()->with('success', "{$count} tiket preventif dibuat untuk {$plan->name}. Jadwal berikutnya {$nextDue}.");
    }
}
