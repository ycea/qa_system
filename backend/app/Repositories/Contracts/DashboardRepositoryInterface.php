<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use DateTimeInterface;

interface DashboardRepositoryInterface
{
    /**
     * Общее количество багов проекта.
     */
    public function totalBugs(Project $project): int;

    /**
     * Количество багов с одним из указанных названий статусов (открытые).
     *
     * @param  array<int, string>  $names
     */
    public function bugsWithStatusNames(Project $project, array $names): int;

    /**
     * Количество багов, закрытых начиная с указанной даты.
     */
    public function bugsClosedSince(Project $project, DateTimeInterface $since): int;
}
