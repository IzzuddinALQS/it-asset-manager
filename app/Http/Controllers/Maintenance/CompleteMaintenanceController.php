<?php

namespace App\Http\Controllers\Maintenance;

use App\Actions\Maintenance\CompleteMaintenance;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\MaintenanceException;
use App\Http\Controllers\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\CompleteMaintenanceRequest;
use App\Models\Maintenance;
use Illuminate\Http\RedirectResponse;

class CompleteMaintenanceController extends Controller
{
    use ResolvesActor;

    public function __invoke(CompleteMaintenanceRequest $request, Maintenance $maintenance, CompleteMaintenance $completeMaintenance): RedirectResponse
    {
        $note = $request->string('note')->trim()->toString();

        try {
            $completeMaintenance->handle($maintenance, [
                'return_status' => $request->string('return_status')->toString(),
                'actual_cost' => $request->validated('actual_cost'),
                'components' => array_map(fn (array $component): array => [
                    'component_id' => (int) $component['component_id'],
                    'qty' => (int) $component['qty'],
                ], $request->validated('components', [])),
                'note' => $note === '' ? null : $note,
            ], $this->actor($request));
        } catch (InsufficientStockException $exception) {
            return back()->withErrors(['components' => $exception->getMessage()]);
        } catch (MaintenanceException $exception) {
            return back()->withErrors(['return_status' => $exception->getMessage()]);
        }

        return back()->with('success', "Tiket {$maintenance->ticket_number} selesai.");
    }
}
