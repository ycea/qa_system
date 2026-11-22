<?php

namespace App\Services;

use App\Models\Project;
use App\Repositories\Contracts\DashboardRepositoryInterface;

class DashboardService
{
    /**
     * Статусы, при которых баг считается открытым (DASH-01).
     */
    private const OPEN_STATUSES = ['Новый', 'Назначен', 'В работе'];

    public function __construct(private readonly DashboardRepositoryInterface $dashboard) {}

    /**
     * Виджеты статистики проекта (DASH-01).
     *
     * @return array{total: int, open: int, closed_week: int}
     */
    public function stats(Project $project): array
    {
        return [
            'total' => $this->dashboard->totalBugs($project),
            'open' => $this->dashboard->bugsWithStatusNames($project, self::OPEN_STATUSES),
            'closed_week' => $this->dashboard->bugsClosedSince($project, now()->subDays(7)),
        ];
    }
}
