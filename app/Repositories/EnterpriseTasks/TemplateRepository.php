<?php

namespace App\Repositories\EnterpriseTasks;

use App\Models\EnterpriseTasks\TaskTemplate;
use App\Models\EnterpriseTasks\TaskTemplateItem;

class TemplateRepository
{
    public function getById(int $id): ?TaskTemplate
    {
        return TaskTemplate::with('items')->find($id);
    }

    public function getForCompany(int $companyId)
    {
        return TaskTemplate::where('company_id', $companyId)
            ->orWhere('is_global', true)
            ->with('items')
            ->get();
    }

    public function create(array $data): TaskTemplate
    {
        return TaskTemplate::create($data);
    }

    public function delete(TaskTemplate $template): ?bool
    {
        return $template->delete();
    }

    public function addItem(int $templateId, array $data): TaskTemplateItem
    {
        return TaskTemplateItem::create(array_merge($data, ['template_id' => $templateId]));
    }
}
