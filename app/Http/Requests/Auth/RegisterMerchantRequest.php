<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RegisterMerchantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('tenant_subdomain')) {
            $this->merge([
                'tenant_subdomain' => strtolower((string) $this->input('tenant_subdomain')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'tenant_subdomain' => ['required', 'string', 'max:63', Rule::exists('tenants', 'subdomain')],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'store_name' => ['required', 'string', 'max:150'],
            'pickup_address' => ['required', 'string', 'max:255'],
        ];
    }
}
