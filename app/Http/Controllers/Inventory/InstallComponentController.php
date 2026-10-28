<?php

namespace App\Http\Controllers\Inventory;

use App\Actions\Inventory\InstallComponent;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\InstallComponentRequest;
use App\Models\Asset;
use Illuminate\Http\RedirectResponse;

class InstallComponentController extends Controller
{
    use ResolvesActor;

    public function __invoke(InstallComponentRequest $request, Asset $asset, InstallComponent $installComponent): RedirectResponse
    {
        if ($asset->isRetired()) {
            return back()->with('error', 'Aset yang sudah dipensiunkan tidak bisa dipasangi komponen.');
        }

        $note = $request->string('note')->trim()->toString();

        try {
            $installation = $installComponent->handle($asset, [
                'component_id' => $request->integer('component_id'),
                'qty' => $request->integer('qty'),
                'is_factory' => $request->boolean('is_factory'),
                'note' => $note === '' ? null : $note,
            ], $this->actor($request));
        } catch (InsufficientStockException $exception) {
            return back()->withErrors(['qty' => $exception->getMessage()]);
        }

        return back()->with('success', "{$installation->qty}× {$installation->component?->name} dipasang ke {$asset->asset_tag}.");
    }
}
