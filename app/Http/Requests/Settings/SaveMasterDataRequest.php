<?php

namespace App\Http\Requests\Settings;

use App\Enums\CategoryType;
use App\Enums\MasterDataType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveMasterDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('tag_prefix')) {
            $this->merge(['tag_prefix' => strtoupper($this->string('tag_prefix')->trim()->toString())]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var MasterDataType $type */
        $type = $this->route('type');
        $ignoreId = $this->route('record');

        return match ($type) {
            MasterDataType::Categories => [
                'name' => [
                    'required', 'string', 'max:100',
                    Rule::unique('categories', 'name')->where('type', $this->string('type')->toString())->ignore($ignoreId),
                ],
                'type' => ['required', Rule::enum(CategoryType::class)],
                'tag_prefix' => ['nullable', 'string', 'max:10', 'alpha_num'],
            ],
            MasterDataType::Manufacturers => [
                'name' => ['required', 'string', 'max:150', Rule::unique('manufacturers', 'name')->ignore($ignoreId)],
            ],
            MasterDataType::Suppliers => [
                'name' => ['required', 'string', 'max:150'],
                'contact_name' => ['nullable', 'string', 'max:150'],
                'phone' => ['nullable', 'string', 'max:50'],
                'email' => ['nullable', 'email', 'max:190'],
                'is_service_vendor' => ['boolean'],
            ],
            MasterDataType::Locations => [
                'name' => ['required', 'string', 'max:150'],
                'address' => ['nullable', 'string', 'max:1000'],
            ],
            MasterDataType::Models => [
                'name' => ['required', 'string', 'max:150'],
                'model_number' => ['nullable', 'string', 'max:100'],
                'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('type', CategoryType::Asset->value)],
                'manufacturer_id' => ['nullable', 'integer', Rule::exists('manufacturers', 'id')],
                'depreciation_months' => ['nullable', 'integer', 'min:1', 'max:600'],
            ],
        };
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Nama ini sudah dipakai.',
            'category_id.exists' => 'Pilih kategori inventaris yang valid.',
        ];
    }
}
