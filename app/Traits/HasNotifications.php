<?php

namespace App\Traits;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;

trait HasNotifications
{
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    public function notify(string $message, string $type = 'info', array $data = [], string $action = null): void
    {
        // Create a unique key for this notification
        $cacheKey = sprintf(
            'notification:%s:%s:%s:%s',
            $this->getTable(),
            $this->id,
            $type,
            md5(json_encode($data))
        );

        // Check if a similar notification was created recently (within 5 minutes)
        if (!Cache::has($cacheKey)) {
            // Check for recent similar notifications in the database
            $recentNotification = $this->notifications()
                ->where('type', $type)
                ->where('message', $message)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->exists();

            if (!$recentNotification) {
                $this->notifications()->create([
                    'message' => $message,
                    'type' => $type,
                    'data' => $data,
                    'action' => $action,
                    'status' => 'unread'
                ]);

                // Cache the notification key for 5 minutes to prevent duplicates
                Cache::put($cacheKey, true, now()->addMinutes(5));
            }
        }
    }

    protected static function bootHasNotifications(): void
    {
        static::created(function ($model) {
            $model->notify(
                "New {$model->getTable()} record created",
                'created',
                ['id' => $model->id]
            );
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            unset($changes['updated_at']);
            
            if (!empty($changes)) {
                // Only notify about significant changes
                $significantChanges = array_diff_key($changes, array_flip(['created_at', 'updated_at']));
                if (!empty($significantChanges)) {
                    $model->notify(
                        "{$model->getTable()} record updated",
                        'updated',
                        ['changes' => $significantChanges]
                    );
                }
            }
        });

        static::deleted(function ($model) {
            $model->notify(
                "{$model->getTable()} record deleted",
                'deleted',
                ['id' => $model->id]
            );
        });
    }
}
