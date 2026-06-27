<?php

namespace App\Http\Controllers\Api\EnterpriseTasks;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\EnterpriseTasks\ProjectSection;
use App\Models\EnterpriseTasks\Label;
use App\Models\EnterpriseTasks\Task;
use App\Models\EnterpriseTasks\TaskUser;
use App\Models\EnterpriseTasks\TaskLabel;
use App\Models\EnterpriseTasks\Checklist;
use App\Models\EnterpriseTasks\ChecklistItem;
use App\Models\EnterpriseTasks\TaskComment;
use App\Models\EnterpriseTasks\CommentReaction;
use App\Models\EnterpriseTasks\TaskAttachment;
use App\Models\EnterpriseTasks\TaskActivity;
use App\Models\EnterpriseTasks\Reminder;
use App\Models\EnterpriseTasks\Notification;
use App\Models\EnterpriseTasks\TimeLog;
use App\Models\EnterpriseTasks\SavedFilter;
use App\Models\User;
use App\Classes\Common;
use App\Exports\EnterpriseReportExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

use App\DTOs\EnterpriseTasks\ProjectDTO;
use App\DTOs\EnterpriseTasks\TaskDTO;
use App\DTOs\EnterpriseTasks\AutomationRuleDTO;
use App\DTOs\EnterpriseTasks\SavedViewDTO;
use App\DTOs\EnterpriseTasks\TimeLogDTO;
use App\DTOs\EnterpriseTasks\TaskTemplateDTO;
use App\DTOs\EnterpriseTasks\DependencyDTO;

use App\Services\EnterpriseTasks\ProjectService;
use App\Services\EnterpriseTasks\TaskService;
use App\Services\EnterpriseTasks\DependencyService;
use App\Services\EnterpriseTasks\AutomationService;
use App\Services\EnterpriseTasks\TemplateService;
use App\Services\EnterpriseTasks\SavedViewService;
use App\Services\EnterpriseTasks\NotificationService;
use App\Services\EnterpriseTasks\ProductivityService;
use App\Services\EnterpriseTasks\DashboardService;
use App\Services\EnterpriseTasks\MetricsService;
use App\Services\EnterpriseTasks\ReportService;

use App\Models\EnterpriseTasks\Dependency;
use App\Models\EnterpriseTasks\AutomationRule;
use App\Models\EnterpriseTasks\TaskTemplate;
use App\Models\EnterpriseTasks\TaskTemplateItem;
use App\Models\EnterpriseTasks\SavedView;
use App\Models\EnterpriseTasks\UserProductivityScore;
use App\Models\EnterpriseTasks\Favorite;
use App\Models\EnterpriseTasks\RecurringTask;
use App\Models\EnterpriseTasks\UserStreak;
use App\Models\EnterpriseTasks\Badge;
use App\Models\EnterpriseTasks\UserAchievement;
use App\Services\EnterpriseTasks\NaturalLanguageParserService;
use App\Services\EnterpriseTasks\GlobalSearchService;
use App\Services\EnterpriseTasks\RecurringTaskService;
use App\Services\EnterpriseTasks\GamificationService;
use App\Services\EnterpriseTasks\UndoService;
use App\Models\EnterpriseTasks\Goal;
use App\Models\EnterpriseTasks\PomodoroSession;
use App\Models\EnterpriseTasks\NotificationPreference;

class TaskController extends Controller
{
    protected ProjectService $projectService;
    protected TaskService $taskService;
    protected DependencyService $dependencyService;
    protected AutomationService $automationService;
    protected TemplateService $templateService;
    protected SavedViewService $savedViewService;
    protected NotificationService $notificationService;
    protected ProductivityService $productivityService;
    protected DashboardService $dashboardService;
    protected MetricsService $metricsService;
    protected ReportService $reportService;

    public function __construct(
        ProjectService $projectService,
        TaskService $taskService,
        DependencyService $dependencyService,
        AutomationService $automationService,
        TemplateService $templateService,
        SavedViewService $savedViewService,
        NotificationService $notificationService,
        ProductivityService $productivityService,
        DashboardService $dashboardService,
        MetricsService $metricsService,
        ReportService $reportService
    ) {
        $this->projectService = $projectService;
        $this->taskService = $taskService;
        $this->dependencyService = $dependencyService;
        $this->automationService = $automationService;
        $this->templateService = $templateService;
        $this->savedViewService = $savedViewService;
        $this->notificationService = $notificationService;
        $this->productivityService = $productivityService;
        $this->dashboardService = $dashboardService;
        $this->metricsService = $metricsService;
        $this->reportService = $reportService;
    }
    // ==========================================
    // HELPERS
    // ==========================================

    private function logActivity($taskId, $type, $description, $properties = null)
    {
        TaskActivity::create([
            'task_id' => $taskId,
            'user_id' => auth('api')->id(),
            'activity_type' => $type,
            'description' => $description,
            'properties' => $properties
        ]);
    }

