<?php

namespace App\Http\Requests\Inventory;

use App\Models\AccessoryCheckout;
use Illuminate\Foundation\Http\FormRequest;

class CheckinAccessoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $checkout = $this->route('checkout');

        return $checkout instanceof AccessoryCheckout
            && $checkout->accessory !== null
            && $this->user()?->can('checkout', $checkout->accessory) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
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
            'qty.min' => 'Jumlah minimal 1.',
        ];
    }
}
