<?php

namespace App\Http\Controllers\Maintenance;

use App\Actions\Maintenance\CancelMaintenance;
use App\Exceptions\MaintenanceException;
use App\Http\Controllers\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Models\Maintenance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CancelMaintenanceController extends Controller
{
    use ResolvesActor;

    public function __invoke(Request $request, Maintenance $maintenance, CancelMaintenance $cancelMaintenance): RedirectResponse
    {
        $this->authorize('update', $maintenance);

        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $reason = $request->string('reason')->trim()->toString();

        try {
            $cancelMaintenance->handle($maintenance, $this->actor($request), $reason === '' ? null : $reason);
        } catch (MaintenanceException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Tiket {$maintenance->ticket_number} dibatalkan.");
    }
}
