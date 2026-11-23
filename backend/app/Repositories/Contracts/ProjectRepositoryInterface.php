<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ProjectRepositoryInterface
{
    /**
     * Постраничный список проектов пользователя (как участника и как владельца)
     * с количеством багов.
     */
    public function paginateForUser(User $user, int $perPage): LengthAwarePaginator;

    public function create(array $attributes): Project;

    /**
     * Загружает участников и количество багов у проекта.
     */
    public function loadWithMembersAndBugCount(Project $project): Project;

    public function delete(Project $project): void;

    /**
     * Участники проекта с ролью (pivot).
     *
     * @return Collection<int, User>
     */
    public function members(Project $project): Collection;

    public function hasMember(Project $project, User $user): bool;

    public function attachMember(Project $project, User $user, string $role): void;

    public function detachMember(Project $project, User $user): void;
}
