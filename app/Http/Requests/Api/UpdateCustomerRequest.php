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

    /**
     * Normalize the phone to digits only — the same canonical form
     * checkout stores — so the unique rule compares like with like.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone') && $this->input('phone') !== null) {
            $this->merge(['phone' => preg_replace('/\D+/', '', (string) $this->input('phone'))]);
        }
    }
}
