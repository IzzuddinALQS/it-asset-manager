<?php

namespace App\Http\Controllers\Inventory;

use App\Actions\Inventory\SaveAccessory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\SaveAccessoryRequest;
use App\Models\Accessory;
use App\Models\AccessoryCheckout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccessoryController extends Controller
{
    public function create(): Response
    {
        $this->authorize('create', Accessory::class);

        return Inertia::render('Inventory/AccessoryForm', [
            'accessory' => null,
        ]);
    }

    public function store(SaveAccessoryRequest $request, SaveAccessory $saveAccessory): RedirectResponse
    {
        $accessory = $saveAccessory->handle(new Accessory, $request->validated());

        return redirect()
            ->route('inventory.accessories.show', $accessory)
            ->with('success', "Aksesori {$accessory->name} ditambahkan.");
    }

    public function show(Request $request, Accessory $accessory): Response
    {
        $this->authorize('view', $accessory);

        $accessory->load([
            'stock',
            'category:id,name',
            'manufacturer:id,name',
            'location:id,name',
            'checkouts' => fn ($query) => $query->with('employee.department')->latest('checked_out_at')->latest('id')->limit(50),
        ]);

        $user = $request->user();

        return Inertia::render('Inventory/AccessoryShow', [
            'accessory' => [
                'id' => $accessory->id,
                'name' => $accessory->name,
                'model' => $accessory->model,
                'category' => $accessory->category?->name,
                'manufacturer' => $accessory->manufacturer?->name,
                'location' => $accessory->location?->name,
                'unit_cost' => $accessory->unit_cost,
                'qty_total' => $accessory->qty_total,
                'qty_out' => $accessory->stock->qty_out ?? 0,
                'qty_available' => $accessory->stock->qty_available ?? $accessory->qty_total,
                'min_qty' => $accessory->min_qty,
                'checkouts' => $accessory->checkouts->map(fn (AccessoryCheckout $checkout): array => [
                    'id' => $checkout->id,
                    'employee' => $checkout->employee?->name,
                    'employee_number' => $checkout->employee?->employee_number,
                    'department' => $checkout->employee?->department?->name,
                    'qty' => $checkout->qty,
                    'checked_out_at' => $checkout->checked_out_at?->toIso8601String(),
                    'checked_in_at' => $checkout->checked_in_at?->toIso8601String(),
                    'note' => $checkout->note,
                ])->all(),
            ],
            'can' => [
                'update' => $user?->can('update', $accessory) ?? false,
                'checkout' => $user?->can('checkout', $accessory) ?? false,
            ],
        ]);
    }

    public function edit(Accessory $accessory): Response
    {
        $this->authorize('update', $accessory);

        $accessory->load('stock');

        return Inertia::render('Inventory/AccessoryForm', [
            'accessory' => [
                'id' => $accessory->id,
                'name' => $accessory->name,
                'model' => $accessory->model,
                'category_id' => $accessory->category_id,
                'manufacturer_id' => $accessory->manufacturer_id,
                'qty_total' => $accessory->qty_total,
                'min_qty' => $accessory->min_qty,
                'unit_cost' => $accessory->unit_cost === null ? null : (int) round((float) $accessory->unit_cost),
                'location_id' => $accessory->location_id,
                'qty_used' => $accessory->stock->qty_out ?? 0,
            ],
        ]);
    }

    public function update(SaveAccessoryRequest $request, Accessory $accessory, SaveAccessory $saveAccessory): RedirectResponse
    {
        $saveAccessory->handle($accessory, $request->validated());

        return redirect()
            ->route('inventory.accessories.show', $accessory)
            ->with('success', "Aksesori {$accessory->name} diperbarui.");
    }
}
