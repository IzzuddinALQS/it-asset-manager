<?php

namespace App\Http\Requests\Assets;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckinAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $asset = $this->route('asset');

        return $asset instanceof Asset && $this->user()?->can('checkin', $asset) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'to_status' => ['required', 'string', Rule::in(['ready', 'maintenance'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'to_status.required' => 'Pilih kondisi inventaris setelah dikembalikan.',
            'to_status.in' => 'Kondisi inventaris hanya boleh "Siap Guna" atau "Perbaikan".',
        ];
    }
}
