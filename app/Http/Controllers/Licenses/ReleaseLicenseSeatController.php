<?php

namespace App\Http\Controllers\Licenses;

use App\Actions\Licenses\ReleaseLicenseSeat;
use App\Exceptions\LicenseSeatException;
use App\Http\Controllers\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Models\LicenseSeat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReleaseLicenseSeatController extends Controller
{
    use ResolvesActor;

    public function __invoke(Request $request, LicenseSeat $seat, ReleaseLicenseSeat $releaseLicenseSeat): RedirectResponse
    {
        $license = $seat->license()->withTrashed()->firstOrFail();

        $this->authorize('assignSeat', $license);

        try {
            $releaseLicenseSeat->handle($seat, $this->actor($request));
        } catch (LicenseSeatException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Kuota {$license->name} dilepas.");
    }
}
