<?php

namespace App\Http\Requests\Inventory;

use App\Enums\CategoryType;
use App\Models\Component;
use App\Models\ComponentStock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $component = $this->route('component');

        return $component instanceof Component
            ? $this->user()?->can('update', $component) === true
            : $this->user()?->can('create', Component::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $component = $this->route('component');
        $installed = $component instanceof Component
            ? (ComponentStock::query()->find($component->id)->qty_installed ?? 0)
            : 0;

        return [
            'name' => ['required', 'string', 'max:150'],
            'spec' => ['nullable', 'string', 'max:150'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('type', CategoryType::Component->value),
            ],
            'manufacturer_id' => ['nullable', 'integer', 'exists:manufacturers,id'],
            // Stock already installed in assets cannot disappear from the total.
            'qty_total' => ['required', 'integer', 'min:'.$installed, 'max:1000000'],
            'min_qty' => ['required', 'integer', 'min:0', 'max:1000000'],
            'unit_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama komponen wajib diisi.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori komponen tidak ditemukan.',
            'qty_total.min' => 'Jumlah total tidak boleh kurang dari :min unit yang sedang terpasang.',
            'min_qty.min' => 'Stok minimum tidak boleh negatif.',
        ];
    }
}