    private function createNotification($userId, $type, $data)
    {
        Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'data' => $data,
            'read_at' => null
        ]);
    }

    // ==========================================
    // TASKS
    // ==========================================

    public function indexTasks(Request $request)
    {
        $companyId = company()->id;
        $query = Task::where('company_id', $companyId)
            ->where('is_deleted', false)
            ->with(['project', 'section', 'subtasks', 'createdByUser', 'updatedByUser']);

        // Filter by completed_by
        if ($request->has('x_completed_by') && $request->x_completed_by != '') {
            $completedById = Common::getIdFromHash($request->x_completed_by);
            $query->where('updated_by', $completedById);
        }

        // Filter by project
        if ($request->has('x_project_id') && $request->x_project_id != '') {
            $projId = Common::getIdFromHash($request->x_project_id);
            $query->where('project_id', $projId);
        }

        // Filter by section
        if ($request->has('x_section_id') && $request->x_section_id != '') {
            $secId = Common::getIdFromHash($request->x_section_id);
            $query->where('section_id', $secId);
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->has('priority') && $request->priority != '') {
            $query->where('priority', $request->priority);
        }

        // Filter by due date range
        if ($request->has('due_date_start') || $request->has('due_date_end')) {
            $start = $request->get('due_date_start');
            $end = $request->get('due_date_end');
            $includeOverdue = $request->get('include_overdue') === 'true';
            $today = \Carbon\Carbon::today()->toDateString();

            $query->where(function($q) use ($start, $end, $includeOverdue, $today) {
                $q->where(function($sq) use ($start, $end) {
                    if ($start) {
                        $sq->where('due_date', '>=', $start);
                    }
                    if ($end) {
                        $sq->where('due_date', '<=', $end);
                    }
                });

                if ($includeOverdue) {
                    $q->orWhere(function($sq) use ($today) {
                        $sq->where('due_date', '<', $today)
                           ->where('status', '!=', 'completed');
                    });
                }
            });
        }

        // Filter by completed_on date
        if ($request->has('completed_on')) {
            $completedOn = $request->get('completed_on');
            $query->where('status', 'completed')
                  ->whereDate('completion_date', $completedOn);
        }

        // Filter by Search Query
        if ($request->has('search') && $request->search != '') {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('title', 'like', "%$s%")
                  ->orWhere('description', 'like', "%$s%")
                  ->orWhere('task_number', 'like', "%$s%");
            });
        }

        // Hide subtasks from parent lists
        if ($request->get('parent_only', 'false') === 'true') {
            $query->whereNull('parent_id');
        }

        // Filter by Assignee
        if ($request->has('x_assignee_id') && $request->x_assignee_id != '') {
            $assId = Common::getIdFromHash($request->x_assignee_id);
            $query->whereHas('taskUsers', function($q) use ($assId) {
                $q->where('user_id', $assId)->where('type', 'assignee');
            });
        }

        // Filter by Labels
        if ($request->has('label_ids') && is_array($request->label_ids)) {
            $labelIds = array_map(function($lid) {
                return Common::getIdFromHash($lid);
            }, $request->label_ids);
            $query->whereHas('taskLabels', function($q) use ($labelIds) {
                $q->whereIn('label_id', $labelIds);
            });
        }

        // Sorting
        $orderBy = $request->get('order_by', 'id');
        $orderDir = $request->get('order_dir', 'desc');
        $query->orderBy($orderBy, $orderDir);

        $tasks = $query->paginate($request->get('per_page', 50));

        return response()->json($tasks);
    }

    public function storeTask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'x_project_id' => 'nullable|string',
            'x_section_id' => 'nullable|string',
            'x_parent_id' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,under_review,testing,completed,cancelled,on_hold',
            'priority' => 'required|in:P1,P2,P3,P4',
            'due_date' => 'nullable|date',
            'deadline' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric',
            'recurrence_type' => 'required|in:none,daily,weekly,monthly,yearly,custom'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $companyId = company()->id;
        $userId = auth('api')->id();
        $dto = TaskDTO::fromRequest($request);

        $task = $this->taskService->createTask($dto, $companyId, $userId);

        return response()->json($task);
    }

    public function showTask($id)
    {
        $taskId = Common::getIdFromHash($id);
        $task = Task::with([
            'project', 'section', 'subtasks', 'createdByUser', 'updatedByUser', 'checklists.items',
            'comments.replies', 'attachments', 'activities.user', 'timeLogs', 'reminders'
        ])->findOrFail($taskId);

        return response()->json($task);
    }

    public function updateTask(Request $request, $id)
    {
        $taskId = Common::getIdFromHash($id);
        $task = Task::findOrFail($taskId);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'status' => 'required|in:pending,in_progress,under_review,testing,completed,cancelled,on_hold',
            'priority' => 'required|in:P1,P2,P3,P4',
            'due_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'deadline' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric',
            'actual_hours' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $dto = TaskDTO::fromRequest($request);
        $userId = auth('api')->id();
        $oldStatus = $task->status;

        try {
            $task = $this->taskService->updateTask($task, $dto, $userId);
            
            $undoXid = null;
            if ($request->has('status') && $request->status !== $oldStatus) {
                $undoService = app(UndoService::class);
                $undo = $undoService->createUndo($userId, 'status_change', [
                    'task_id' => $task->id,
                    'old_status' => $oldStatus
                ]);
                $undoXid = $undo->xid;
            }

            return response()->json(array_merge($task->toArray(), ['x_undo_id' => $undoXid]));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroyTask($id)
    {
        $taskId = Common::getIdFromHash($id);
        $task = Task::findOrFail($taskId);
        
        // Save undo action
        $undoService = app(UndoService::class);
        $undo = $undoService->createUndo(auth('api')->id(), 'delete', [
            'task_id' => $task->id
        ]);

        $this->taskService->deleteTask($task);

        return response()->json(['success' => true, 'x_undo_id' => $undo->xid]);
    }

    private function syncTaskRelationships(Task $task, Request $request)
    {
        // 1. Assignees
        if ($request->has('assignees_xids')) {
            TaskUser::where('task_id', $task->id)->where('type', 'assignee')->delete();
            foreach ($request->assignees_xids as $xid) {
                $uid = Common::getIdFromHash($xid);
                if ($uid) {
                    TaskUser::create([
                        'task_id' => $task->id,
                        'user_id' => $uid,
                        'type' => 'assignee',
                        'assigned_by' => auth('api')->id(),
                        'assigned_date' => Carbon::now()
                    ]);
                    $this->createNotification($uid, 'task_assigned', ['task_title' => $task->title, 'task_xid' => $task->xid]);
                }
            }
        }

        // 2. Reviewers
        if ($request->has('reviewers_xids')) {
            TaskUser::where('task_id', $task->id)->where('type', 'reviewer')->delete();
            foreach ($request->reviewers_xids as $xid) {
                $uid = Common::getIdFromHash($xid);
                if ($uid) {
                    TaskUser::create([
                        'task_id' => $task->id,
                        'user_id' => $uid,
                        'type' => 'reviewer',
                        'assigned_by' => auth('api')->id(),
                        'assigned_date' => Carbon::now()
                    ]);
                }
            }
        }

        // 3. Watchers
        if ($request->has('watchers_xids')) {
            TaskUser::where('task_id', $task->id)->where('type', 'watcher')->delete();
            foreach ($request->watchers_xids as $xid) {
                $uid = Common::getIdFromHash($xid);
                if ($uid) {
                    TaskUser::create([
                        'task_id' => $task->id,
                        'user_id' => $uid,
                        'type' => 'watcher'
                    ]);
                }
            }
        }

        // 4. Labels
        if ($request->has('labels_xids')) {
            TaskLabel::where('task_id', $task->id)->delete();
            foreach ($request->labels_xids as $xid) {
                $lid = Common::getIdFromHash($xid);
                if ($lid) {
                    TaskLabel::create([
                        'task_id' => $task->id,
                        'label_id' => $lid
                    ]);
                }
            }
        }
    }

    // ==========================================
    // RECURRING TASKS
    // ==========================================

    private function calculateNextRecurrence($type, $dueDate, $pattern = null)
    {
        if ($type === 'none' || !$dueDate) return null;
        $date = Carbon::parse($dueDate);

        switch ($type) {
            case 'daily':
                return $date->addDay()->toDateString();
            case 'weekly':
                return $date->addWeek()->toDateString();
            case 'monthly':
                return $date->addMonth()->toDateString();
            case 'yearly':
                return $date->addYear()->toDateString();
            case 'custom':
                // expect interval in pattern
                $interval = (int)($pattern ?? 1);
                return $date->addDays($interval)->toDateString();
        }
        return null;
    }

    private function generateNextRecurringTask(Task $task)
    {
        if (!$task->next_recurrence_date) return;

        // Duplicate task
        $newTask = $task->replicate();
        $newTask->status = 'pending';
        
        // Next dates
        $newTask->due_date = $task->next_recurrence_date;
        $newTask->start_date = null;
        $newTask->completion_date = null;

        // Generate next recurrence date
        $newTask->next_recurrence_date = $this->calculateNextRecurrence($task->recurrence_type, $task->next_recurrence_date, $task->recurrence_pattern);
        
        // Save
        $maxNum = Task::where('company_id', $task->company_id)->count() + 1;
        $projectCode = $task->project_id ? strtoupper(substr($task->project->name, 0, 3)) : 'TASK';
        $newTask->task_number = $projectCode . '-' . str_pad($maxNum, 4, '0', STR_PAD_LEFT);
        $newTask->save();

        // Re-assign users
        $oldUsers = TaskUser::where('task_id', $task->id)->get();
        foreach ($oldUsers as $ou) {
            $ouCopy = $ou->replicate();
            $ouCopy->task_id = $newTask->id;
            $ouCopy->save();
        }

        // Re-assign labels
        $oldLabels = TaskLabel::where('task_id', $task->id)->get();
        foreach ($oldLabels as $ol) {
            $olCopy = $ol->replicate();
            $olCopy->task_id = $newTask->id;
            $olCopy->save();
        }

        $this->logActivity($newTask->id, 'create', "Task automatically generated from recurrence of {$task->task_number}");
    }

    // ==========================================
    // SUBTASKS
    // ==========================================

    public function storeSubtask(Request $request, $id)
    {
        $parentTaskId = Common::getIdFromHash($id);
        $parent = Task::findOrFail($parentTaskId);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'priority' => 'required|in:P1,P2,P3,P4',
            'due_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $companyId = company()->id;
        $userId = auth('api')->id();

        // Increment overall tasks
        $maxNum = Task::where('company_id', $companyId)->count() + 1;
        $projectCode = $parent->project_id ? strtoupper(substr($parent->project->name, 0, 3)) : 'SUB';
        $taskNumber = $projectCode . '-SUB-' . str_pad($maxNum, 4, '0', STR_PAD_LEFT);

        $subtask = Task::create([
            'company_id' => $companyId,
            'project_id' => $parent->project_id,
            'section_id' => $parent->section_id,
            'parent_id' => $parent->id,
            'task_number' => $taskNumber,
            'title' => $request->title,
            'status' => 'pending',
            'priority' => $request->priority,
            'created_by' => $userId,
            'updated_by' => $userId,
            'due_date' => $request->due_date
        ]);

        $this->logActivity($parent->id, 'subtask_create', "Subtask {$subtask->task_number} added");
        return response()->json($subtask);
    }

    public function convertSubtask(Request $request, $id)
    {
        $taskId = Common::getIdFromHash($id);
        $task = Task::findOrFail($taskId);

        if ($task->parent_id) {
            // Convert subtask to standalone task
            $oldParentId = $task->parent_id;
            $task->update(['parent_id' => null]);
            $this->logActivity($task->id, 'convert', "Subtask converted to standalone Task");
            $this->logActivity($oldParentId, 'subtask_convert', "Subtask {$task->task_number} converted to standalone Task");
        } else {
            // Convert task to subtask
            $validator = Validator::make($request->all(), [
                'x_parent_id' => 'required|string'
            ]);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            $parentId = Common::getIdFromHash($request->x_parent_id);
            $task->update(['parent_id' => $parentId]);
            $this->logActivity($task->id, 'convert', "Standalone task converted to subtask");
            $this->logActivity($parentId, 'subtask_added', "Task {$task->task_number} converted and attached as subtask");
        }

        return response()->json(['success' => true]);
    }

    // ==========================================
    // CHECKLISTS
    // ==========================================

    public function storeChecklist(Request $request, $taskId)
    {
        $tId = Common::getIdFromHash($taskId);
        $task = Task::findOrFail($tId);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $sortOrder = Checklist::where('task_id', $task->id)->max('sort_order') + 1;

        $checklist = Checklist::create([
            'task_id' => $task->id,
            'name' => $request->name,
            'sort_order' => $sortOrder
        ]);

        $this->logActivity($task->id, 'checklist_create', "Checklist '{$checklist->name}' added");
        return response()->json($checklist);
    }

    public function destroyChecklist($id)
    {
        $checklistId = Common::getIdFromHash($id);
        $checklist = Checklist::findOrFail($checklistId);
        $taskId = $checklist->task_id;
        $checklist->delete();
        $this->logActivity($taskId, 'checklist_delete', "Checklist '{$checklist->name}' removed");
        return response()->json(['success' => true]);
    }

    public function storeChecklistItem(Request $request, $checklistId)
    {
        $cId = Common::getIdFromHash($checklistId);
        $checklist = Checklist::findOrFail($cId);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $sortOrder = ChecklistItem::where('checklist_id', $checklist->id)->max('sort_order') + 1;

        $item = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'name' => $request->name,
            'is_completed' => false,
            'sort_order' => $sortOrder
        ]);

        return response()->json($item);
    }

    public function updateChecklistItem(Request $request, $id)
    {
        $itemId = Common::getIdFromHash($id);
        $item = ChecklistItem::findOrFail($itemId);

        $item->update([
            'name' => $request->name ?? $item->name,
            'is_completed' => $request->has('is_completed') ? $request->is_completed : $item->is_completed
        ]);

        return response()->json($item);
    }

    public function destroyChecklistItem($id)
    {
        $itemId = Common::getIdFromHash($id);
        $item = ChecklistItem::findOrFail($itemId);
        $item->delete();
        return response()->json(['success' => true]);
    }

    public function reorderChecklistItems(Request $request, $checklistId)
    {
        $cId = Common::getIdFromHash($checklistId);
        
        foreach ($request->items as $index => $itemData) {
            $itemId = Common::getIdFromHash($itemData['xid']);
            ChecklistItem::where('id', $itemId)->where('checklist_id', $cId)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    // ==========================================
    // ATTACHMENTS
    // ==========================================


    public function storeAttachment(Request $request, $taskId)
    {
        $tId = Common::getIdFromHash($taskId);
        $task = Task::findOrFail($tId);

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240' // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $fileType = $file->getClientOriginalExtension();

        $folderPath = 'enterprise_tasks/attachments';
        $savedPath = $file->storePublicly($folderPath, 'public');
        $fileUrl = asset('storage/' . $savedPath);

        $attachment = TaskAttachment::create([
            'task_id' => $task->id,
            'user_id' => auth('api')->id(),
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'file_type' => $fileType,
            'file_path' => $savedPath,
            'file_url' => $fileUrl
        ]);

        $this->logActivity($task->id, 'attachment', "Uploaded attachment '$fileName'");

        return response()->json($attachment);
    }

    public function destroyAttachment($id)
    {
        $attachmentId = Common::getIdFromHash($id);
        $attachment = TaskAttachment::findOrFail($attachmentId);
        
        Storage::disk('public')->delete($attachment->file_path);
        
        $taskId = $attachment->task_id;
        $fileName = $attachment->file_name;
        
        $attachment->delete();

        $this->logActivity($taskId, 'attachment_delete', "Deleted attachment '$fileName'");

        return response()->json(['success' => true]);
    }

    // ==========================================
    // REMINDERS
    // ==========================================

    public function storeReminder(Request $request, $taskId)
    {
        $tId = Common::getIdFromHash($taskId);
        $task = Task::findOrFail($tId);

        $validator = Validator::make($request->all(), [
            'reminder_type' => 'required|in:10m,30m,1h,1d,custom',
            'remind_at' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $reminder = Reminder::create([
            'task_id' => $task->id,
            'user_id' => auth('api')->id(),
            'reminder_type' => $request->reminder_type,
            'remind_at' => Carbon::parse($request->remind_at)
        ]);

        return response()->json($reminder);
    }

    public function destroyReminder($id)
    {
        $reminderId = Common::getIdFromHash($id);
        $reminder = Reminder::findOrFail($reminderId);
        $reminder->delete();
        return response()->json(['success' => true]);
    }



    // ==========================================
    // TASK OPERATIONS & BULK UPDATES
    // ==========================================

    public function duplicateTask($id)
    {
        $taskId = Common::getIdFromHash($id);
        $task = Task::findOrFail($taskId);

        $newTask = $task->replicate();
        $maxNum = Task::where('company_id', $task->company_id)->count() + 1;
        $projectCode = $task->project_id ? strtoupper(substr($task->project->name, 0, 3)) : 'TASK';
        
        $newTask->task_number = $projectCode . '-' . str_pad($maxNum, 4, '0', STR_PAD_LEFT);
        $newTask->title = $task->title . ' (Copy)';
        $newTask->status = 'pending';
        $newTask->completion_date = null;
        $newTask->save();

        // Copy relations
        $taskUsers = TaskUser::where('task_id', $task->id)->get();
        foreach ($taskUsers as $tu) {
            $tuCopy = $tu->replicate();
            $tuCopy->task_id = $newTask->id;
            $tuCopy->save();
        }

        $taskLabels = TaskLabel::where('task_id', $task->id)->get();
        foreach ($taskLabels as $tl) {
            $tlCopy = $tl->replicate();
            $tlCopy->task_id = $newTask->id;
            $tlCopy->save();
        }

        $this->logActivity($newTask->id, 'duplicate', "Task duplicated from {$task->task_number}");

        return response()->json($newTask);
    }

    public function moveTask(Request $request, $id)
    {
        $taskId = Common::getIdFromHash($id);
        $task = Task::findOrFail($taskId);

        $validator = Validator::make($request->all(), [
            'x_project_id' => 'required|string',
            'x_section_id' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $projId = Common::getIdFromHash($request->x_project_id);
        $secId = $request->x_section_id ? Common::getIdFromHash($request->x_section_id) : null;

        $oldProjectId = $task->project_id;
        $oldSectionId = $task->section_id;
        $oldProjectName = $task->project ? $task->project->name : 'No Project';
        $newProject = Project::findOrFail($projId);

        $task->update([
            'project_id' => $projId,
            'section_id' => $secId
        ]);

        $this->logActivity($task->id, 'move', "Task moved from '$oldProjectName' to '{$newProject->name}'");

        // Save undo action
        $undoService = app(UndoService::class);
        $undo = $undoService->createUndo(auth('api')->id(), 'move', [
            'task_id' => $task->id,
            'old_project_id' => $oldProjectId,
            'old_section_id' => $oldSectionId
        ]);

        return response()->json(array_merge($task->toArray(), ['x_undo_id' => $undo->xid]));
    }

    public function toggleTaskComplete($id)
    {
        $taskId = Common::getIdFromHash($id);
        $task = Task::findOrFail($taskId);
        $oldStatus = $task->status;

        try {
            $task = $this->taskService->toggleComplete($task, auth('api')->id());

            // Save undo action
            $undoService = app(UndoService::class);
            $undo = $undoService->createUndo(auth('api')->id(), 'complete', [
                'task_id'    => $task->id,
                'old_status' => $oldStatus
            ]);

            // Update gamification (streak + goals) if just completed
            if ($task->status === 'completed') {
                $companyId = company()->id;
                $userId = auth('api')->id();
                $gamificationService = app(GamificationService::class);
                $gamificationService->updateStreak($companyId, $userId);
                $gamificationService->updateGoalProgress($companyId, $userId);
                $gamificationService->checkAndAwardBadges(
                    $companyId,
                    $userId,
                    Task::where('company_id', $companyId)
                        ->where('status', 'completed')
                        ->whereHas('taskUsers', fn($q) => $q->where('user_id', $userId)->where('type', 'assignee'))
                        ->count(),
                    'tasks_completed'
                );
            }

            return response()->json(array_merge($task->toArray(), ['x_undo_id' => $undo->xid]));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function bulkUpdateTasks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'xids' => 'required|array',
            'status' => 'nullable|in:pending,in_progress,under_review,testing,completed,cancelled,on_hold',
            'priority' => 'nullable|in:P1,P2,P3,P4',
            'assignees_xids' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ids = array_map(function($xid) {
            return Common::getIdFromHash($xid);
        }, $request->xids);

        if ($request->has('status')) {
            Task::whereIn('id', $ids)->update([
                'status' => $request->status,
                'completion_date' => $request->status === 'completed' ? Carbon::now() : null
            ]);
            foreach ($ids as $id) {
                $this->logActivity($id, 'status_change', "Bulk updated status to {$request->status}");
            }
        }

        if ($request->has('priority')) {
            Task::whereIn('id', $ids)->update(['priority' => $request->priority]);
            foreach ($ids as $id) {
                $this->logActivity($id, 'priority_change', "Bulk updated priority to {$request->priority}");
            }
        }

        if ($request->has('assignees_xids')) {
            TaskUser::whereIn('task_id', $ids)->where('type', 'assignee')->delete();
            foreach ($ids as $id) {
                foreach ($request->assignees_xids as $xid) {
                    $uid = Common::getIdFromHash($xid);
                    if ($uid) {
                        TaskUser::create([
                            'task_id' => $id,
                            'user_id' => $uid,
                            'type' => 'assignee',
                            'assigned_by' => auth('api')->id(),
                            'assigned_date' => Carbon::now()
                        ]);
                    }
                }
            }
        }

        return response()->json(['success' => true]);
    }

    public function bulkDeleteTasks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'xids' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ids = array_map(function($xid) {
            return Common::getIdFromHash($xid);
        }, $request->xids);

        Task::whereIn('id', $ids)->update(['is_deleted' => true]);

        return response()->json(['success' => true]);
    }

    // ==========================================
    // REPORTS
    // ==========================================

    public function getReports(Request $request)
    {
        $companyId = company()->id;
        $type = $request->get('type', 'productivity');
        $data = $this->reportService->getReportData($companyId, $type, $request->all());
        return response()->json($data);
    }

    public function exportReport(Request $request)
    {
        $type = $request->get('type', 'productivity');
        $companyId = company()->id;
        $export = $this->reportService->exportExcel($companyId, $type, $request->all());

        return response()->streamDownload(function () use ($export) {
            echo Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        }, "enterprise_{$type}_report.xlsx");
    }

    public function exportPdfReport(Request $request)
    {
        $type = $request->get('type', 'productivity');
        $companyId = company()->id;
        $reportData = $this->reportService->getReportData($companyId, $type, $request->all());
        $company = company();

        $pdfData = [
            'type' => $type,
            'title' => 'Enterprise Tasks - ' . ucfirst($type) . ' Report',
            'reportData' => $reportData,
            'company' => $company,
            'light_color' => Common::lightenHexColor($company->letterhead_header_color ?? '#3b82f6', 30),
            'showHeaderFooter' => true
        ];

        config([
            'pdf.margin_left' => 15,
            'pdf.margin_right' => 15,
            'pdf.margin_top' => 15,
            'pdf.margin_bottom' => 15,
        ]);

        $pdf = PDF::loadView('pdf.enterprise_report', $pdfData);

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="enterprise_' . $type . '_report.pdf"');
    }

    // ==========================================
    // TIME LOGS
    // ==========================================

    public function startTimeLog(Request $request, $id)
    {
        $taskId = Common::getIdFromHash($id);
        $userId = auth('api')->id();

        try {
            $log = $this->timeLogService->startTimeLog($taskId, $userId);
            return response()->json($log);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function stopTimeLog(Request $request, $id)
    {
        $taskId = Common::getIdFromHash($id);
        $userId = auth('api')->id();
        $memo = $request->get('memo');

        try {
            $log = $this->timeLogService->stopTimeLog($taskId, $userId, $memo);
            return response()->json($log);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function storeTimeLog(Request $request, $id)
    {
        $taskId = Common::getIdFromHash($id);
        $userId = auth('api')->id();

        $validator = Validator::make($request->all(), [
            'duration_minutes' => 'required|integer|min:1',
            'memo' => 'nullable|string',
            'log_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $dto = TimeLogDTO::fromRequest($request);
        $log = $this->timeLogService->storeTimeLog($taskId, $userId, $dto);

        return response()->json($log);
    }

    public function indexTimeLogs(Request $request)
    {
        $companyId = company()->id;
        $logs = TimeLog::whereHas('task', function($q) use ($companyId) {
            $q->where('company_id', $companyId)->where('is_deleted', false);
        })->with(['task', 'user'])->orderBy('id', 'desc')->paginate($request->get('per_page', 50));
        return response()->json($logs);
    }

    // ==========================================
    // DEPENDENCIES
    // ==========================================

    public function indexDependencies(Request $request, $id)
    {
        $taskId = Common::getIdFromHash($id);
        $dependencies = Dependency::where('task_id', $taskId)
            ->with(['dependsOnTask'])
            ->get();
        return response()->json($dependencies);
    }

    public function storeDependency(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'x_task_id' => 'required|string',
            'x_depends_on_task_id' => 'required|string',
            'dependency_type' => 'required|in:finish_to_start,start_to_start,finish_to_finish,start_to_finish',
            'lag_days' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $companyId = company()->id;
        $dto = DependencyDTO::fromRequest($request);

        try {
            $task = Task::findOrFail($dto->task_id);
            $dependency = $this->dependencyService->createDependency(
                $companyId,
                $dto->project_id ?? $task->project_id,
                $dto->task_id,
                $dto->depends_on_task_id,
                $dto->dependency_type,
                $dto->lag_days
            );
            return response()->json($dependency);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroyDependency($id)
    {
        $depId = Common::getIdFromHash($id);
        $deleted = $this->dependencyService->removeDependency($depId);
        return response()->json(['success' => $deleted]);
    }

    public function criticalPath($id)
    {
        $projectId = Common::getIdFromHash($id);
        $path = $this->dependencyService->calculateCriticalPath($projectId);
        return response()->json($path);
    }

    // ==========================================
    // AUTOMATION RULES
    // ==========================================

    public function indexRules(Request $request)
    {
        $companyId = company()->id;
        $rules = AutomationRule::where('company_id', $companyId)->get();
        return response()->json($rules);
    }

    public function storeRule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'event_name' => 'required|string',
            'conditions' => 'nullable|array',
            'actions' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $companyId = company()->id;
        $dto = AutomationRuleDTO::fromRequest($request);

        $rule = AutomationRule::create(array_merge($dto->toArray(), [
            'company_id' => $companyId,
            'created_by' => auth('api')->id()
        ]));

        return response()->json($rule);
    }

    public function updateRule(Request $request, $id)
    {
        $ruleId = Common::getIdFromHash($id);
        $rule = AutomationRule::findOrFail($ruleId);

        $dto = AutomationRuleDTO::fromRequest($request);
        $rule->update($dto->toArray());

        return response()->json($rule);
    }

    public function destroyRule($id)
    {
        $ruleId = Common::getIdFromHash($id);
        $rule = AutomationRule::findOrFail($ruleId);
        $rule->delete();
        return response()->json(['success' => true]);
    }

    // ==========================================
    // TASK TEMPLATES
    // ==========================================

    public function indexTemplates(Request $request)
    {
        $companyId = company()->id;
        $templates = TaskTemplate::where('company_id', $companyId)->with('items')->get();
        return response()->json($templates);
    }

    public function storeTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'items' => 'required|array',
            'items.*.title' => 'required|string|max:255',
            'items.*.priority' => 'required|in:P1,P2,P3,P4',
            'items.*.estimated_hours' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $companyId = company()->id;
        $dto = TaskTemplateDTO::fromRequest($request);

        $template = DB::transaction(function() use ($dto, $companyId) {
            $tpl = TaskTemplate::create(array_merge($dto->toArray(), [
                'company_id' => $companyId,
                'created_by' => auth('api')->id()
            ]));

            foreach ($dto->items as $index => $item) {
                TaskTemplateItem::create([
                    'template_id' => $tpl->id,
                    'title' => $item['title'],
                    'description' => $item['description'] ?? $item['title'],
                    'priority' => $item['priority'],
                    'estimated_hours' => $item['estimated_hours'] ?? null,
                    'sort_order' => $index
                ]);
            }

            return $tpl;
        });

        return response()->json($template->load('items'));
    }

    public function updateTemplate(Request $request, $id)
    {
        $templateId = Common::getIdFromHash($id);
        $template = TaskTemplate::findOrFail($templateId);

        $dto = TaskTemplateDTO::fromRequest($request);
        
        DB::transaction(function() use ($template, $dto) {
            $template->update($dto->toArray());

            if (isset($dto->items)) {
                TaskTemplateItem::where('template_id', $template->id)->delete();
                foreach ($dto->items as $index => $item) {
                    TaskTemplateItem::create([
                        'template_id' => $template->id,
                        'title' => $item['title'],
                        'description' => $item['description'] ?? $item['title'],
                        'priority' => $item['priority'],
                        'estimated_hours' => $item['estimated_hours'] ?? null,
                        'sort_order' => $index
                    ]);
                }
            }
        });

        return response()->json($template->load('items'));
    }

    public function destroyTemplate($id)
    {
        $templateId = Common::getIdFromHash($id);
        $template = TaskTemplate::findOrFail($templateId);
        $template->delete();
        return response()->json(['success' => true]);
    }

    public function applyTemplate(Request $request, $id)
    {
        $templateId = Common::getIdFromHash($id);
        $validator = Validator::make($request->all(), [
            'x_project_id' => 'required|string',
            'x_section_id' => 'nullable|string',
            'baseline_date' => 'nullable|date',
            'assignees_xids' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $companyId = company()->id;
        $projectId = Common::getIdFromHash($request->x_project_id);
        $sectionId = $request->x_section_id ? Common::getIdFromHash($request->x_section_id) : null;

        $tasks = $this->templateService->applyTemplate($templateId, $companyId, $projectId, $sectionId, $request->all());

        return response()->json($tasks);
    }

    // ==========================================
    // SAVED VIEWS
    // ==========================================

    public function indexSavedViews(Request $request)
    {
        $companyId = company()->id;
        $userId = auth('api')->id();
        $views = $this->savedViewService->getViews($companyId, $userId);
        return response()->json($views);
    }

    public function storeSavedView(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'filters' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $companyId = company()->id;
        $userId = auth('api')->id();
        $dto = SavedViewDTO::fromRequest($request);

        $view = $this->savedViewService->createView($dto, $companyId, $userId);

        return response()->json($view);
    }

    public function updateSavedView(Request $request, $id)
    {
        $viewId = Common::getIdFromHash($id);
        $view = SavedView::findOrFail($viewId);

        $dto = SavedViewDTO::fromRequest($request);
        $this->savedViewService->updateView($view, $dto);

        return response()->json($view->fresh());
    }

    public function destroySavedView($id)
    {
        $viewId = Common::getIdFromHash($id);
        $view = SavedView::findOrFail($viewId);
        $this->savedViewService->deleteView($view);
        return response()->json(['success' => true]);
    }

    // ==========================================
    // PRODUCTIVITY RANKINGS
    // ==========================================

    public function productivityRankings(Request $request)
    {
        $companyId = company()->id;
        $today = Carbon::today();
        $year = $request->get('year', $today->year);
        $month = $request->get('month', $today->month);

        $rankings = UserProductivityScore::where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->with('user')
            ->orderBy('rank')
            ->get();

        return response()->json($rankings);
    }

    // ==========================================
    // SAVED FILTERS
    // ==========================================


    public function indexSavedFilters(Request $request)
    {
        $userId = auth('api')->id();
        $filters = SavedFilter::where('user_id', $userId)->get();
        return response()->json($filters);
    }

    public function storeSavedFilter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'filter_criteria' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userId = auth('api')->id();
        $filter = SavedFilter::create([
            'user_id' => $userId,
            'name' => $request->name,
            'filter_criteria' => $request->filter_criteria
        ]);

        return response()->json($filter);
    }

    public function destroySavedFilter($id)
    {
        $filterId = Common::getIdFromHash($id);
        $filter = SavedFilter::findOrFail($filterId);
        if ($filter->user_id === auth('api')->id()) {
            $filter->delete();
            return response()->json(['success' => true]);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // ====================================================
    // TODOIST PREMIUM & JIRA ENTERPRISE ADDITIONAL ENDPOINTS
    // ====================================================

    public function quickCreateTask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:500',
            'x_project_id' => 'nullable|string',
            'x_section_id' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $companyId = company()->id;
        $userId = auth('api')->id();

        $nlpService = app(NaturalLanguageParserService::class);
        $parsed = $nlpService->parse($request->text, $companyId);

        // Resolve Project/Section (Inbox as fallback)
        $xProjectId = $request->x_project_id;
        $xSectionId = $request->x_section_id;

        if (!$xProjectId) {
            $inbox = $this->projectService->ensureInboxProjectExists($companyId, $userId);
            $xProjectId = $inbox->xid;
            $xSectionId = Common::getHashFromId($inbox->sections()->first()->id);
        }

        $dtoData = [
            'title' => $parsed['title'],
            'x_project_id' => $xProjectId,
            'x_section_id' => $xSectionId,
            'status' => 'pending',
            'priority' => $parsed['priority'],
            'due_date' => $parsed['due_date'],
            'due_time' => $parsed['due_time'],
            'estimated_hours' => null,
            'recurrence_type' => $parsed['recurrence'],
            'labels_xids' => $parsed['labels']
        ];

        $dto = new TaskDTO($dtoData);
        $task = $this->taskService->createTask($dto, $companyId, $userId);

        if ($parsed['recurrence'] !== 'none') {
            $recService = app(RecurringTaskService::class);
            $recService->createRecurring($task, [
                'frequency' => $parsed['recurrence'],
                'interval_value' => 1,
                'end_type' => 'never'
            ], $userId);
        }

        return response()->json($task);
    }

    public function globalSearch(Request $request)
    {
        $query = $request->query('query', '');
        $companyId = company()->id;
        $userId = auth('api')->id();

        $searchService = app(GlobalSearchService::class);
        $results = $searchService->search($query, $companyId, $userId);

        return response()->json($results);
    }

    public function indexFavorites(Request $request)
    {
        $userId = auth('api')->id();
        $favorites = Favorite::where('user_id', $userId)->get();

        $favorites->each(function($fav) {
            $itemId = Common::getIdFromHash($fav->reference_id);
            $details = null;
            if ($fav->type === 'project') {
                $details = Project::find($itemId);
            } elseif ($fav->type === 'label') {
                $details = Label::find($itemId);
            } elseif ($fav->type === 'filter') {
                $details = SavedFilter::find($itemId);
            } elseif ($fav->type === 'view') {
                $details = SavedView::find($itemId);
            }
            $fav->setAttribute('details', $details);
        });

        return response()->json($favorites);
    }

    public function storeFavorite(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:project,label,filter,view',
            'reference_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $companyId = company()->id;
        $userId = auth('api')->id();

        $fav = Favorite::updateOrCreate([
            'company_id' => $companyId,
            'user_id' => $userId,
            'type' => $request->type,
            'reference_id' => $request->reference_id
        ]);

        return response()->json($fav);
    }

    public function destroyFavorite($id)
    {
        $favId = Common::getIdFromHash($id);
        $fav = Favorite::findOrFail($favId);

        if ($fav->user_id === auth('api')->id()) {
            $fav->delete();
            return response()->json(['success' => true]);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function executeUndo($xid)
    {
        $undoId = Common::getIdFromHash($xid);
        $action = UndoAction::findOrFail($undoId);

        if ($action->user_id !== auth('api')->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $undoService = app(UndoService::class);
        $success = $undoService->execute($action);

        return response()->json(['success' => $success]);
    }

    public function indexAchievements(Request $request)
    {
        $companyId = company()->id;
        $userId = auth('api')->id();

        // Ensure user streak record exists
        $streak = UserStreak::firstOrCreate(
            ['company_id' => $companyId, 'user_id' => $userId],
            ['daily_streak' => 0, 'weekly_streak' => 0]
        );

        $achievements = UserAchievement::where('user_id', $userId)
            ->with(['badge'])
            ->get();

        return response()->json([
            'streak' => $streak,
            'achievements' => $achievements
        ]);
    }

    public function getInboxProject(Request $request)
    {
        $companyId = company()->id;
        $userId = auth('api')->id();

        $inbox = $this->projectService->ensureInboxProjectExists($companyId, $userId);

        return response()->json($inbox->load(['sections']));
    }

}

