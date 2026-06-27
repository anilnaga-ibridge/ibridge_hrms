<?php

namespace App\Repositories\EnterpriseTasks;

use App\Models\EnterpriseTasks\AutomationRule;

class AutomationRepository
{
    public function getById(int $id): ?AutomationRule
    {
        return AutomationRule::find($id);
    }

    public function getActiveRulesForEvent(string $eventName, ?int $projectId = null)
    {
        $query = AutomationRule::where('event_name', $eventName)->where('is_active', true);
        if ($projectId) {
            $query->where(function($q) use ($projectId) {
                $q->where('project_id', $projectId)->orWhereNull('project_id');
            });
        } else {
            $query->whereNull('project_id');
        }
        return $query->get();
    }

    public function create(array $data): AutomationRule
    {
        return AutomationRule::create($data);
    }

    public function update(AutomationRule $rule, array $data): bool
    {
        return $rule->update($data);
    }

    public function delete(AutomationRule $rule): ?bool
    {
        return $rule->delete();
    }
}
