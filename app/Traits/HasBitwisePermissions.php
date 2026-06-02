<?php

namespace App\Traits;

use App\Enums\Permission;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $permissions_mask
 */
trait HasBitwisePermissions
{
    // Bitwise OR (|) Gate: Grant multiple permissions simultaneously without mutating existing flags
    public function grantPermissions(Permission ...$permissions): void
    {
        $mask = $this->permissions_mask;
        foreach ($permissions as $permission) {
            $mask |= $permission->value;
        }
        $this->update(['permissions_mask' => $mask]);
    }

    // Bitwise AND (&) Gate: Instantaneous O(1) evaluation to verify if a specific bit flag is set
    public function hasPermission(Permission $permission): bool
    {
        return ($this->permissions_mask & $permission->value) === $permission->value;
    }

    // Bitwise NOT (~) and AND (&) Gates: Revoke and deactivate a specific bit flag within the mask
    public function revokePermission(Permission $permission): void
    {
        $this->update([
            'permissions_mask' => $this->permissions_mask & ~$permission->value,
        ]);
    }

    // Custom Eloquent scope for direct, high-performance bitwise verification at the database layer without loops
    public function scopeWhereHasPermission(Builder $query, Permission $permission): Builder
    {
        return $query->whereRaw('(permissions_mask & ?) = ?', [$permission->value, $permission->value]);
    }
}
