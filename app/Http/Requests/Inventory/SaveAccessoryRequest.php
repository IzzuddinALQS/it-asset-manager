<?php

namespace App\Http\Requests\Inventory;

use App\Enums\CategoryType;
use App\Models\Accessory;
use App\Models\AccessoryStock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAccessoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $accessory = $this->route('accessory');

        return $accessory instanceof Accessory
            ? $this->user()?->can('update', $accessory) === true
            : $this->user()?->can('create', Accessory::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $accessory = $this->route('accessory');
        $out = $accessory instanceof Accessory
            ? (AccessoryStock::query()->find($accessory->id)->qty_out ?? 0)
            : 0;

        return [
            'name' => ['required', 'string', 'max:150'],
            'model' => ['nullable', 'string', 'max:150'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('type', CategoryType::Accessory->value),
            ],
            'manufacturer_id' => ['nullable', 'integer', 'exists:manufacturers,id'],
            // Units on loan cannot disappear from the total.
            'qty_total' => ['required', 'integer', 'min:'.$out, 'max:1000000'],
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
            'name.required' => 'Nama aksesori wajib diisi.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori aksesori tidak ditemukan.',
            'qty_total.min' => 'Jumlah total tidak boleh kurang dari :min unit yang sedang dipinjam.',
            'min_qty.min' => 'Stok minimum tidak boleh negatif.',
        ];
    }
}
