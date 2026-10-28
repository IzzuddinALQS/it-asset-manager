<?php

namespace App\Http\Controllers\Inventory;

use App\Actions\Inventory\RemoveComponent;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\ComponentInstallation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RemoveComponentController extends Controller
{
    use ResolvesActor;

    public function __invoke(Request $request, ComponentInstallation $installation, RemoveComponent $removeComponent): RedirectResponse
    {
        $this->authorize('install', Component::class);

        $validated = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        try {
            $removeComponent->handle($installation, $this->actor($request), $validated['note'] ?? null);
        } catch (InsufficientStockException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Komponen dilepas dan dikembalikan ke stok.');
    }
}
