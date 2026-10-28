<?php

namespace App\Http\Controllers\Maintenance;

use App\Actions\Maintenance\UpdateMaintenanceStatus;
use App\Enums\MaintenanceStatus;
use App\Exceptions\MaintenanceException;
use App\Http\Controllers\Controller;
use App\Models\Maintenance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateMaintenanceStatusController extends Controller
{
    public function __invoke(Request $request, Maintenance $maintenance, UpdateMaintenanceStatus $updateStatus): RedirectResponse
    {
        $this->authorize('update', $maintenance);

        $validated = $request->validate([
            'status' => ['required', Rule::in([MaintenanceStatus::InProgress->value, MaintenanceStatus::WaitingParts->value])],
        ]);

        $target = MaintenanceStatus::from($validated['status']);

        try {
            $updateStatus->handle($maintenance, $target);
        } catch (MaintenanceException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Tiket {$maintenance->ticket_number} dipindah ke \"{$target->label()}\".");
    }
}
