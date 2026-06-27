<?php

namespace App\Services\EnterpriseTasks;

use App\Models\EnterpriseTasks\Task;
use Illuminate\Support\Collection;

class ICSFeedService
{
    /**
     * Generate an RFC 5545 compliant ICS string from a collection of tasks.
     *
     * @param Collection $tasks
     * @return string
     */
    public function generate(Collection $tasks): string
    {
        $ics = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//HRM Enterprise Tasks//NONSGML Calendar Sync//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
        ];

        foreach ($tasks as $task) {
            if (!$task->due_date) {
                continue;
            }

            $ics[] = 'BEGIN:VEVENT';
            $ics[] = 'UID:' . $task->xid . '@hrm.enterprise-tasks';
            
            // Format dates as Ymd\THis\Z or Ymd for all-day events
            $dueTime = $task->due_time ?: '00:00:00';
            $dateTime = \Carbon\Carbon::parse($task->due_date . ' ' . $dueTime)->setTimezone('UTC');
            
            if ($task->due_time) {
                $dtStart = $dateTime->format('Ymd\THis\Z');
                // Assume 1 hour duration by default if not specified
                $dtEnd = $dateTime->copy()->addHour()->format('Ymd\THis\Z');
                $ics[] = 'DTSTART:' . $dtStart;
                $ics[] = 'DTEND:' . $dtEnd;
            } else {
                // All-day event
                $dtStart = \Carbon\Carbon::parse($task->due_date)->format('Ymd');
                $dtEnd = \Carbon\Carbon::parse($task->due_date)->addDay()->format('Ymd');
                $ics[] = 'DTSTART;VALUE=DATE:' . $dtStart;
                $ics[] = 'DTEND;VALUE=DATE:' . $dtEnd;
            }

            $ics[] = 'SUMMARY:' . $this->escapeString($task->title);
            $ics[] = 'DESCRIPTION:' . $this->escapeString($task->description ?: 'No description provided.');
            $ics[] = 'STATUS:' . ($task->status === 'completed' ? 'COMPLETED' : 'NEEDS-ACTION');
            $ics[] = 'LAST-MODIFIED:' . $task->updated_at->setTimezone('UTC')->format('Ymd\THis\Z');
            $ics[] = 'DTSTAMP:' . now()->setTimezone('UTC')->format('Ymd\THis\Z');
            $ics[] = 'END:VEVENT';
        }

        $ics[] = 'END:VCALENDAR';

        return implode("\r\n", $ics);
    }

    /**
     * Escape special characters for ICS format.
     */
    private function escapeString(?string $str): string
    {
        if (!$str) {
            return '';
        }
        $str = str_replace('\\', '\\\\', $str);
        $str = str_replace(';', '\;', $str);
        $str = str_replace(',', '\,', $str);
        $str = str_replace("\n", '\\n', $str);
        $str = str_replace("\r", '', $str);
        return $str;
    }
}
