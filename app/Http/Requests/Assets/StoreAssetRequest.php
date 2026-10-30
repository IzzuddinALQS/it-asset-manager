<?php

namespace App\Http\Requests\Assets;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Asset::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'asset_model_id' => ['required', 'integer', Rule::exists('asset_models', 'id')->whereNull('deleted_at')],
            'serial_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('assets', 'serial_number')->whereNull('deleted_at'),
            ],
            'imei' => ['nullable', 'digits:15', Rule::unique('assets', 'imei')->whereNull('deleted_at')],
            'phone_number' => ['nullable', 'string', 'regex:/^0[0-9]{8,14}$/'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')->whereNull('deleted_at')],
            'purchase_order' => ['nullable', 'string', 'max:50'],
            'purchase_date' => ['nullable', 'date', 'before_or_equal:today'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'warranty_until' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'asset_model_id.required' => 'Model inventaris wajib dipilih.',
            'asset_model_id.exists' => 'Model inventaris tidak ditemukan.',
            'serial_number.unique' => 'Nomor seri ini sudah dipakai inventaris lain.',
            'imei.digits' => 'IMEI harus 15 digit angka.',
            'imei.unique' => 'IMEI ini sudah dipakai inventaris lain.',
            'phone_number.regex' => 'Nomor HP diawali 0 dan berisi 9–15 digit angka, mis. 081234567890.',
            'purchase_date.before_or_equal' => 'Tanggal pembelian tidak boleh di masa depan.',
            'purchase_cost.numeric' => 'Harga perolehan harus berupa angka.',
            'warranty_until.after_or_equal' => 'Masa garansi tidak boleh sebelum tanggal pembelian.',
        ];
    }
}
