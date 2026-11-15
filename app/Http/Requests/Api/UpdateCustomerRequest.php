<?php

// app/Http/Requests/Api/UpdateCustomerRequest.php

namespace App\Http\Requests\Api;

use App\Models\GlobalSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32', Rule::unique('customers', 'phone')->ignore($this->user()?->id)],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'suburb' => ['sometimes', 'nullable', Rule::in(GlobalSetting::current()->delivery_suburbs)],
        ];
    }
}
