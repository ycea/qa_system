<?php

namespace App\Policies;

use App\Models\Bug;
use App\Models\User;

class BugPolicy
{
    /**
     * Просмотр бага — участник или владелец проекта.
     */
    public function view(User $user, Bug $bug): bool
    {
        return $this->isProjectMember($user, $bug);
    }

    /**
     * Редактирование бага — любой участник проекта (в т.ч. смена статуса/исполнителя).
     */
    public function update(User $user, Bug $bug): bool
    {
        return $this->isProjectMember($user, $bug);
    }

    private function isProjectMember(User $user, Bug $bug): bool
    {
        $project = $bug->project;

        if ($project->owner_id === $user->id) {
            return true;
        }

        return $project->members()->where('user_id', $user->id)->exists();
    }
}
