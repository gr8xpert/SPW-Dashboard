<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Use hasUser() to avoid infinite loop:
        // Auth::check() triggers EloquentUserProvider which queries User model
        // which boots this scope again → infinite recursion → memory exhaustion.
        // hasUser() only returns true when user is already resolved in memory.
        if (Auth::hasUser()) {
            $user = Auth::user();

            // Super admins bypass tenant scope
            if ($user->role === 'super_admin') {
                return;
            }

            $builder->where($model->getTable() . '.client_id', $user->client_id);
        }
    }
}
