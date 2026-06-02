<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Traits\HasBitwisePermissions;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

#[ScopedBy([TenantScope::class])] // Native injection of the Tenant security scope
class User extends Authenticatable
{
    use HasApiTokens, HasBitwisePermissions, HasFactory;

    protected $fillable = ['tenant_id', 'name', 'email', 'phone', 'password', 'permissions_mask', 'status'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'permissions_mask' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function merchantProfile(): HasOne
    {
        return $this->hasOne(MerchantProfile::class);
    }

    public function merchantShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'merchant_id');
    }

    public function driverShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'driver_id');
    }
}
