<?php

namespace App\Http\Requests\Licenses;

use App\Enums\CategoryType;
use App\Enums\LicenseType;
use App\Models\License;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $license = $this->route('license');

        return $license instanceof License
            ? $this->user()?->can('update', $license) === true
            : $this->user()?->can('create', License::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $license = $this->route('license');
        $seatsUsed = $license instanceof License ? $license->seatsUsed() : 0;

        return [
            'name' => ['required', 'string', 'max:150'],
            'manufacturer_id' => ['nullable', 'integer', 'exists:manufacturers,id'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('type', CategoryType::License->value),
            ],
            'license_type' => ['required', Rule::enum(LicenseType::class)],
            // Left blank on edit to keep the stored key; the form never receives the plain key.
            'product_key' => ['nullable', 'string', 'max:500'],
            // Seats in use cannot disappear from the total.
            'seats_total' => ['required', 'integer', 'min:'.max(1, $seatsUsed), 'max:100000'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'purchase_order' => ['nullable', 'string', 'max:50'],
            'purchase_date' => ['nullable', 'date'],
            'annual_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama lisensi wajib diisi.',
            'category_id.exists' => 'Kategori lisensi tidak ditemukan.',
            'license_type.required' => 'Jenis lisensi wajib dipilih.',
            'seats_total.required' => 'Jumlah seat wajib diisi.',
            'seats_total.min' => 'Jumlah seat tidak boleh kurang dari :min (seat yang sedang terpakai).',
            'expires_at.after_or_equal' => 'Tanggal kedaluwarsa tidak boleh sebelum tanggal pembelian.',
        ];
    }
}
