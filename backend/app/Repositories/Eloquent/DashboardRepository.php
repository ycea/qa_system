<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use DateTimeInterface;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function totalBugs(Project $project): int
    {
        return $project->bugs()->count();
    }

    public function bugsWithStatusNames(Project $project, array $names): int
    {
        return $project->bugs()
            ->whereHas('status', fn ($query) => $query->whereIn('name', $names))
            ->count();
    }

    public function bugsClosedSince(Project $project, DateTimeInterface $since): int
    {
        return $project->bugs()
            ->whereNotNull('closed_at')
            ->where('closed_at', '>=', $since)
            ->count();
    }
}
