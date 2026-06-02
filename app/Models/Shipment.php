<?php

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

    protected $guarded = ['id']; // Protect the primary key, leaving the remaining payload mass-assignable via dedicated Actions later

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
}
