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

        // Attach computed slug to every AP so the frontend can do permission
        // filtering by matching view_allocationpoint_{slug} / view_data_entry_{slug}
        // without a second round-trip.
        $allocationPoints = array_map(function (array $ap): array {
            $ap['slug'] = AllocationPoint::slugify($ap['name']);
            return $ap;
        }, $allocationPoints);

        // For non-SA / non-WM users: return only APs the user can interact with.
        // We match BOTH view_allocationpoint_* (Allocation Officer)
        // AND view_data_entry_*  (Data Entry Officer) so that DEO users
        // also see their assigned APs in the sidebar.
        if (!PermissionService::isSuperAdmin($user) && !PermissionService::hasRole($user, 'Warehouse Manager')) {
            $perms = $user['permissions'] ?? [];

            $permittedIds = [];

            // view_allocationpoint_{slug} permissions
            $apIds = AllocationPoint::getPermittedForUser($user);
            foreach ($apIds as $id) {
                $permittedIds[$id] = true;
            }

            // view_data_entry_{slug} permissions
            foreach ($perms as $perm) {
                if (preg_match('/^view_data_entry_(.+)$/', $perm, $m)) {
                    $ap = \App\Core\Database::queryOne(
                        "SELECT id FROM allocation_points WHERE LOWER(REPLACE(name,' ','_')) = ?",
                        [$m[1]]
                    );
                    if ($ap) $permittedIds[(int) $ap['id']] = true;
                }
            }

            $allocationPoints = array_values(
                array_filter($allocationPoints, fn($ap) => isset($permittedIds[$ap['id']]))
            );
        }

        Response::success([
            'allocation_points'    => $allocationPoints,
            'distribution_points'  => $distributionPoints,
            'unread_notifications' => $unreadCount,
        ]);
    }
}
