<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();
        $merchantId = null;

        if ($user !== null) {
            $merchantId = $this->isStaffOrAdminActor($user)
                ? $this->input('merchant_id')
                : $user->id;
        }

        $this->merge([
            'merchant_id' => $merchantId,
            'customer_name' => $this->normalizeString('customer_name'),
            'customer_phone' => $this->normalizeString('customer_phone'),
            'city' => $this->normalizeString('city'),
            'area_or_zone' => $this->normalizeString('area_or_zone'),
            'detailed_address' => $this->normalizeString('detailed_address'),
            'notes' => $this->normalizeString('notes'),
            'cod_amount' => $this->normalizeString('cod_amount'),
            'delivery_fees' => $this->normalizeString('delivery_fees'),
            'weight_kg' => $this->normalizeString('weight_kg'),
        ]);
    }

    public function rules(): array
    {
        $user = $this->user();
        $tenantId = $user?->tenant_id;
        $isStaffOrAdminActor = $this->isStaffOrAdminActor($user);
        $merchantRules = [
            $isStaffOrAdminActor ? 'required' : 'nullable',
            'integer',
            Rule::exists('users', 'id')->where(
                fn($query) => $query->where('tenant_id', $tenantId)
            ),
        ];

        if (! $isStaffOrAdminActor && $user !== null) {
            $merchantRules[] = Rule::in([(int) $user->id]);
        }

        return [
            'merchant_id' => $merchantRules,
            'warehouse_id' => [
                'nullable',
                'integer',
                Rule::exists('warehouses', 'id')->where(
                    fn($query) => $query->where('tenant_id', $tenantId)
                ),
            ],
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:100'],
            'area_or_zone' => ['required', 'string', 'max:100'],
            'detailed_address' => ['required', 'string', 'max:1000'],
            'customer_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'customer_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'cod_amount' => ['nullable', 'numeric', 'min:0', 'regex:/^\d{1,8}(\.\d{1,4})?$/'],
            'delivery_fees' => ['nullable', 'numeric', 'min:0'],
            'weight_kg' => ['nullable', 'numeric', 'min:0.01', 'max:999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'cod_amount.regex' => 'The cod_amount field must use precision 12,4 (up to 8 integer digits and 4 decimal places).',
        ];
    }

    private function normalizeString(string $key): mixed
    {
        $value = $this->input($key);

        if (! is_string($value)) {
            return $value;
        }

        $trimmedValue = trim($value);

        return $trimmedValue === '' ? null : $trimmedValue;
    }

    private function isStaffOrAdminActor(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasPermission(Permission::MANAGE_TENANT)
            || $user->hasPermission(Permission::ASSIGN_DRIVERS)
            || $user->hasPermission(Permission::SORT_PACKAGES)
            || $user->hasPermission(Permission::DELIVER_SHIPMENT);
    }
}
