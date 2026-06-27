<?php

namespace App\Events\EnterpriseTasks;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public int $userId;
    public array $notification;

    public function __construct(int $userId, array $notification)
    {
        $this->userId = $userId;
        $this->notification = $notification;
    }

    public function broadcastOn()
    {
        return ['user.' . $this->userId];
    }

    public function broadcastAs()
    {
        return 'notification.created';
    }
}
