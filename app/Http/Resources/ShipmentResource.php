<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Shipment */
final class ShipmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tracking_number' => $this->tracking_number,
            'status' => $this->status,
            'warehouse_id' => $this->warehouse_id,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'city' => $this->city,
            'area_or_zone' => $this->area_or_zone,
            'cod_amount' => $this->cod_amount,
            'delivery_fees' => $this->delivery_fees,
            'weight_kg' => $this->weight_kg,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
