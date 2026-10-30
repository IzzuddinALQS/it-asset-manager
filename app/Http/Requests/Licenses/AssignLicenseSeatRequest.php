<?php

namespace App\Http\Requests\Licenses;

use App\Enums\AssetStatus;
use App\Enums\EmploymentStatus;
use App\Models\License;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignLicenseSeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        $license = $this->route('license');

        return $license instanceof License && $this->user()?->can('assignSeat', $license) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'holder_type' => ['required', Rule::in(['employee', 'asset'])],
            'employee_id' => [
                'required_if:holder_type,employee',
                'nullable',
                'integer',
                Rule::exists('employees', 'id')->where('employment_status', EmploymentStatus::Active->value),
            ],
            'asset_id' => [
                'required_if:holder_type,asset',
                'nullable',
                'integer',
                Rule::exists('assets', 'id')
                    ->whereNull('deleted_at')
                    ->whereNot('status', AssetStatus::Retired->value),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.required_if' => 'Pilih karyawan penerima kuota.',
            'employee_id.exists' => 'Karyawan tidak ditemukan atau sudah tidak aktif.',
            'asset_id.required_if' => 'Pilih inventaris penerima kuota.',
            'asset_id.exists' => 'Inventaris tidak ditemukan atau sudah dipensiunkan.',
        ];
    }
}
