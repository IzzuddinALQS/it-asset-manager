<?php

namespace App\Http\Controllers\Settings;

use App\Enums\CategoryType;
use App\Enums\MasterDataType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SaveMasterDataRequest;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin CRUD for the lookup tables used by assets, inventory and licenses.
 */
class MasterDataController extends Controller
{
    public function index(MasterDataType $type): Response
    {
        return Inertia::render('Settings/MasterData', [
            'type' => $type->value,
            'types' => array_map(
                fn (MasterDataType $type): array => ['value' => $type->value, 'label' => $type->label()],
                MasterDataType::cases(),
            ),
            'fields' => $type->fields(),
            'records' => $this->records($type),
            'options' => [
                'categoryTypes' => array_map(
                    fn (CategoryType $categoryType): array => ['value' => $categoryType->value, 'label' => $categoryType->label()],
                    CategoryType::cases(),
                ),
                'assetCategories' => Category::query()
                    ->where('type', CategoryType::Asset)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (Category $category): array => ['value' => (string) $category->id, 'label' => $category->name])
                    ->all(),
                'manufacturers' => Manufacturer::query()
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (Manufacturer $manufacturer): array => ['value' => (string) $manufacturer->id, 'label' => $manufacturer->name])
                    ->all(),
            ],
        ]);
    }

    public function store(SaveMasterDataRequest $request, MasterDataType $type): RedirectResponse
    {
        $record = $type->modelClass()::query()->create($request->validated());

        return back()->with('success', "{$type->label()} {$record->getAttribute('name')} ditambahkan.");
    }

    public function update(SaveMasterDataRequest $request, MasterDataType $type, int $record): RedirectResponse
    {
        $model = $this->findRecord($type, $record);
        $model->update($request->validated());

        return back()->with('success', "{$type->label()} {$model->getAttribute('name')} diperbarui.");
    }

    public function destroy(MasterDataType $type, int $record): RedirectResponse
    {
        $model = $this->findRecord($type, $record);

        // The transaction becomes a savepoint, so a foreign-key violation leaves any outer transaction usable.
        try {
            DB::transaction(fn () => $model->delete());
        } catch (QueryException) {
            return back()->with('error', "{$type->label()} {$model->getAttribute('name')} masih dipakai data lain dan tidak bisa dihapus.");
        }

        return back()->with('success', "{$type->label()} {$model->getAttribute('name')} dihapus.");
    }

    private function findRecord(MasterDataType $type, int $id): Model
    {
        return $type->modelClass()::query()->findOrFail($id);
    }

    /**
     * Raw form values per record, keyed like the type's fields.
     *
     * @return list<array<string, mixed>>
     */
    private function records(MasterDataType $type): array
    {
        return match ($type) {
            MasterDataType::Categories => Category::query()->orderBy('type')->orderBy('name')->get()
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type->value,
                    'tag_prefix' => $category->tag_prefix,
                ])->all(),
            MasterDataType::Manufacturers => Manufacturer::query()->orderBy('name')->get()
                ->map(fn (Manufacturer $manufacturer): array => ['id' => $manufacturer->id, 'name' => $manufacturer->name])
                ->all(),
            MasterDataType::Suppliers => Supplier::query()->orderBy('name')->get()
                ->map(fn (Supplier $supplier): array => [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'contact_name' => $supplier->contact_name,
                    'phone' => $supplier->phone,
                    'email' => $supplier->email,
                    'is_service_vendor' => $supplier->is_service_vendor,
                ])->all(),
            MasterDataType::Locations => Location::query()->orderBy('name')->get()
                ->map(fn (Location $location): array => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'address' => $location->address,
                ])->all(),
            MasterDataType::Models => AssetModel::query()->orderBy('name')->get()
                ->map(fn (AssetModel $model): array => [
                    'id' => $model->id,
                    'name' => $model->name,
                    'model_number' => $model->model_number,
                    'category_id' => (string) $model->category_id,
                    'manufacturer_id' => $model->manufacturer_id === null ? null : (string) $model->manufacturer_id,
                    'depreciation_months' => $model->depreciation_months,
                ])->all(),
        };
    }
}
