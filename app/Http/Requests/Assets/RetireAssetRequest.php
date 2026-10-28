<?php

namespace App\Http\Requests\Assets;

use App\Enums\RetireReason;
use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RetireAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $asset = $this->route('asset');

        return $asset instanceof Asset && $this->user()?->can('retire', $asset) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'retire_reason' => ['required', Rule::enum(RetireReason::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'retire_reason.required' => 'Alasan pensiun wajib dipilih.',
            'retire_reason.enum' => 'Alasan pensiun tidak dikenal.',
        ];
    }
}
