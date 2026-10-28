<?php

namespace App\Http\Requests\Inventory;

use App\Enums\EmploymentStatus;
use App\Models\Accessory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutAccessoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $accessory = $this->route('accessory');

        return $accessory instanceof Accessory && $this->user()?->can('checkout', $accessory) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where('employment_status', EmploymentStatus::Active->value),
            ],
            'qty' => ['required', 'integer', 'min:1', 'max:1000'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Pilih karyawan penerima.',
            'employee_id.exists' => 'Karyawan tidak ditemukan atau sudah tidak aktif.',
            'qty.min' => 'Jumlah minimal 1.',
        ];
    }
}
