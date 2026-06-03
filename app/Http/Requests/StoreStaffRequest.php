<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Actual authorization checks are handled by middleware.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')],
            'role_type' => ['required', 'string', Rule::in(['driver', 'warehouse_manager'])],
        ];
    }
}
