<?php

namespace App\Http\Requests\Maintenance;

use App\Enums\CategoryType;
use App\Models\MaintenancePlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveMaintenancePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $plan = $this->route('plan');

        return $plan instanceof MaintenancePlan
            ? $this->user()?->can('update', $plan) === true
            : $this->user()?->can('create', MaintenancePlan::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('type', CategoryType::Asset->value),
            ],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'interval_months' => ['required', 'integer', 'min:1', 'max:60'],
            'next_due_date' => ['required', 'date'],
            'pic' => ['nullable', 'string', 'max:150'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama jadwal wajib diisi.',
            'category_id.required' => 'Pilih kategori inventaris yang dicakup.',
            'interval_months.required' => 'Interval wajib diisi.',
            'interval_months.min' => 'Interval minimal 1 bulan.',
            'next_due_date.required' => 'Tanggal jatuh tempo berikutnya wajib diisi.',
        ];
    }
}
