<?php

namespace App\Http\Requests\Assets;

use App\Enums\EmploymentStatus;
use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $asset = $this->route('asset');

        return $asset instanceof Asset && $this->user()?->can('checkout', $asset) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'assigned_employee_id' => [
                'nullable',
                'integer',
                'required_without:assigned_location_id',
                'prohibits:assigned_location_id',
                Rule::exists('employees', 'id')->where('employment_status', EmploymentStatus::Active->value),
            ],
            'assigned_location_id' => [
                'nullable',
                'integer',
                'required_without:assigned_employee_id',
                'exists:locations,id',
            ],
            'expected_return_date' => ['nullable', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'assigned_employee_id.required_without' => 'Pilih karyawan atau lokasi tujuan.',
            'assigned_employee_id.prohibits' => 'Pilih salah satu: karyawan atau lokasi.',
            'assigned_employee_id.exists' => 'Karyawan tidak ditemukan atau sudah tidak aktif.',
            'assigned_location_id.required_without' => 'Pilih karyawan atau lokasi tujuan.',
            'expected_return_date.after_or_equal' => 'Tanggal pengembalian tidak boleh di masa lalu.',
        ];
    }
}
