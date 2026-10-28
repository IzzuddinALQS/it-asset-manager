<?php

namespace App\Http\Controllers\Alerts;

use App\Http\Controllers\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ResolveAlertController extends Controller
{
    use ResolvesActor;

    /**
     * Marks the alert handled. If the condition still holds, the next daily run opens a new one.
     */
    public function __invoke(Request $request, Alert $alert): RedirectResponse
    {
        $this->authorize('resolve', $alert);

        if ($alert->isResolved()) {
            return back()->with('error', 'Peringatan ini sudah ditandai selesai.');
        }

        $alert->update([
            'resolved_at' => Carbon::now(),
            'resolved_by' => $this->actor($request)->id,
        ]);

        return back()->with('success', 'Peringatan ditandai selesai.');
    }
}
