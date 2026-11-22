<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ReportRepository implements ReportRepositoryInterface
{
    public function countCreatedBetween(Project $project, CarbonInterface $from, CarbonInterface $to): int
    {
        return $project->bugs()->whereBetween('created_at', [$from, $to])->count();
    }

    public function closedBugsBetween(Project $project, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $project->bugs()
            ->whereNotNull('closed_at')
            ->whereBetween('closed_at', [$from, $to])
            ->get();
    }
}
