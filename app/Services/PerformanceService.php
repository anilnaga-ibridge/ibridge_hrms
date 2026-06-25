<?php

namespace App\Services;

use App\Models\Appreciation;
use App\Models\Attendance;
use App\Models\Complaint;
use App\Models\EmployeePerformanceScore;
use App\Models\Feedback;
use App\Models\FeedbackUser;
use App\Models\StaffMember;
use App\Models\Task;
use App\Models\Warning;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PerformanceService
{
    public const WEIGHTS = [
        'attendance' => 0.15,
        'productivity' => 0.25,
        'communication' => 0.15,
        'leadership' => 0.10,
        'discipline' => 0.10,
        'teamwork' => 0.10,
        'task_completion' => 0.15,
    ];

    public function calculateForEmployee(int $userId, int $month, int $year): EmployeePerformanceScore
    {
        $companyId = $this->getCompanyId($userId);

        $attendanceScore = $this->calculateAttendanceScore($userId, $month, $year);
        $productivityScore = $this->calculateProductivityScore($userId, $month, $year);
        $communicationScore = $this->calculateCommunicationScore($userId, $month, $year);
        $leadershipScore = $this->calculateLeadershipScore($userId, $month, $year);
        $disciplineScore = $this->calculateDisciplineScore($userId, $month, $year);
        $teamworkScore = $this->calculateTeamworkScore($userId, $month, $year);
        $taskCompletionScore = $this->calculateTaskCompletionScore($userId, $month, $year);

        $overallScore = collect([
            $attendanceScore * self::WEIGHTS['attendance'],
            $productivityScore * self::WEIGHTS['productivity'],
            $communicationScore * self::WEIGHTS['communication'],
            $leadershipScore * self::WEIGHTS['leadership'],
            $disciplineScore * self::WEIGHTS['discipline'],
            $teamworkScore * self::WEIGHTS['teamwork'],
            $taskCompletionScore * self::WEIGHTS['task_completion'],
        ])->sum();

        $overallScore = round($overallScore, 2);
        $grade = $this->getGrade($overallScore);

        $score = EmployeePerformanceScore::updateOrCreate(
            [
                'user_id' => $userId,
                'month' => $month,
                'year' => $year,
            ],
            [
                'company_id' => $companyId,
                'attendance_score' => $attendanceScore,
                'productivity_score' => $productivityScore,
                'communication_score' => $communicationScore,
                'leadership_score' => $leadershipScore,
                'discipline_score' => $disciplineScore,
                'teamwork_score' => $teamworkScore,
                'task_completion_score' => $taskCompletionScore,
                'overall_score' => $overallScore,
                'grade' => $grade,
            ]
        );

        $this->updateRanks($month, $year, $companyId);

        return $score;
    }

    public function calculateForAll(int $month, int $year): void
    {
        $employees = StaffMember::where('user_type', 'staff_members')
            ->where('status', 'active')
            ->get();

        foreach ($employees as $employee) {
            $this->calculateForEmployee($employee->id, $month, $year);
        }
    }

    public function calculateAttendanceScore(int $userId, int $month, int $year): float
    {
        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

        $weekends = $this->getWeekendCount($year, $month);
        $holidays = $this->getHolidayCount($year, $month);
        $workingDays = $daysInMonth - $weekends - $holidays;

        if ($workingDays <= 0) {
            return 100;
        }

        $presentDays = Attendance::where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('status', 'present')
            ->where('is_holiday', false)
            ->where('is_leave', false)
            ->count();

        $attendancePercent = ($presentDays / $workingDays) * 100;

        return round(min($attendancePercent, 100), 2);
    }

    public function calculateProductivityScore(int $userId, int $month, int $year): float
    {
        $assignedTasks = Task::whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->where(function ($q) use ($userId) {
                $q->whereJsonContains('assignees', (string) $userId)
                    ->orWhere('created_by', $userId);
            })
            ->count();

        if ($assignedTasks === 0) {
            $totalFeedbacks = FeedbackUser::where('user_id', $userId)
                ->where('feedback_given', 1)
                ->count();

            if ($totalFeedbacks > 0) {
                $avgRating = FeedbackUser::where('user_id', $userId)
                    ->where('feedback_given', 1)
                    ->avg('rating');

                return round(min(($avgRating / 5) * 100, 100), 2);
            }

            return 75;
        }

        $completedTasks = Task::whereIn('status', ['completed', 'done', 'finished'])
            ->whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->where(function ($q) use ($userId) {
                $q->whereJsonContains('assignees', (string) $userId)
                    ->orWhere('created_by', $userId);
            })
            ->count();

        $productivity = ($completedTasks / $assignedTasks) * 100;

        return round(min($productivity, 100), 2);
    }

    public function calculateCommunicationScore(int $userId, int $month, int $year): float
    {
        $score = 0;
        $count = 0;

        $feedbackRatings = FeedbackUser::where('user_id', $userId)
            ->where('feedback_given', 1)
            ->whereYear('submit_date', $year)
            ->whereMonth('submit_date', $month)
            ->get();

        foreach ($feedbackRatings as $feedback) {
            $details = $feedback->rating_details;

            if (is_array($details)) {
                foreach ($details as $section) {
                    if (isset($section['fields']) && is_array($section['fields'])) {
                        foreach ($section['fields'] as $field) {
                            $fieldName = strtolower($field['name'] ?? '');
                            if (str_contains($fieldName, 'communicat') ||
                                str_contains($fieldName, 'presentation') ||
                                str_contains($fieldName, 'knowledge') ||
                                str_contains($fieldName, 'responsive')) {
                                $score += $field['rating_details'] ?? 0;
                                $count++;
                            }
                        }
                    }
                }
            }
        }

        if ($count === 0) {
            // Fallback: use average rating for this user scoped to the same month,
            // so historical feedback from other months does not influence this score.
            $avgRating = FeedbackUser::where('user_id', $userId)
                ->where('feedback_given', 1)
                ->whereYear('submit_date', $year)
                ->whereMonth('submit_date', $month)
                ->avg('rating');

            if ($avgRating) {
                return round(min(($avgRating / 5) * 100, 100), 2);
            }

            return 75;
        }

        $avgScore = ($score / $count) / 5 * 100;

        return round(min($avgScore, 100), 2);
    }

    public function calculateLeadershipScore(int $userId, int $month, int $year): float
    {
        $score = 0;
        $count = 0;

        $feedbackRatings = FeedbackUser::where('user_id', $userId)
            ->where('feedback_given', 1)
            ->whereYear('submit_date', $year)
            ->whereMonth('submit_date', $month)
            ->get();

        foreach ($feedbackRatings as $feedback) {
            $details = $feedback->rating_details;

            if (is_array($details)) {
                foreach ($details as $section) {
                    if (isset($section['fields']) && is_array($section['fields'])) {
                        foreach ($section['fields'] as $field) {
                            $fieldName = strtolower($field['name'] ?? '');
                            if (str_contains($fieldName, 'initiative') ||
                                str_contains($fieldName, 'decision') ||
                                str_contains($fieldName, 'problem solv') ||
                                str_contains($fieldName, 'mentor') ||
                                str_contains($fieldName, 'ownership')) {
                                $score += $field['rating_details'] ?? 0;
                                $count++;
                            }
                        }
                    }
                }
            }
        }

        $appreciationBonus = Appreciation::where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->count();

        if ($count === 0) {
            $baseScore = 70;
        } else {
            $baseScore = ($score / $count) / 5 * 100;
        }

        $bonusPoints = min($appreciationBonus * 5, 15);
        $finalScore = min($baseScore + $bonusPoints, 100);

        return round($finalScore, 2);
    }

    public function calculateDisciplineScore(int $userId, int $month, int $year): float
    {
        $warningsCount = Warning::where('user_id', $userId)
            ->whereYear('warning_date', $year)
            ->whereMonth('warning_date', $month)
            ->count();

        $complaintsCount = Complaint::where('to_user_id', $userId)
            ->whereYear('date_time', $year)
            ->whereMonth('date_time', $month)
            ->count();

        $lateDays = Attendance::where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('is_late', true)
            ->count();

        $absentDays = Attendance::where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('is_leave', true)
            ->where('is_paid', false)
            ->count();

        $totalIssues = ($warningsCount * 15) + ($complaintsCount * 10) + ($lateDays * 5) + ($absentDays * 8);
        $score = max(100 - $totalIssues, 0);

        return round($score, 2);
    }

    public function calculateTeamworkScore(int $userId, int $month, int $year): float
    {
        $score = 0;
        $count = 0;

        $feedbackRatings = FeedbackUser::where('user_id', $userId)
            ->where('feedback_given', 1)
            ->whereYear('submit_date', $year)
            ->whereMonth('submit_date', $month)
            ->get();

        foreach ($feedbackRatings as $feedback) {
            $details = $feedback->rating_details;

            if (is_array($details)) {
                foreach ($details as $section) {
                    if (isset($section['fields']) && is_array($section['fields'])) {
                        foreach ($section['fields'] as $field) {
                            $fieldName = strtolower($field['name'] ?? '');
                            if (str_contains($fieldName, 'collaborat') ||
                                str_contains($fieldName, 'team') ||
                                str_contains($fieldName, 'help') ||
                                str_contains($fieldName, 'cross') ||
                                str_contains($fieldName, 'knowledge sharing')) {
                                $score += $field['rating_details'] ?? 0;
                                $count++;
                            }
                        }
                    }
                }
            }
        }

        if ($count === 0) {
            // Fallback: use average rating for this user scoped to the same month,
            // so historical feedback from other months does not influence this score.
            $avgRating = FeedbackUser::where('user_id', $userId)
                ->where('feedback_given', 1)
                ->whereYear('submit_date', $year)
                ->whereMonth('submit_date', $month)
                ->avg('rating');

            if ($avgRating) {
                return round(min(($avgRating / 5) * 100, 100), 2);
            }

            return 75;
        }

        $avgScore = ($score / $count) / 5 * 100;

        return round(min($avgScore, 100), 2);
    }

    public function calculateTaskCompletionScore(int $userId, int $month, int $year): float
    {
        $assignedTasks = Task::whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->where(function ($q) use ($userId) {
                $q->whereJsonContains('assignees', (string) $userId)
                    ->orWhere('created_by', $userId);
            })
            ->count();

        if ($assignedTasks === 0) {
            // Fallback: check feedback only for the scored month so that
            // historical feedback from other periods does not alter the fallback.
            $feedbackCount = FeedbackUser::where('user_id', $userId)
                ->where('feedback_given', 1)
                ->whereYear('submit_date', $year)
                ->whereMonth('submit_date', $month)
                ->count();

            if ($feedbackCount > 0) {
                return 80;
            }

            return 75;
        }

        $completedTasks = Task::whereIn('status', ['completed', 'done', 'finished'])
            ->whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->where(function ($q) use ($userId) {
                $q->whereJsonContains('assignees', (string) $userId)
                    ->orWhere('created_by', $userId);
            })
            ->count();

        $completedStatuses = ['completed', 'done', 'finished'];
        $overdueTasks = Task::where('due_date', '<', Carbon::now())
            ->whereNotIn('status', $completedStatuses)
            ->whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->where(function ($q) use ($userId) {
                $q->whereJsonContains('assignees', (string) $userId)
                    ->orWhere('created_by', $userId);
            })
            ->count();

        $completionRate = ($completedTasks / $assignedTasks) * 100;
        $overduePenalty = $overdueTasks * 5;

        $finalScore = max($completionRate - $overduePenalty, 0);

        return round(min($finalScore, 100), 2);
    }

    public function getGrade(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A+',
            $score >= 80 => 'A',
            $score >= 70 => 'B',
            $score >= 60 => 'C',
            default => 'D',
        };
    }

    public function getDepartmentId(int $userId): ?int
    {
        $user = StaffMember::with('department')->find($userId);
        return $user?->department?->id;
    }

    public function updateRanks(int $month, int $year, ?int $companyId = null): void
    {
        $query = EmployeePerformanceScore::where('month', $month)
            ->where('year', $year);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $allScores = $query->orderBy('overall_score', 'desc')->get();

        $companyRank = 1;
        $departmentRanks = [];

        foreach ($allScores as $score) {
            $score->company_rank = $companyRank++;
            $score->save();

            $userDepartment = $this->getDepartmentId($score->user_id);

            if ($userDepartment) {
                if (!isset($departmentRanks[$userDepartment])) {
                    $departmentRanks[$userDepartment] = 1;
                }

                EmployeePerformanceScore::where('id', $score->id)
                    ->update(['department_rank' => $departmentRanks[$userDepartment]++]);
            }
        }
    }

    private function getWeekendCount(int $year, int $month): int
    {
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $weekends = 0;

        while ($startDate->lte($endDate)) {
            if ($startDate->isSaturday() || $startDate->isSunday()) {
                $weekends++;
            }
            $startDate->addDay();
        }

        return $weekends;
    }

    private function getHolidayCount(int $year, int $month): int
    {
        return DB::table('holidays')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('is_weekend', false)
            ->count();
    }

    private function getCompanyId(int $userId): ?int
    {
        $user = StaffMember::find($userId);
        return $user?->company_id;
    }

    public function getDashboardData(int $userId, int $month, int $year): array
    {
        $score = EmployeePerformanceScore::where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        $percentile = null;
        if ($score) {
            $totalCount = EmployeePerformanceScore::where('month', $month)
                ->where('year', $year)
                ->where('company_id', $score->company_id)
                ->count();
            if ($totalCount > 0) {
                $betterCount = EmployeePerformanceScore::where('month', $month)
                    ->where('year', $year)
                    ->where('company_id', $score->company_id)
                    ->where('overall_score', '>', $score->overall_score)
                    ->count();
                $topPercentage = ($betterCount / $totalCount) * 100;
                $percentile = max(round($topPercentage, 0), 1);
            }
        }

        return [
            'current' => $score,
            'percentile' => $percentile,
            'trend' => $this->getTrend($userId, $year),
            'kpis' => $this->getKpiBreakdown($userId, $month, $year),
            'appreciations' => $this->getMonthlyAppreciations($userId, $year),
            'tasks' => $this->getTaskAnalytics($userId, $month, $year),
            'attendance' => $this->getAttendanceAnalytics($userId, $month, $year),
            'strengths' => $this->getStrengths($userId, $month, $year),
        ];
    }

    public function getTrend(int $userId, int $year): array
    {
        return EmployeePerformanceScore::where('user_id', $userId)
            ->where('year', $year)
            ->orderBy('month')
            ->get(['month', 'overall_score'])
            ->toArray();
    }

    public function getKpiBreakdown(int $userId, int $month, int $year): array
    {
        $score = EmployeePerformanceScore::where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if (!$score) {
            return [];
        }

        return [
            ['label' => 'Attendance', 'value' => $score->attendance_score, 'weight' => self::WEIGHTS['attendance'] * 100],
            ['label' => 'Productivity', 'value' => $score->productivity_score, 'weight' => self::WEIGHTS['productivity'] * 100],
            ['label' => 'Communication', 'value' => $score->communication_score, 'weight' => self::WEIGHTS['communication'] * 100],
            ['label' => 'Leadership', 'value' => $score->leadership_score, 'weight' => self::WEIGHTS['leadership'] * 100],
            ['label' => 'Discipline', 'value' => $score->discipline_score, 'weight' => self::WEIGHTS['discipline'] * 100],
            ['label' => 'Teamwork', 'value' => $score->teamwork_score, 'weight' => self::WEIGHTS['teamwork'] * 100],
            ['label' => 'Task Completion', 'value' => $score->task_completion_score, 'weight' => self::WEIGHTS['task_completion'] * 100],
        ];
    }

    public function getTaskAnalytics(int $userId, int $month, int $year): array
    {
        $assigned = Task::whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->where(function ($q) use ($userId) {
                $q->whereJsonContains('assignees', (string) $userId)
                    ->orWhere('created_by', $userId);
            })
            ->count();

        $completed = Task::whereIn('status', ['completed', 'done', 'finished'])
            ->whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->where(function ($q) use ($userId) {
                $q->whereJsonContains('assignees', (string) $userId)
                    ->orWhere('created_by', $userId);
            })
            ->count();

        $completedStatuses = ['completed', 'done', 'finished'];
        $overdue = Task::where('due_date', '<', Carbon::now())
            ->whereNotIn('status', $completedStatuses)
            ->whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->where(function ($q) use ($userId) {
                $q->whereJsonContains('assignees', (string) $userId)
                    ->orWhere('created_by', $userId);
            })
            ->count();

        $pending = $assigned - $completed;

        return [
            'assigned' => $assigned,
            'completed' => $completed,
            'pending' => max($pending, 0),
            'overdue' => $overdue,
        ];
    }

    public function getAttendanceAnalytics(int $userId, int $month, int $year): array
    {
        $present = Attendance::where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('status', 'present')
            ->count();

        $absent = Attendance::where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('status', 'on_leave')
            ->where('is_paid', false)
            ->count();

        $lop = Attendance::where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('is_leave', true)
            ->where('is_paid', false)
            ->count();

        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $weekends = $this->getWeekendCount($year, $month);
        $holidays = $this->getHolidayCount($year, $month);
        $workingDays = $daysInMonth - $weekends - $holidays;

        return [
            'present' => $present,
            'absent' => $absent,
            'lop' => $lop,
            'working_days' => $workingDays,
            'percentage' => $workingDays > 0 ? round(($present / $workingDays) * 100, 2) : 100,
        ];
    }

    public function getMonthlyAppreciations(int $userId, int $year): array
    {
        $appreciations = Appreciation::select(
            DB::raw('MONTH(date) as month'),
            DB::raw('COUNT(*) as count')
        )
            ->where('user_id', $userId)
            ->whereYear('date', $year)
            ->groupBy(DB::raw('MONTH(date)'))
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $result = [];
        for ($m = 1; $m <= 12; $m++) {
            $result[] = $appreciations[$m] ?? 0;
        }

        return $result;
    }

    public function getTopPerformers(int $month, int $year, int $limit = 10): array
    {
        return EmployeePerformanceScore::with('user:id,name,profile_image,department_id')
            ->where('month', $month)
            ->where('year', $year)
            ->orderBy('overall_score', 'desc')
            ->take($limit)
            ->get()
            ->toArray();
    }

    public function getLowPerformers(int $month, int $year, int $limit = 10): array
    {
        return EmployeePerformanceScore::with('user:id,name,profile_image,department_id')
            ->where('month', $month)
            ->where('year', $year)
            ->where('overall_score', '<', 60)
            ->orderBy('overall_score')
            ->take($limit)
            ->get()
            ->toArray();
    }

    public function getDepartmentPerformance(int $month, int $year): array
    {
        return EmployeePerformanceScore::join('users', 'users.id', '=', 'employee_performance_scores.user_id')
            ->join('departments', 'departments.id', '=', 'users.department_id')
            ->where('employee_performance_scores.month', $month)
            ->where('employee_performance_scores.year', $year)
            ->select(
                'departments.id',
                'departments.name',
                DB::raw('AVG(employee_performance_scores.overall_score) as avg_score'),
                DB::raw('COUNT(employee_performance_scores.id) as employee_count'),
                DB::raw('MAX(employee_performance_scores.overall_score) as max_score'),
                DB::raw('MIN(employee_performance_scores.overall_score) as min_score')
            )
            ->groupBy('departments.id', 'departments.name')
            ->orderBy('avg_score', 'desc')
            ->get()
            ->toArray();
    }

    public function getScoreDistribution(int $month, int $year): array
    {
        $scores = EmployeePerformanceScore::where('month', $month)
            ->where('year', $year)
            ->selectRaw("
                SUM(CASE WHEN overall_score >= 90 THEN 1 ELSE 0 END) as a_plus,
                SUM(CASE WHEN overall_score >= 80 AND overall_score < 90 THEN 1 ELSE 0 END) as a,
                SUM(CASE WHEN overall_score >= 70 AND overall_score < 80 THEN 1 ELSE 0 END) as b,
                SUM(CASE WHEN overall_score >= 60 AND overall_score < 70 THEN 1 ELSE 0 END) as c,
                SUM(CASE WHEN overall_score < 60 THEN 1 ELSE 0 END) as d
            ")
            ->first();

        return [
            ['label' => 'A+', 'value' => (int) ($scores->a_plus ?? 0), 'color' => '#22c55e'],
            ['label' => 'A', 'value' => (int) ($scores->a ?? 0), 'color' => '#86efac'],
            ['label' => 'B', 'value' => (int) ($scores->b ?? 0), 'color' => '#fbbf24'],
            ['label' => 'C', 'value' => (int) ($scores->c ?? 0), 'color' => '#fb923c'],
            ['label' => 'D', 'value' => (int) ($scores->d ?? 0), 'color' => '#ef4444'],
        ];
    }

    public function getEmployeesNeedingTraining(int $month, int $year, int $threshold = 60): array
    {
        return EmployeePerformanceScore::with('user:id,name,profile_image,department_id')
            ->where('month', $month)
            ->where('year', $year)
            ->where('overall_score', '>=', $threshold)
            ->where('overall_score', '<', 70)
            ->orderBy('overall_score')
            ->take(15)
            ->get()
            ->toArray();
    }

    public function getEmployeesAtRisk(int $month, int $year, int $threshold = 50): array
    {
        return EmployeePerformanceScore::with('user:id,name,profile_image,department_id')
            ->where('month', $month)
            ->where('year', $year)
            ->where('overall_score', '<', $threshold)
            ->orderBy('overall_score')
            ->take(15)
            ->get()
            ->toArray();
    }

    public function getPromotionRecommendations(int $month, int $year): array
    {
        $threeMonthsAgo = $this->getThreeMonthsAgo($month, $year);

        return EmployeePerformanceScore::with('user:id,name,profile_image,designation_id,department_id')
            ->where('month', $month)
            ->where('year', $year)
            ->where('overall_score', '>=', 85)
            ->whereExists(function ($query) use ($threeMonthsAgo) {
                $query->select(DB::raw(1))
                    ->from('employee_performance_scores as eps2')
                    ->whereColumn('eps2.user_id', 'employee_performance_scores.user_id')
                    ->where('eps2.month', $threeMonthsAgo['month'])
                    ->where('eps2.year', $threeMonthsAgo['year'])
                    ->where('eps2.overall_score', '>=', 80);
            })
            ->orderBy('overall_score', 'desc')
            ->take(10)
            ->get()
            ->toArray();
    }

    public function getIncrementRecommendations(int $month, int $year): array
    {
        $threeMonthsAgo = $this->getThreeMonthsAgo($month, $year);

        return EmployeePerformanceScore::with('user:id,name,profile_image,designation_id,department_id,basic_salary')
            ->where('month', $month)
            ->where('year', $year)
            ->where('overall_score', '>=', 90)
            ->whereExists(function ($query) use ($threeMonthsAgo) {
                $query->select(DB::raw(1))
                    ->from('employee_performance_scores as eps2')
                    ->whereColumn('eps2.user_id', 'employee_performance_scores.user_id')
                    ->where('eps2.month', $threeMonthsAgo['month'])
                    ->where('eps2.year', $threeMonthsAgo['year'])
                    ->where('eps2.overall_score', '>=', 85);
            })
            ->orderBy('overall_score', 'desc')
            ->take(10)
            ->get()
            ->toArray();
    }

    public function getStrengths(int $userId, int $month, int $year): array
    {
        $score = EmployeePerformanceScore::where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if (!$score) {
            return [];
        }

        $categories = [
            'Attendance' => $score->attendance_score,
            'Productivity' => $score->productivity_score,
            'Communication' => $score->communication_score,
            'Leadership' => $score->leadership_score,
            'Discipline' => $score->discipline_score,
            'Teamwork' => $score->teamwork_score,
            'Task Completion' => $score->task_completion_score,
        ];

        arsort($categories);

        $strengths = [];
        $improvements = [];

        foreach ($categories as $name => $value) {
            if ($value >= 80) {
                $strengths[] = ['name' => $name, 'score' => $value];
            } elseif ($value < 60) {
                $improvements[] = ['name' => $name, 'score' => $value];
            }
        }

        return [
            'strengths' => $strengths,
            'improvements' => $improvements,
        ];
    }

    private function getThreeMonthsAgo(int $month, int $year): array
    {
        $date = Carbon::createFromDate($year, $month, 1)->subMonths(3);
        return ['month' => $date->month, 'year' => $date->year];
    }
}
