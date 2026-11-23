<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProjectService
{
    public function __construct(private readonly ProjectRepositoryInterface $projects) {}

    /**
     * Список проектов пользователя (PROJ-02) с пагинацией.
     */
    public function listForUser(User $user, int $perPage): LengthAwarePaginator
    {
        return $this->projects->paginateForUser($user, $perPage);
    }

    /**
     * Создание проекта (PROJ-01). Создатель становится администратором.
     *
     * @param  array{name: string, description?: string|null}  $data
     */
    public function create(User $user, array $data): Project
    {
        $project = $this->projects->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'owner_id' => $user->id,
        ]);

        $this->projects->attachMember($project, $user, 'admin');

        return $project;
    }

    /**
     * Просмотр проекта с участниками и количеством багов.
     */
    public function getForUser(Project $project): Project
    {
        return $this->projects->loadWithMembersAndBugCount($project);
    }

    public function delete(Project $project): void
    {
        $this->projects->delete($project);
    }
}
