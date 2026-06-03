<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    public function apply(Builder $query, Model $model): void
    {
        if (! Auth::check()) {
            return;
        }

        $tenantId = Auth::user()->tenant_id;

        if (! $tenantId) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where($model->getTable().'.tenant_id', $tenantId);
    }
}
