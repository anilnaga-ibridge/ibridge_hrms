<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use Examyou\RestAPI\ApiResponse;

class NotificationController extends ApiBaseController
{
    public function index()
    {
        $request = request();
        $user = user();
        
        // Paginate notifications (default 10)
        $limit = $request->input('limit', 10);
        
        $notificationsQuery = $user->notifications()->orderBy('created_at', 'desc');
        
        $unreadCount = $user->unreadNotifications()->count();
        
        $notifications = $notificationsQuery->paginate($limit);
        
        $items = collect($notifications->items())->map(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'data' => $notification->data,
                'read_at' => $notification->read_at ? $notification->read_at->toIso8601String() : null,
                'created_at' => $notification->created_at->toIso8601String(),
            ];
        });
        
        return ApiResponse::make('Notifications fetched successfully', [
            'notifications' => $items,
            'unread_count' => $unreadCount,
            'total' => $notifications->total(),
            'current_page' => $notifications->currentPage(),
            'last_page' => $notifications->lastPage(),
        ]);
    }

    public function markRead()
    {
        $request = request();
        $user = user();
        $notificationId = $request->input('id');

        if ($notificationId) {
            $notification = $user->notifications()->where('id', $notificationId)->first();
            if ($notification) {
                $notification->markAsRead();
            }
        } else {
            $user->unreadNotifications->markAsRead();
        }

        return ApiResponse::make('Notifications marked as read successfully', [
            'unread_count' => $user->unreadNotifications()->count()
        ]);
    }
}
