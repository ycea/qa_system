<?php

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Collection;

class ProjectMemberService
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projects,
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * Список участников проекта (PROJ-04).
     *
     * @return Collection<int, User>
     */
    public function list(Project $project): Collection
    {
        return $this->projects->members($project);
    }

    /**
     * Добавление участника по логину (PROJ-03).
     */
    public function add(Project $project, string $name, string $role): User
    {
        /** @var User $user */
        $user = $this->users->findByNameOrFail($name);

        if ($this->projects->hasMember($project, $user)) {
            throw new DomainException('Пользователь уже является участником проекта.');
        }

        $this->projects->attachMember($project, $user, $role);

        return $user;
    }

    /**
     * Удаление участника (PROJ-05).
     */
    public function remove(Project $project, User $user): void
    {
        if ($project->owner_id === $user->id) {
            throw new DomainException('Нельзя удалить владельца проекта.');
        }

        if (! $this->projects->hasMember($project, $user)) {
            throw new DomainException('Пользователь не является участником проекта.', 404);
        }

        $this->projects->detachMember($project, $user);
    }
}
