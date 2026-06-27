<?php

namespace App\Http\Controllers\Api\EnterpriseTasks;

use App\Http\Controllers\Controller;
use App\Models\EnterpriseTasks\NotificationPreference;
use App\Services\EnterpriseTasks\NotificationService;
use App\Classes\Common;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function indexNotifications(Request $request)
    {
        $userId = auth('api')->id();
        $notifications = $this->notificationService->getNotifications($userId);
        return response()->json($notifications);
    }

    public function markNotificationsRead(Request $request)
    {
        $userId = auth('api')->id();
        $ids = array_map([Common::class, 'getIdFromHash'], $request->get('xids', []));
        if (empty($ids)) {
            $this->notificationService->markAllRead($userId);
        } else {
            $this->notificationService->markAsRead($userId, $ids);
        }
        return response()->json(['success' => true]);
    }

    public function indexNotificationPreferences(Request $request)
    {
        $userId = auth('api')->id();
        $prefs = NotificationPreference::where('user_id', $userId)->first();
        return response()->json($prefs);
    }

    public function saveNotificationPreferences(Request $request)
    {
        $companyId = company()->id;
        $userId = auth('api')->id();

        $prefs = NotificationPreference::updateOrCreate(
            ['company_id' => $companyId, 'user_id' => $userId],
            [
                'browser' => $request->boolean('browser', true),
                'email'   => $request->boolean('email', true),
                'push'    => $request->boolean('push', true),
                'digest'  => $request->boolean('digest', false),
            ]
        );

        return response()->json($prefs);
    }
}
