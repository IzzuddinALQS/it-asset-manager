<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Accessory;
use App\Models\Component;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Stock overview for components and accessories; one tab is loaded per request.
 */
class InventoryController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tab = $request->string('tab')->toString() === 'accessories' ? 'accessories' : 'components';

        $this->authorize('viewAny', $tab === 'accessories' ? Accessory::class : Component::class);

        $search = $request->string('search')->trim()->toString();
        $lowStockOnly = $request->boolean('low_stock');
        $user = $request->user();

        return Inertia::render('Inventory/Index', [
            'tab' => $tab,
            'filters' => [
                'search' => $search,
                'low_stock' => $lowStockOnly,
            ],
            'items' => $tab === 'accessories'
                ? $this->accessories($search, $lowStockOnly)
                : $this->components($search, $lowStockOnly),
            'lowStockCounts' => [
                'components' => Component::query()->whereHas('stock', $this->lowStockConstraint('component_stock', 'components'))->count(),
                'accessories' => Accessory::query()->whereHas('stock', $this->lowStockConstraint('accessory_stock', 'accessories'))->count(),
            ],
            'can' => [
                'createComponent' => $user?->can('create', Component::class) ?? false,
                'createAccessory' => $user?->can('create', Accessory::class) ?? false,
            ],
        ]);
    }

    /**
     * Paginated rows for the components tab.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function components(string $search, bool $lowStockOnly): LengthAwarePaginator
    {
        return Component::query()
            ->with(['stock', 'category:id,name', 'manufacturer:id,name', 'location:id,name'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = '%'.$search.'%';
                $query->where(fn (Builder $query) => $query->where('name', 'ilike', $term)->orWhere('spec', 'ilike', $term));
            })
            ->when($lowStockOnly, fn (Builder $query) => $query->whereHas('stock', $this->lowStockConstraint('component_stock', 'components')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Component $component): array => [
                'id' => $component->id,
                'name' => $component->name,
                'detail' => $component->spec,
                'category' => $component->category?->name,
                'manufacturer' => $component->manufacturer?->name,
                'location' => $component->location?->name,
                'qty_total' => $component->qty_total,
                'qty_used' => $component->stock->qty_installed ?? 0,
                'qty_available' => $component->stock->qty_available ?? $component->qty_total,
                'min_qty' => $component->min_qty,
                'unit_cost' => $component->unit_cost,
            ]);
    }

    /**
     * Paginated rows for the accessories tab.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function accessories(string $search, bool $lowStockOnly): LengthAwarePaginator
    {
        return Accessory::query()
            ->with(['stock', 'category:id,name', 'manufacturer:id,name', 'location:id,name'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = '%'.$search.'%';
                $query->where(fn (Builder $query) => $query->where('name', 'ilike', $term)->orWhere('model', 'ilike', $term));
            })
            ->when($lowStockOnly, fn (Builder $query) => $query->whereHas('stock', $this->lowStockConstraint('accessory_stock', 'accessories')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Accessory $accessory): array => [
                'id' => $accessory->id,
                'name' => $accessory->name,
                'detail' => $accessory->model,
                'category' => $accessory->category?->name,
                'manufacturer' => $accessory->manufacturer?->name,
                'location' => $accessory->location?->name,
                'qty_total' => $accessory->qty_total,
                'qty_used' => $accessory->stock->qty_out ?? 0,
                'qty_available' => $accessory->stock->qty_available ?? $accessory->qty_total,
                'min_qty' => $accessory->min_qty,
                'unit_cost' => $accessory->unit_cost,
            ]);
    }

    /**
     * Same rule as SyncLowStockAlert: available stock at or below the minimum.
     */
    private function lowStockConstraint(string $view, string $table): Closure
    {
        return fn (Builder $query) => $query->whereColumn("{$view}.qty_available", '<=', "{$table}.min_qty");
    }
}
