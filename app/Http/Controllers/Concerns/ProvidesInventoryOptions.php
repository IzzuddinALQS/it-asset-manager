<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\Location;
use App\Models\Manufacturer;

/**
 * Select options shared by the component and accessory forms.
 */
trait ProvidesInventoryOptions
{
    /**
     * @return array{categories: array<int, array{id: int, name: string}>, manufacturers: array<int, array{id: int, name: string}>, locations: array<int, array{id: int, name: string}>}
     */
    private function inventoryFormOptions(CategoryType $type): array
    {
        return [
            'categories' => Category::query()
                ->where('type', $type)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Category $category): array => ['id' => $category->id, 'name' => $category->name])
                ->all(),
            'manufacturers' => Manufacturer::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Manufacturer $manufacturer): array => ['id' => $manufacturer->id, 'name' => $manufacturer->name])
                ->all(),
            'locations' => Location::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Location $location): array => ['id' => $location->id, 'name' => $location->name])
                ->all(),
        ];
    }
}
