<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Notification;

class NotificationController
{
    public function index(Request $req): void
    {
        $page    = max(1, (int) $req->query('page', 1));
        $perPage = min(100, max(1, (int) $req->query('per_page', 25)));
        $filter  = $req->query('filter');
        $result  = Notification::listPaginated($page, $perPage, $filter);
        Response::paginated($result['data'], $result['total'], $page, $perPage);
    }

    public function unreadCount(Request $req): void
    {
        Response::success(['count' => Notification::unreadCount()]);
    }

    public function bulkRead(Request $req): void
    {
        $ids = $req->input('ids', []);
        if (!$ids) Response::error('ids are required');
        Notification::markRead($ids);
        Response::success(null, 'Marked as read');
    }

    public function bulkUnread(Request $req): void
    {
        $ids = $req->input('ids', []);
        if (!$ids) Response::error('ids are required');
        Notification::markUnread($ids);
        Response::success(null, 'Marked as unread');
    }

    public function markRead(Request $req): void
    {
        Notification::markRead([(int) $req->param('id')]);
        Response::success(null, 'Marked as read');
    }

    public function markUnread(Request $req): void
    {
        Notification::markUnread([(int) $req->param('id')]);
        Response::success(null, 'Marked as unread');
    }
}
