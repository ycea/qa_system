<?php

namespace App\Repositories\Eloquent;

use App\Models\Bug;
use App\Models\BugHistory;
use App\Models\User;
use App\Repositories\Contracts\BugHistoryRepositoryInterface;
use Illuminate\Support\Collection;

class BugHistoryRepository implements BugHistoryRepositoryInterface
{
    public function listForBug(Bug $bug): Collection
    {
        return $bug->histories()->with('user')->orderBy('created_at')->orderBy('id')->get();
    }

    public function add(
        Bug $bug,
        User $user,
        string $action,
        ?string $field = null,
        ?string $oldValue = null,
        ?string $newValue = null,
    ): BugHistory {
        return $bug->histories()->create([
            'user_id' => $user->id,
            'action' => $action,
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);
    }
}
