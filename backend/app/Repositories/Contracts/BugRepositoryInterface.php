<?php

namespace App\Repositories\Contracts;

use App\Models\Bug;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BugRepositoryInterface
{
    /**
     * Постраничный список багов проекта с учётом фильтров и поиска.
     *
     * @param  array{search?: string|null, status_id?: int|null, priority_id?: int|null, severity_id?: int|null, assignee_id?: int|null}  $filters
     */
    public function paginateForProject(Project $project, array $filters, int $perPage): LengthAwarePaginator;

    public function create(Project $project, User $author, array $attributes): Bug;

    public function update(Bug $bug, array $attributes): Bug;

    public function delete(Bug $bug): void;

    /**
     * Загружает связи для страницы деталей (справочники, автор, комментарии, история).
     */
    public function loadForDetails(Bug $bug): Bug;
}
