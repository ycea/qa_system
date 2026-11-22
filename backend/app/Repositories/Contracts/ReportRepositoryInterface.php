<?php

namespace App\Repositories\Contracts;

use App\Models\Bug;
use App\Models\Project;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface ReportRepositoryInterface
{
    /**
     * Количество багов, созданных в интервале.
     */
    public function countCreatedBetween(Project $project, CarbonInterface $from, CarbonInterface $to): int;

    /**
     * Закрытые в интервале баги (для расчёта среднего времени жизни).
     *
     * @return Collection<int, Bug>
     */
    public function closedBugsBetween(Project $project, CarbonInterface $from, CarbonInterface $to): Collection;
}
