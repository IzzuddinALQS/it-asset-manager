<?php

namespace App\Http\Requests\Maintenance;

use App\Enums\AssetStatus;
use App\Models\Maintenance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $maintenance = $this->route('maintenance');

        return $maintenance instanceof Maintenance && $this->user()?->can('update', $maintenance) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'return_status' => ['required', Rule::in([AssetStatus::Ready->value, AssetStatus::Deployed->value])],
            'actual_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'components' => ['array', 'max:20'],
            'components.*.component_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('components', 'id')->whereNull('deleted_at'),
            ],
            'components.*.qty' => ['required', 'integer', 'min:1', 'max:1000'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'return_status.required' => 'Pilih status aset setelah maintenance.',
            'components.*.component_id.required' => 'Pilih komponen yang dipakai.',
            'components.*.component_id.distinct' => 'Komponen yang sama dipilih lebih dari sekali.',
            'components.*.qty.min' => 'Jumlah minimal 1.',
        ];
    }
}
