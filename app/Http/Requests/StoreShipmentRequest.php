<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => [
                'nullable',
                'integer',
                Rule::exists('warehouses', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $this->user()?->tenant_id)
                ),
            ],
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:100'],
            'area_or_zone' => ['required', 'string', 'max:100'],
            'detailed_address' => ['required', 'string', 'max:1000'],
            'customer_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'customer_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'cod_amount' => ['nullable', 'numeric', 'min:0'],
            'delivery_fees' => ['nullable', 'numeric', 'min:0'],
            'weight_kg' => ['nullable', 'numeric', 'min:0.01', 'max:999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
