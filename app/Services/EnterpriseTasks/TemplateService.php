<?php

namespace App\Services\EnterpriseTasks;

use App\Repositories\EnterpriseTasks\TemplateRepository;
use App\Models\EnterpriseTasks\TaskTemplate;
use App\Models\EnterpriseTasks\TaskTemplateItem;
use App\Models\EnterpriseTasks\Task;
use App\Models\Project;
use App\Models\EnterpriseTasks\ProjectSection;
use App\Services\EnterpriseTasks\TaskService;
use App\DTOs\EnterpriseTasks\TaskDTO;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TemplateService
{
    protected TemplateRepository $repository;
    protected TaskService $taskService;

    public function __construct(TemplateRepository $repository, TaskService $taskService)
    {
        $this->repository = $repository;
        $this->taskService = $taskService;
    }

    public function applyTemplate(int $templateId, int $companyId, int $projectId, ?int $sectionId = null, array $options = []): array
    {
        $template = TaskTemplate::findOrFail($templateId);
        $items = TaskTemplateItem::where('template_id', $templateId)->orderBy('sort_order')->get();

        $createdTasks = [];
        $baselineDate = isset($options['baseline_date']) ? Carbon::parse($options['baseline_date']) : Carbon::today();
        $assigneeIds = $options['assignees_xids'] ?? []; // Array of User Hashids
        $mode = $options['mode'] ?? 'tasks'; // 'tasks' or 'subtasks'
        $parentTaskId = isset($options['x_parent_id']) && $options['x_parent_id'] ? \App\Classes\Common::getIdFromHash($options['x_parent_id']) : null;

        DB::transaction(function() use ($items, $companyId, $projectId, $sectionId, $baselineDate, $assigneeIds, $mode, $parentTaskId, &$createdTasks) {
            foreach ($items as $item) {
                // Calculate relative due date based on sort order (e.g. baseline + sort_order days)
                $dueDate = (clone $baselineDate)->addDays($item->sort_order)->toDateString();

                $dtoData = [
                    'title' => $item->title,
                    'description' => $item->description,
                    'rich_text_description' => null,
                    'status' => 'pending',
                    'priority' => $item->priority,
                    'due_date' => $dueDate,
                    'estimated_hours' => (float)$item->estimated_hours,
                    'recurrence_type' => 'none',
                    'assignees_xids' => $assigneeIds,
                    'reviewers_xids' => [],
                    'watchers_xids' => [],
                    'labels_xids' => []
                ];

                if ($projectId) {
                    $dtoData['x_project_id'] = \Vinkla\Hashids\Facades\Hashids::encode($projectId);
                }
                if ($sectionId) {
                    $dtoData['x_section_id'] = \Vinkla\Hashids\Facades\Hashids::encode($sectionId);
                }
                if ($mode === 'subtasks' && $parentTaskId) {
                    $dtoData['x_parent_id'] = \Vinkla\Hashids\Facades\Hashids::encode($parentTaskId);
                }

                $dto = new TaskDTO($dtoData);
                $task = $this->taskService->createTask($dto, $companyId, auth('api')->id());
                $createdTasks[] = $task;
            }
        });

        return $createdTasks;
    }

    public function createSystemTemplates(int $companyId, int $userId): void
    {
        $templates = [
            [
                'name' => 'Employee Onboarding',
                'description' => 'Standard tasks for onboarding new hires.',
                'category' => 'HR',
                'items' => [
                    ['title' => 'Collect signed offer letter & background check docs', 'priority' => 'P1', 'est' => 2, 'sort' => 0],
                    ['title' => 'Configure email and IT accounts', 'priority' => 'P1', 'est' => 1, 'sort' => 1],
                    ['title' => 'Prepare onboarding package & welcome kit', 'priority' => 'P2', 'est' => 3, 'sort' => 2],
                    ['title' => 'Setup workstation and credentials', 'priority' => 'P1', 'est' => 2, 'sort' => 3],
                    ['title' => 'HR Orientation & benefits signup session', 'priority' => 'P3', 'est' => 4, 'sort' => 4]
                ]
            ],
            [
                'name' => 'Recruitment Pipeline',
                'description' => 'Workflow for hiring candidates.',
                'category' => 'HR',
                'items' => [
                    ['title' => 'Post job vacancy on boards & social media', 'priority' => 'P2', 'est' => 3, 'sort' => 0],
                    ['title' => 'Screen resumes & filter top applications', 'priority' => 'P2', 'est' => 5, 'sort' => 2],
                    ['title' => 'Conduct initial HR phone interview', 'priority' => 'P3', 'est' => 4, 'sort' => 4],
                    ['title' => 'Schedule Technical Evaluation Assessment', 'priority' => 'P1', 'est' => 4, 'sort' => 6],
                    ['title' => 'Final interview panel & offer negotiation', 'priority' => 'P1', 'est' => 3, 'sort' => 8]
                ]
            ],
            [
                'name' => 'Payroll Processing Cycle',
                'description' => 'Monthly payroll computation tasks.',
                'category' => 'Finance',
                'items' => [
                    ['title' => 'Collect and approve timesheets & attendance records', 'priority' => 'P1', 'est' => 4, 'sort' => 0],
                    ['title' => 'Calculate bonuses, deduct leaves & unpaid hours', 'priority' => 'P1', 'est' => 6, 'sort' => 1],
                    ['title' => 'Review tax calculations & social deductions', 'priority' => 'P2', 'est' => 4, 'sort' => 2],
                    ['title' => 'Draft salary payments register and obtain approval', 'priority' => 'P1', 'est' => 2, 'sort' => 3],
                    ['title' => 'Release payments & email salary payslips', 'priority' => 'P1', 'est' => 3, 'sort' => 4]
                ]
            ],
            [
                'name' => 'Release Management Pipeline',
                'description' => 'Software deployment workflow tasks.',
                'category' => 'Engineering',
                'items' => [
                    ['title' => 'Create branch and freeze staging codebase', 'priority' => 'P1', 'est' => 2, 'sort' => 0],
                    ['title' => 'Execute regression test suite & fix blocker bugs', 'priority' => 'P1', 'est' => 8, 'sort' => 1],
                    ['title' => 'Draft release notes & documentation changes', 'priority' => 'P3', 'est' => 3, 'sort' => 3],
                    ['title' => 'Deploy to production environment', 'priority' => 'P1', 'est' => 2, 'sort' => 4],
                    ['title' => 'Sanity test production & monitor application health', 'priority' => 'P1', 'est' => 4, 'sort' => 5]
                ]
            ],
            [
                'name' => 'Bug Resolution Workflow',
                'description' => 'Sequence for resolving software bugs.',
                'category' => 'QA',
                'items' => [
                    ['title' => 'Reproduce reported issue & create test case', 'priority' => 'P2', 'est' => 2, 'sort' => 0],
                    ['title' => 'Analyze codebase & develop bugfix patch', 'priority' => 'P1', 'est' => 4, 'sort' => 1],
                    ['title' => 'Peer review fix code & verify tests pass', 'priority' => 'P2', 'est' => 2, 'sort' => 2],
                    ['title' => 'Merge branch and deploy release to staging', 'priority' => 'P2', 'est' => 1, 'sort' => 3],
                    ['title' => 'Notify reporter and close tickets', 'priority' => 'P3', 'est' => 1, 'sort' => 4]
                ]
            ]
        ];

        foreach ($templates as $t) {
            $tpl = TaskTemplate::updateOrCreate(
                ['company_id' => $companyId, 'name' => $t['name']],
                [
                    'description' => $t['description'],
                    'category' => $t['category'],
                    'is_global' => true,
                    'created_by' => $userId
                ]
            );

            // Clean existing items if any
            TaskTemplateItem::where('template_id', $tpl->id)->delete();

            foreach ($t['items'] as $item) {
                TaskTemplateItem::create([
                    'template_id' => $tpl->id,
                    'title' => $item['title'],
                    'description' => $item['title'],
                    'priority' => $item['priority'],
                    'estimated_hours' => $item['est'],
                    'sort_order' => $item['sort']
                ]);
            }
        }
    }
}
