<?php

namespace App\Repositories\EnterpriseTasks;

use App\Models\EnterpriseTasks\SavedView;

class SavedViewRepository
{
    public function getById(int $id): ?SavedView
    {
        return SavedView::find($id);
    }

    public function getForUser(int $userId)
    {
        return SavedView::where('user_id', $userId)->get();
    }

    public function create(array $data): SavedView
    {
        return SavedView::create($data);
    }

    public function delete(SavedView $view): ?bool
    {
        return $view->delete();
    }
}
