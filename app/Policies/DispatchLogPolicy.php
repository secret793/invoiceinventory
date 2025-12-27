<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DispatchLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class DispatchLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer']);
    }

    public function view(User $user, DispatchLog $dispatchLog): bool
    {
        return $user->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer']);
    }

    public function update(User $user, DispatchLog $dispatchLog): bool
    {
        return $user->hasRole(['Super Admin', 'Warehouse Manager']);
    }

    public function delete(User $user, DispatchLog $dispatchLog): bool
    {
        return $user->hasRole(['Super Admin', 'Warehouse Manager']);
    }
}
