<?php

namespace App\Http\Controllers\Api\EnterpriseTasks;

use App\Http\Controllers\Controller;
use App\Services\EnterpriseTasks\AITaskAssistantService;
use App\Services\EnterpriseTasks\SmartSchedulerService;
use App\Classes\Common;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AIController extends Controller
{
    protected AITaskAssistantService $aiService;
    protected SmartSchedulerService $schedulerService;

    public function __construct(
        AITaskAssistantService $aiService,
        SmartSchedulerService $schedulerService
    ) {
        $this->aiService = $aiService;
        $this->schedulerService = $schedulerService;
    }

    public function generateSubtasks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_title' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $subtasks = $this->aiService->generateSubtasks($request->task_title);

        return response()->json([
            'subtasks' => $subtasks
        ]);
    }

    public function suggestPriority(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_title' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $priority = $this->aiService->suggestPriority($request->task_title, $request->description);

        return response()->json([
            'suggested_priority' => $priority
        ]);
    }

    public function suggestDeadline(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'x_user_id' => 'required|string',
            'estimated_hours' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userId = Common::getIdFromHash($request->x_user_id);
        $suggestion = $this->schedulerService->suggestBestDueDate($userId, $request->get('estimated_hours', 2));

        return response()->json($suggestion);
    }

    public function generateDescription(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_title' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $description = $this->aiService->generateDescription($request->task_title);

        return response()->json([
            'suggested_description' => $description
        ]);
    }

    public function standupSummary(Request $request)
    {
        $companyId = company()->id;
        $userId = auth('api')->id();
        $date = $request->get('date', now()->toDateString());

        $summary = $this->aiService->generateStandupSummary($companyId, $userId, $date);

        return response()->json($summary);
    }

    public function smartSchedule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'x_project_id' => 'required|string',
            'task_title' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $projectId = Common::getIdFromHash($request->x_project_id);
        
        $assigneeSuggestion = $this->schedulerService->suggestAssignee($projectId);
        $sectionSuggestion = $this->schedulerService->suggestSection($projectId, $request->task_title);

        return response()->json([
            'assignee_suggestion' => $assigneeSuggestion,
            'section_suggestion' => $sectionSuggestion
        ]);
    }
}
