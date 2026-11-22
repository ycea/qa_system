<?php

namespace App\Repositories\Contracts;

use App\Models\Bug;
use App\Models\BugHistory;
use App\Models\User;
use Illuminate\Support\Collection;

interface BugHistoryRepositoryInterface
{
    /**
     * @return Collection<int, BugHistory>
     */
    public function listForBug(Bug $bug): Collection;

    /**
     * Добавляет запись в ленту истории изменений бага.
     */
    public function add(
        Bug $bug,
        User $user,
        string $action,
        ?string $field = null,
        ?string $oldValue = null,
        ?string $newValue = null,
    ): BugHistory;
}
