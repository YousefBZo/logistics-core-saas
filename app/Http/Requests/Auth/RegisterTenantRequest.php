<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:150'],
            // The company's sub-link must be completely unique to the platform
            'subdomain' => ['required', 'string', 'alpha_dash', 'max:60', 'unique:tenants,subdomain'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
