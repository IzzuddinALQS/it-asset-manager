<?php

namespace App\Http\Controllers\Inventory;

use App\Actions\Inventory\SaveComponent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\SaveComponentRequest;
use App\Models\Component;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ComponentController extends Controller
{
    public function create(): Response
    {
        $this->authorize('create', Component::class);

        return Inertia::render('Inventory/ComponentForm', [
            'component' => null,
        ]);
    }

    public function store(SaveComponentRequest $request, SaveComponent $saveComponent): RedirectResponse
    {
        $component = $saveComponent->handle(new Component, $request->validated());

        return redirect()
            ->route('inventory.index')
            ->with('success', "Komponen {$component->name} ditambahkan.");
    }

    public function edit(Component $component): Response
    {
        $this->authorize('update', $component);

        $component->load('stock');

        return Inertia::render('Inventory/ComponentForm', [
            'component' => [
                'id' => $component->id,
                'name' => $component->name,
                'spec' => $component->spec,
                'category_id' => $component->category_id,
                'manufacturer_id' => $component->manufacturer_id,
                'qty_total' => $component->qty_total,
                'min_qty' => $component->min_qty,
                'unit_cost' => $component->unit_cost === null ? null : (int) round((float) $component->unit_cost),
                'location_id' => $component->location_id,
                'qty_used' => $component->stock->qty_installed ?? 0,
            ],
        ]);
    }

    public function update(SaveComponentRequest $request, Component $component, SaveComponent $saveComponent): RedirectResponse
    {
        $saveComponent->handle($component, $request->validated());

        return redirect()
            ->route('inventory.index')
            ->with('success', "Komponen {$component->name} diperbarui.");
    }
}
