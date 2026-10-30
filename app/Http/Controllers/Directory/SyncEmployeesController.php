<?php

namespace App\Http\Controllers\Directory;

use App\Actions\Herd\SyncEmployeesFromHerd;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Sync sekarang" on the directory page: runs the HERD employee sync on demand.
 */
class SyncEmployeesController extends Controller
{
    public function __invoke(Request $request, SyncEmployeesFromHerd $syncEmployees): RedirectResponse
    {
        $run = $syncEmployees->handle($request->user());

        if ($run->status === 'failed') {
            return back()->with('error', "Sinkronisasi HERD gagal: {$run->error_message}");
        }

        return back()->with('success', "Sinkronisasi HERD selesai: {$run->created_count} baru, {$run->updated_count} diperbarui, {$run->offboarding_count} offboarding.");
    }
}
