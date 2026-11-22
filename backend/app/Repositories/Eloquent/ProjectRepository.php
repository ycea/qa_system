<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Support\Collection;

class ProjectRepository implements ProjectRepositoryInterface
{
    public function listForUser(User $user): Collection
    {
        $memberProjects = $user->projects()->withCount('bugs')->get();
        $ownedProjects = $user->ownedProjects()->withCount('bugs')->get();

        return $memberProjects->merge($ownedProjects)->unique('id')->values();
    }

    public function create(array $attributes): Project
    {
        return Project::create($attributes);
    }

    public function loadWithMembersAndBugCount(Project $project): Project
    {
        return $project->load('members')->loadCount('bugs');
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }

    public function members(Project $project): Collection
    {
        return $project->members()
            ->select('users.id', 'users.name', 'users.email')
            ->withPivot('role')
            ->get();
    }

    public function hasMember(Project $project, User $user): bool
    {
        return $project->members()->where('user_id', $user->id)->exists();
    }

    public function attachMember(Project $project, User $user, string $role): void
    {
        $project->members()->attach($user->id, ['role' => $role]);
    }

    public function detachMember(Project $project, User $user): void
    {
        $project->members()->detach($user->id);
    }
}
