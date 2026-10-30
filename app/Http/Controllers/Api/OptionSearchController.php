<?php

namespace App\Http\Controllers\Api;

use App\Enums\CategoryType;
use App\Http\Controllers\Controller;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Component;
use App\Models\Department;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Type-ahead search for every master-data picker. `q` filters by name; `id` resolves
 * the label of an already-saved value (edit forms) without loading the whole table.
 */
class OptionSearchController extends Controller
{
    public const int LIMIT = 20;

    /**
     * @var list<string>
     */
    public const array SOURCES = ['suppliers', 'manufacturers', 'locations', 'departments', 'categories', 'asset-models', 'components'];

    public function __invoke(Request $request, string $source): JsonResponse
    {
        abort_unless(in_array($source, self::SOURCES, true), 404);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'id' => ['nullable', 'integer'],
            'category_type' => ['nullable', Rule::enum(CategoryType::class)],
        ]);

        $term = trim($validated['q'] ?? '');
        $query = $this->query($source, $validated['category_type'] ?? null);

        if (isset($validated['id'])) {
            $query->whereKey($validated['id']);
        } elseif ($term !== '') {
            $query->where($query->qualifyColumn('name'), 'ilike', "%{$term}%");
        }

        return response()->json([
            'data' => $query->limit(self::LIMIT)->get()->map(fn ($record): array => $this->option($source, $record))->all(),
        ]);
    }

    /**
     * @return Builder<covariant \Illuminate\Database\Eloquent\Model>
     */
    private function query(string $source, ?string $categoryType): Builder
    {
        return match ($source) {
            // Service vendors first, since they are the usual pick for a ticket.
            'suppliers' => Supplier::query()->orderByDesc('is_service_vendor')->orderBy('name'),
            'manufacturers' => Manufacturer::query()->orderBy('name'),
            'locations' => Location::query()->orderBy('name'),
            'departments' => Department::query()->where('is_active', true)->orderBy('name'),
            'categories' => Category::query()
                ->when($categoryType !== null, fn (Builder $query) => $query->where('type', $categoryType))
                ->orderBy('name'),
            'asset-models' => AssetModel::query()
                ->with(['category:id,name', 'manufacturer:id,name'])
                ->whereHas('category', fn (Builder $query) => $query->where('type', CategoryType::Asset))
                ->orderBy('name'),
            'components' => Component::query()->with('stock')->orderBy('name'),
        };
    }

    /**
     * @return array{value: string, label: string, hint: string|null, meta?: array<string, mixed>}
     */
    private function option(string $source, mixed $record): array
    {
        return match ($source) {
            'suppliers' => [
                'value' => (string) $record->id,
                'label' => $record->name,
                'hint' => $record->is_service_vendor ? 'Vendor servis' : null,
            ],
            'asset-models' => [
                'value' => (string) $record->id,
                'label' => $record->name,
                'hint' => $record->category?->name,
            ],
            'components' => [
                'value' => (string) $record->id,
                'label' => $record->name,
                'hint' => collect([$record->spec, 'stok '.($record->stock->qty_available ?? $record->qty_total)])->filter()->implode(' · '),
                'meta' => ['qty_available' => $record->stock->qty_available ?? $record->qty_total],
            ],
            default => [
                'value' => (string) $record->id,
                'label' => $record->name,
                'hint' => null,
            ],
        };
    }
}
