<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\AllocationPoint;
use App\Models\DistributionPoint;
use App\Models\Notification;
use App\Services\PermissionService;

class SidebarController
{
    public function index(Request $req): void
    {
        $user = $req->user();

        $allocationPoints  = AllocationPoint::allWithCounts();
        $distributionPoints = DistributionPoint::allWithCounts();
        $unreadCount       = Notification::unreadCount();

        // Filter allocation points by user permission
        if (!PermissionService::isSuperAdmin($user) && !PermissionService::hasRole($user, 'Warehouse Manager')) {
            $permittedIds      = AllocationPoint::getPermittedForUser($user);
            $allocationPoints  = array_filter($allocationPoints, fn($ap) => in_array($ap['id'], $permittedIds));
            $allocationPoints  = array_values($allocationPoints);
        }

        Response::success([
            'allocation_points'   => $allocationPoints,
            'distribution_points' => $distributionPoints,
            'unread_notifications' => $unreadCount,
        ]);
    }
}
