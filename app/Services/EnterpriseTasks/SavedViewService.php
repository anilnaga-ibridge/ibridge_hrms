<?php

namespace App\Services\EnterpriseTasks;

use App\Repositories\EnterpriseTasks\SavedViewRepository;
use App\DTOs\EnterpriseTasks\SavedViewDTO;
use App\Models\EnterpriseTasks\SavedView;

class SavedViewService
{
    protected SavedViewRepository $repository;

    public function __construct(SavedViewRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getViews(int $companyId, int $userId)
    {
        return $this->repository->getForUser($userId);
    }

    public function createView(SavedViewDTO $dto, int $companyId, int $userId): SavedView
    {
        if ($dto->is_default) {
            // Remove previous defaults for this user
            SavedView::where('user_id', $userId)->update(['is_default' => false]);
        }

        return $this->repository->create(array_merge($dto->toArray(), [
            'company_id' => $companyId,
            'user_id' => $userId
        ]));
    }

    public function updateView(SavedView $view, SavedViewDTO $dto): bool
    {
        if ($dto->is_default) {
            // Remove previous defaults for this user
            SavedView::where('user_id', $view->user_id)
                ->where('id', '!=', $view->id)
                ->update(['is_default' => false]);
        }

        return $this->repository->update($view, $dto->toArray());
    }

    public function deleteView(SavedView $view): bool
    {
        return $this->repository->delete($view);
    }
}
