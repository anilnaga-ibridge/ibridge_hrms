<?php

namespace App\Services\EnterpriseTasks;

use Carbon\Carbon;
use App\Models\EnterpriseTasks\Label;

class NaturalLanguageParserService
{
    /**
     * Parse a quick add string into task attributes.
     *
     * @param string $text
     * @param int $companyId
     * @return array
     */
    public function parse(string $text, int $companyId): array
    {
        $originalText = $text;
        $title = $text;

        $priority = 'P3';
        $dueDate = null;
        $dueTime = null;
        $recurrence = 'none';
        $labelIds = [];
        $labelNames = [];

        // 1. Extract Priority (e.g., p1, p2, p3, p4, urgent, high, medium, low)
        if (preg_match('/\b(p[1-4])\b/i', $title, $matches)) {
            $priority = strtoupper($matches[1]);
            $title = preg_replace('/\b' . preg_quote($matches[0], '/') . '\b/i', '', $title);
        } elseif (preg_match('/\b(urgent|critical)\b/i', $title, $matches)) {
            $priority = 'P1';
            $title = preg_replace('/\b' . preg_quote($matches[0], '/') . '\b/i', '', $title);
        } elseif (preg_match('/\b(high)\b/i', $title, $matches)) {
            $priority = 'P2';
            $title = preg_replace('/\b' . preg_quote($matches[0], '/') . '\b/i', '', $title);
        } elseif (preg_match('/\b(medium)\b/i', $title, $matches)) {
            $priority = 'P3';
            $title = preg_replace('/\b' . preg_quote($matches[0], '/') . '\b/i', '', $title);
        } elseif (preg_match('/\b(low)\b/i', $title, $matches)) {
            $priority = 'P4';
            $title = preg_replace('/\b' . preg_quote($matches[0], '/') . '\b/i', '', $title);
        }

        // 2. Extract Hashtag Labels (e.g., #Backend, #Frontend)
        if (preg_match_all('/#([a-z0-9_-]+)/i', $title, $matches)) {
            foreach ($matches[1] as $index => $labelName) {
                $labelNames[] = $labelName;
                $title = str_replace($matches[0][$index], '', $title);
            }
        }

        // 3. Extract Recurrence
        if (preg_match('/\bevery\s+(day|weekday|week|month|year)\b/i', $title, $matches)) {
            $freqMap = [
                'day' => 'daily',
                'weekday' => 'weekdays',
                'week' => 'weekly',
                'month' => 'monthly',
                'year' => 'yearly'
            ];
            $recurrence = $freqMap[strtolower($matches[1])] ?? 'none';
            $title = preg_replace('/' . preg_quote($matches[0], '/') . '/i', '', $title);
        } elseif (preg_match('/\b(daily|weekly|monthly|yearly)\b/i', $title, $matches)) {
            $recurrence = strtolower($matches[1]);
            $title = preg_replace('/\b' . preg_quote($matches[0], '/') . '\b/i', '', $title);
        }

        // 4. Extract Date / Time (today, tomorrow, next monday, etc.)
        $now = Carbon::now();
        
        // Time matching (e.g., at 5pm, at 5:30pm, 5pm, 17:00)
        if (preg_match('/\bat\s+(\d{1,2})(?::(\d{2}))?\s*(am|pm)?\b/i', $title, $matches)) {
            $hour = (int)$matches[1];
            $minute = isset($matches[2]) ? (int)$matches[2] : 0;
            $ampm = isset($matches[3]) ? strtolower($matches[3]) : null;

            if ($ampm === 'pm' && $hour < 12) $hour += 12;
            if ($ampm === 'am' && $hour === 12) $hour = 0;

            $dueTime = sprintf('%02d:%02d:00', $hour, $minute);
            $title = preg_replace('/' . preg_quote($matches[0], '/') . '/i', '', $title);
        } elseif (preg_match('/\b(\d{1,2})(?::(\d{2}))?\s*(am|pm)\b/i', $title, $matches)) {
            $hour = (int)$matches[1];
            $minute = isset($matches[2]) ? (int)$matches[2] : 0;
            $ampm = strtolower($matches[3]);

            if ($ampm === 'pm' && $hour < 12) $hour += 12;
            if ($ampm === 'am' && $hour === 12) $hour = 0;

            $dueTime = sprintf('%02d:%02d:00', $hour, $minute);
            $title = preg_replace('/\b' . preg_quote($matches[0], '/') . '\b/i', '', $title);
        }

        // Date matching
        if (preg_match('/\btomorrow\b/i', $title, $matches)) {
            $dueDate = $now->copy()->addDay()->format('Y-m-d');
            $title = preg_replace('/\b' . preg_quote($matches[0], '/') . '\b/i', '', $title);
        } elseif (preg_match('/\btoday\b/i', $title, $matches)) {
            $dueDate = $now->format('Y-m-d');
            $title = preg_replace('/\b' . preg_quote($matches[0], '/') . '\b/i', '', $title);
        } elseif (preg_match('/\bnext\s+(monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/i', $title, $matches)) {
            $dayOfWeek = strtolower($matches[1]);
            $dueDate = $now->copy()->next($dayOfWeek)->format('Y-m-d');
            $title = preg_replace('/' . preg_quote($matches[0], '/') . '/i', '', $title);
        } elseif (preg_match('/\b(monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/i', $title, $matches)) {
            $dayOfWeek = strtolower($matches[1]);
            // If it's today, we might mean next week, but default to next occurrence (which could be next week if already passed today)
            $dueDate = $now->copy()->next($dayOfWeek)->format('Y-m-d');
            $title = preg_replace('/\b' . preg_quote($matches[0], '/') . '\b/i', '', $title);
        } elseif (preg_match('/\b(?:on\s+)?([a-z]+)\s+(\d{1,2})\b/i', $title, $matches)) {
            // Month and day, e.g., "July 10" or "on July 10"
            try {
                $parsedDate = Carbon::parse($matches[1] . ' ' . $matches[2]);
                if ($parsedDate->isPast() && $parsedDate->diffInDays($now) > 1) {
                    $parsedDate->addYear();
                }
                $dueDate = $parsedDate->format('Y-m-d');
                $title = preg_replace('/' . preg_quote($matches[0], '/') . '/i', '', $title);
            } catch (\Exception $e) {
                // Ignore parsing errors
            }
        }

        // Remove any left-over assignee mentions (@name) or hashes/tags
        $title = preg_replace('/\s*@\w*\b/', '', $title);
        $title = preg_replace('/\s*[#@]\s*$/', '', $title);

        // 5. Clean up title
        $title = trim(preg_replace('/\s+/', ' ', $title));
        if (empty($title)) {
            $title = $originalText;
        }

        // 6. Query matching labels from Database
        if (!empty($labelNames)) {
            $labelIds = Label::where('company_id', $companyId)
                ->whereIn('name', $labelNames)
                ->pluck('id')
                ->toArray();
        }

        // Fallback: look for label names in title words if no hashtag was matched
        if (empty($labelIds)) {
            $allLabels = Label::where('company_id', $companyId)->get();
            foreach ($allLabels as $lbl) {
                if (preg_match('/\b' . preg_quote($lbl->name, '/') . '\b/i', $title)) {
                    $labelIds[] = $lbl->id;
                    // Optional: remove label name from title
                    $title = preg_replace('/\b' . preg_quote($lbl->name, '/') . '\b/i', '', $title);
                }
            }
            $title = trim(preg_replace('/\s+/', ' ', $title));
        }

        // Wrap labels into xids
        $labelXids = array_map(function($id) {
            return \Vinkla\Hashids\Facades\Hashids::encode($id);
        }, $labelIds);

        return [
            'title' => $title,
            'due_date' => $dueDate,
            'due_time' => $dueTime,
            'recurrence' => $recurrence,
            'priority' => $priority,
            'labels' => $labelXids
        ];
    }
}
