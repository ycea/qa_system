<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Просмотр проекта — владелец или участник.
     */
    public function view(User $user, Project $project): bool
    {
        if ($project->owner_id === $user->id) {
            return true;
        }

        return $project->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Управление участниками (добавление/удаление) — только админ проекта.
     */
    public function manageMembers(User $user, Project $project): bool
    {
        if ($project->owner_id === $user->id) {
            return true;
        }

        return $project->members()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    public function delete(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id;
    }
}
