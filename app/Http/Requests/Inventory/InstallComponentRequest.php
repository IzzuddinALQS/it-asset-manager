<?php

namespace App\Http\Requests\Inventory;

use App\Models\Component;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InstallComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('install', Component::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'component_id' => ['required', 'integer', Rule::exists('components', 'id')->whereNull('deleted_at')],
            'qty' => ['required', 'integer', 'min:1', 'max:1000'],
            'is_factory' => ['boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'component_id.required' => 'Pilih komponen yang dipasang.',
            'component_id.exists' => 'Komponen tidak ditemukan.',
            'qty.min' => 'Jumlah minimal 1.',
        ];
    }
}
