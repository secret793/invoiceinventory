<?php

namespace App\Observers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationObserver
{
    public function creating(Notification $notification): void
    {
        if (empty($notification->user_id) && Auth::check()) {
            $notification->user_id = Auth::id();
        }
    }

    public function created(Notification $notification): void
    {
        // Broadcast the notification if needed
        // broadcast(new NotificationCreated($notification))->toOthers();
    }
}
