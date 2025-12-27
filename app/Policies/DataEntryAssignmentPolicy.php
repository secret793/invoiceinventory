<?php

namespace App\Policies;

use App\Models\DataEntryAssignment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DataEntryAssignmentPolicy
{
    use HandlesAuthorization;

    public function view(User $user, DataEntryAssignment $dataEntryAssignment): bool
    {
        // Allow access for Super Admin, Warehouse Manager, and Data Entry Officer
        if ($user->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer'])) {
            return true;
        }

        return false;
    }

    public function viewDispatchReport(User $user): bool
    {
        // Allow access for Super Admin, Warehouse Manager, and Data Entry Officer
        return $user->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer']);
    }
}