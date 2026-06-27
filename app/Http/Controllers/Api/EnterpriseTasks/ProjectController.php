<?php

namespace App\Http\Controllers\Api\EnterpriseTasks;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\EnterpriseTasks\ProjectSection;
use App\Models\EnterpriseTasks\Label;
use App\Models\EnterpriseTasks\Task;
use App\Models\User;
use App\Classes\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\EnterpriseTasks\ProjectService;

class ProjectController extends Controller
{
    protected ProjectService $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }

    public function indexProjects()
    {
        $companyId = company()->id;
        $user = user();

        $projects = Project::where('company_id', $companyId)
            ->where(function($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhereJsonContains('members', $user->xid);
            })
            ->with(['creator'])
            ->get();

        $projectsData = [];
        foreach ($projects as $project) {
            $projectArray = $project->toArray();
            
            // Fetch tasks for this project
            $tasks = Task::where('project_id', $project->id)->where('is_deleted', false)->get();
            $completed = $tasks->where('status', 'completed')->count();
            $pending = $tasks->where('status', '!=', 'completed')->count();
            
            $today = Carbon::today();
            $overdue = $tasks->filter(function($t) use ($today) {
                return $t->due_date && Carbon::parse($t->due_date)->lt($today) && $t->status !== 'completed';
            })->count();

            // Set fallback fields
            $projectArray['color'] = $project->color ?? '#3b82f6';
            $projectArray['visibility'] = $project->visibility ?? 'public';
            $projectArray['status'] = $project->status ?? 'active';

            // Format stats
            $projectArray['total_tasks'] = $tasks->count();
            $projectArray['completed_tasks'] = $completed;
            $projectArray['pending_tasks'] = $pending;
            $projectArray['overdue_tasks'] = $overdue;
            $projectArray['productivity_percentage'] = $tasks->count() > 0 ? round(($completed / $tasks->count()) * 100) : 0;
            
            // Format owner
            $projectArray['owner'] = $project->creator ? $project->creator->toArray() : null;

            // Format members
            $formattedMembers = [];
            $memberHashes = $project->members;
            if (is_array($memberHashes)) {
                $userIds = array_map(fn($hash) => Common::getIdFromHash($hash), $memberHashes);
                $users = User::whereIn('id', $userIds)->get();
                foreach ($users as $u) {
                    $formattedMembers[] = [
                        'role' => ($u->id == $project->created_by) ? 'owner' : 'member',
                        'user' => [
                            'xid' => $u->xid,
                            'name' => $u->name,
                            'profile_image_url' => $u->profile_image_url
                        ]
                    ];
                }
            }
            $projectArray['members_count'] = count($formattedMembers);
            $projectArray['members'] = $formattedMembers;

            $projectsData[] = $projectArray;
        }

        return response()->json($projectsData);
    }

    public function showProject($id)
    {
        $projectId = Common::getIdFromHash($id);
        $project = Project::with(['creator', 'sections'])->findOrFail($projectId);

        $tasks = Task::where('project_id', $project->id)->where('is_deleted', false)->get();
        $completed = $tasks->where('status', 'completed')->count();
        $pending = $tasks->where('status', '!=', 'completed')->count();
        $today = Carbon::today();
        $overdue = $tasks->filter(function($t) use ($today) {
            return $t->due_date && Carbon::parse($t->due_date)->lt($today) && $t->status !== 'completed';
        })->count();

        // Format owner & members
        $formattedMembers = [];
        $memberHashes = $project->members;
        if (is_array($memberHashes)) {
            $userIds = array_map(fn($hash) => Common::getIdFromHash($hash), $memberHashes);
            $users = User::whereIn('id', $userIds)->get();
            foreach ($users as $u) {
                $formattedMembers[] = [
                    'role' => ($u->id == $project->created_by) ? 'owner' : 'member',
                    'user' => [
                        'xid' => $u->xid,
                        'name' => $u->name,
                        'profile_image_url' => $u->profile_image_url
                    ]
                ];
            }
        }

        $projectArray = $project->toArray();
        $projectArray['color'] = $project->color ?? '#3b82f6';
        $projectArray['visibility'] = $project->visibility ?? 'public';
        $projectArray['status'] = $project->status ?? 'active';
        $projectArray['owner'] = $project->creator ? $project->creator->toArray() : null;
        $projectArray['members'] = $formattedMembers;

        return response()->json([
            'project' => $projectArray,
            'analytics' => [
                'total_tasks' => $tasks->count(),
                'completed_tasks' => $completed,
                'pending_tasks' => $pending,
                'overdue_tasks' => $overdue,
                'productivity_percentage' => $tasks->count() > 0 ? round(($completed / $tasks->count()) * 100) : 0,
                'members_count' => count($formattedMembers)
            ]
        ]);
    }

    public function storeSection(Request $request, $projectId)
    {
        $projId = Common::getIdFromHash($projectId);
        $project = Project::findOrFail($projId);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $sortOrder = ProjectSection::where('project_id', $project->id)->max('sort_order') + 1;

        $section = ProjectSection::create([
            'project_id' => $project->id,
            'name' => $request->name,
            'sort_order' => $sortOrder
        ]);

        return response()->json($section);
    }

    public function updateSection(Request $request, $id)
    {
        $sectionId = Common::getIdFromHash($id);
        $section = ProjectSection::findOrFail($sectionId);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $section->update(['name' => $request->name]);
        return response()->json($section);
    }

    public function destroySection($id)
    {
        $sectionId = Common::getIdFromHash($id);
        $section = ProjectSection::findOrFail($sectionId);
        $section->delete();
        return response()->json(['success' => true]);
    }

    public function reorderSections(Request $request, $projectId)
    {
        $projId = Common::getIdFromHash($projectId);
        
        $validator = Validator::make($request->all(), [
            'sections' => 'required|array',
            'sections.*.xid' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        foreach ($request->sections as $index => $secData) {
            $secId = Common::getIdFromHash($secData['xid']);
            ProjectSection::where('id', $secId)->where('project_id', $projId)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    public function indexLabels()
    {
        $companyId = company()->id;
        $labels = Label::where('company_id', $companyId)->get();
        return response()->json($labels);
    }

    public function storeLabel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'color' => 'required|string|max:10'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $companyId = company()->id;

        $label = Label::create([
            'company_id' => $companyId,
            'name' => $request->name,
            'color' => $request->color
        ]);

        return response()->json($label);
    }

    public function updateLabel(Request $request, $id)
    {
        $labelId = Common::getIdFromHash($id);
        $label = Label::findOrFail($labelId);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'color' => 'required|string|max:10'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $label->update($request->only('name', 'color'));
        return response()->json($label);
    }

    public function destroyLabel($id)
    {
        $labelId = Common::getIdFromHash($id);
        $label = Label::findOrFail($labelId);
        $label->delete();
        return response()->json(['success' => true]);
    }
}
