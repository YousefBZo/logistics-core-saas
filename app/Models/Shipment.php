<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy([TenantScope::class])]
class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'merchant_id',
        'warehouse_id',
        'tracking_number',
        'status',
        'customer_name',
        'customer_phone',
        'city',
        'area_or_zone',
        'detailed_address',
        'customer_latitude',
        'customer_longitude',
        'cod_amount',
        'delivery_fees',
        'weight_kg',
        'notes',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ShipmentLog::class)->orderBy('created_at', 'desc');
    }

    public function assignDriver(int $driverId): void
    {
        $this->forceFill(['driver_id' => $driverId])->save();
    }
}
