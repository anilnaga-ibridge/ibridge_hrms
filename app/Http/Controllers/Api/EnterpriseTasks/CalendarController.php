<?php

namespace App\Http\Controllers\Api\EnterpriseTasks;

use App\Http\Controllers\Controller;
use App\Services\EnterpriseTasks\CalendarSyncService;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    protected CalendarSyncService $calendarSyncService;

    public function __construct(CalendarSyncService $calendarSyncService)
    {
        $this->calendarSyncService = $calendarSyncService;
    }

    /**
     * Download or subscribe to the user's tasks calendar feed.
     * Accessible publicly via token or via authenticated route.
     *
     * @param Request $request
     * @param string|null $token
     * @return \Illuminate\Http\Response
     */
    public function exportICS(Request $request, ?string $token = null)
    {
        // Fallback to query param if not in URL route
        $token = $token ?: $request->query('token');

        if (!$token) {
            return response('Unauthorized: Missing calendar token', 401);
        }

        try {
            $icsFeed = $this->calendarSyncService->generateUserICSFeed($token);

            return response($icsFeed)
                ->header('Content-Type', 'text/calendar; charset=utf-8')
                ->header('Content-Disposition', 'attachment; filename="tasks-calendar.ics"');
        } catch (\Exception $e) {
            return response('Error generating calendar feed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get the authenticated user's personal iCal subscription URL.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function icalSubscriptionUrl(Request $request)
    {
        $companyId = company()->id;
        $userId = auth('api')->id();

        $url = $this->calendarSyncService->getICalUrl($companyId, $userId);

        return response()->json([
            'ical_url' => $url
        ]);
    }
}
