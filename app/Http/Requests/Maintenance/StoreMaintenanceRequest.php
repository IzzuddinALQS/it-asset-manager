<?php

namespace App\Http\Requests\Maintenance;

use App\Enums\HandledBy;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceType;
use App\Models\Maintenance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Maintenance::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'integer', Rule::exists('assets', 'id')->whereNull('deleted_at')],
            'type' => ['required', Rule::enum(MaintenanceType::class)],
            'priority' => ['required', Rule::enum(MaintenancePriority::class)],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'handled_by' => ['required', Rule::enum(HandledBy::class)],
            'supplier_id' => ['nullable', 'required_if:handled_by,vendor', 'integer', 'exists:suppliers,id'],
            'reported_by_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'start_date' => ['nullable', 'date'],
            'target_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'asset_id.required' => 'Pilih aset yang akan di-maintenance.',
            'asset_id.exists' => 'Aset tidak ditemukan.',
            'type.required' => 'Jenis maintenance wajib dipilih.',
            'title.required' => 'Judul tiket wajib diisi.',
            'supplier_id.required_if' => 'Pilih vendor yang menangani.',
            'target_date.after_or_equal' => 'Target selesai tidak boleh sebelum tanggal mulai.',
        ];
    }
}
