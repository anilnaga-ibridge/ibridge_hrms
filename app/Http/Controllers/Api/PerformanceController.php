<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\EmployeePerformanceScore;
use App\Models\StaffMember;
use App\Services\PerformanceService;
use Carbon\Carbon;
use Examyou\RestAPI\ApiResponse;
use Illuminate\Http\Request;

class PerformanceController extends ApiBaseController
{
    protected $model = EmployeePerformanceScore::class;

    public function dashboard($employeeId)
    {
        $request = request();
        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;

        $userId = $this->getIdFromHash($employeeId);

        $performanceService = app(PerformanceService::class);
        $data = $performanceService->getDashboardData($userId, (int) $month, (int) $year);

        return ApiResponse::make('Performance data fetched', $data);
    }

    public function trend($employeeId)
    {
        $request = request();
        $year = $request->year ?? Carbon::now()->year;

        $userId = $this->getIdFromHash($employeeId);

        $performanceService = app(PerformanceService::class);
        $trend = $performanceService->getTrend($userId, (int) $year);

        return ApiResponse::make('Performance trend fetched', [
            'trend' => $trend,
        ]);
    }

    public function kpis($employeeId)
    {
        $request = request();
        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;

        $userId = $this->getIdFromHash($employeeId);

        $performanceService = app(PerformanceService::class);
        $kpis = $performanceService->getKpiBreakdown($userId, (int) $month, (int) $year);
        $strengths = $performanceService->getStrengths($userId, (int) $month, (int) $year);

        return ApiResponse::make('KPI data fetched', [
            'kpis' => $kpis,
            'strengths' => $strengths,
        ]);
    }

    public function ranking()
    {
        $request = request();
        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;

        $scores = EmployeePerformanceScore::with('user:id,name,profile_image,department_id')
            ->where('month', (int) $month)
            ->where('year', (int) $year)
            ->orderBy('overall_score', 'desc')
            ->get();

        return ApiResponse::make('Ranking data fetched', $scores);
    }

    public function topPerformers()
    {
        $request = request();
        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;

        $performanceService = app(PerformanceService::class);
        $performers = $performanceService->getTopPerformers((int) $month, (int) $year);

        return ApiResponse::make('Top performers fetched', $performers);
    }

    public function lowPerformers()
    {
        $request = request();
        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;

        $performanceService = app(PerformanceService::class);
        $performers = $performanceService->getLowPerformers((int) $month, (int) $year);

        return ApiResponse::make('Low performers fetched', $performers);
    }

    public function departmentPerformance()
    {
        $request = request();
        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;

        $performanceService = app(PerformanceService::class);
        $data = $performanceService->getDepartmentPerformance((int) $month, (int) $year);

        return ApiResponse::make('Department performance fetched', $data);
    }

    public function scoreDistribution()
    {
        $request = request();
        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;

        $performanceService = app(PerformanceService::class);
        $data = $performanceService->getScoreDistribution((int) $month, (int) $year);

        return ApiResponse::make('Score distribution fetched', $data);
    }

    public function employeesNeedingTraining()
    {
        $request = request();
        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;

        $performanceService = app(PerformanceService::class);
        $data = $performanceService->getEmployeesNeedingTraining((int) $month, (int) $year);

        return ApiResponse::make('Employees needing training fetched', $data);
    }

    public function employeesAtRisk()
    {
        $request = request();
        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;

        $performanceService = app(PerformanceService::class);
        $data = $performanceService->getEmployeesAtRisk((int) $month, (int) $year);

        return ApiResponse::make('Employees at risk fetched', $data);
    }

    public function promotionRecommendations()
    {
        $request = request();
        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;

        $performanceService = app(PerformanceService::class);
        $data = $performanceService->getPromotionRecommendations((int) $month, (int) $year);

        return ApiResponse::make('Promotion recommendations fetched', $data);
    }

    public function incrementRecommendations()
    {
        $request = request();
        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;

        $performanceService = app(PerformanceService::class);
        $data = $performanceService->getIncrementRecommendations((int) $month, (int) $year);

        return ApiResponse::make('Increment recommendations fetched', $data);
    }

    public function calculate(Request $request)
    {
        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;

        $performanceService = app(PerformanceService::class);

        if ($request->has('user_id')) {
            $userId = $this->getIdFromHash($request->user_id);
            $performanceService->calculateForEmployee($userId, (int) $month, (int) $year);
        } else {
            $performanceService->calculateForAll((int) $month, (int) $year);
        }

        return ApiResponse::make('Performance scores calculated successfully', []);
    }

    public function selfDashboard()
    {
        $user = user();
        $request = request();
        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;

        $performanceService = app(PerformanceService::class);
        $targetUserId = $user->id;

        if ($user->user_type === 'staff_members') {
            $scoreExists = \App\Models\EmployeePerformanceScore::where('user_id', $user->id)
                ->where('month', (int) $month)
                ->where('year', (int) $year)
                ->exists();

            if (!$scoreExists) {
                try {
                    $performanceService->calculateForEmployee($user->id, (int) $month, (int) $year);
                } catch (\Exception $e) {
                    // Ignore calculation error
                }
            }
        } else {
            // Super Admin / Non-staff fallback to show populated dashboard
            $firstScore = \App\Models\EmployeePerformanceScore::where('month', (int) $month)
                ->where('year', (int) $year)
                ->first();
            if ($firstScore) {
                $targetUserId = $firstScore->user_id;
            } else {
                $activeStaff = \App\Models\StaffMember::where('user_type', 'staff_members')
                    ->where('status', 'active')
                    ->first();
                if ($activeStaff) {
                    $targetUserId = $activeStaff->id;
                }
            }
        }

        $data = $performanceService->getDashboardData($targetUserId, (int) $month, (int) $year);

        return ApiResponse::make('Self performance data fetched', $data);
    }
}
