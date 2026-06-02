<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    public function apply(Builder $query, Model $model): void
    {
        // If an authenticated user exists (merchant, driver, admin), automatically scope queries to their assigned tenant ID
        if (Auth::check() && Auth::user()->tenant_id) {
            $query->where($model->getTable().'.tenant_id', Auth::user()->tenant_id);
        }
    }
}
