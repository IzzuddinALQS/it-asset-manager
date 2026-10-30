<?php

namespace App\Http\Controllers\Licenses;

use App\Actions\Licenses\AssignLicenseSeat;
use App\Exceptions\LicenseSeatException;
use App\Http\Controllers\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Licenses\AssignLicenseSeatRequest;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\License;
use Illuminate\Http\RedirectResponse;

class AssignLicenseSeatController extends Controller
{
    use ResolvesActor;

    public function __invoke(AssignLicenseSeatRequest $request, License $license, AssignLicenseSeat $assignLicenseSeat): RedirectResponse
    {
        $isEmployee = $request->string('holder_type')->toString() === 'employee';
        $holder = $isEmployee
            ? Employee::query()->findOrFail($request->integer('employee_id'))
            : Asset::query()->findOrFail($request->integer('asset_id'));

        try {
            $assignLicenseSeat->handle($license, $holder, $this->actor($request));
        } catch (LicenseSeatException $exception) {
            return back()->withErrors([$isEmployee ? 'employee_id' : 'asset_id' => $exception->getMessage()]);
        }

        $holderName = $holder instanceof Employee ? $holder->name : $holder->asset_tag;

        return back()->with('success', "Kuota {$license->name} diberikan ke {$holderName}.");
    }
}
