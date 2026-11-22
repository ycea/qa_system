<?php

namespace App\Services;

use App\Models\Bug;
use App\Models\Project;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Carbon\CarbonInterface;

class ReportService
{
    public function __construct(private readonly ReportRepositoryInterface $reports) {}

    /**
     * Аналитический отчёт по проекту за период (REPT-01).
     *
     * @return array<string, mixed>
     */
    public function build(Project $project, string $period): array
    {
        $to = now();
        $from = $this->periodStart($period, $to);

        $total = $this->reports->countCreatedBetween($project, $from, $to);
        $closedBugs = $this->reports->closedBugsBetween($project, $from, $to);
        $closed = $closedBugs->count();

        // Среднее время жизни: только закрытые за период баги.
        $avgLifetime = $closed === 0
            ? 0.0
            : round($closedBugs->sum(function (Bug $bug) {
                return (float) $bug->created_at->diffInHours($bug->closed_at);
            }) / $closed, 2);

        return [
            'period' => $period,
            'total' => $total,
            'closed' => $closed,
            'closed_percent' => $total === 0 ? 0.0 : round($closed / $total * 100, 2),
            'avg_lifetime_hours' => $avgLifetime,
        ];
    }

    private function periodStart(string $period, CarbonInterface $to): CarbonInterface
    {
        return match ($period) {
            'day' => $to->copy()->subDay(),
            'month' => $to->copy()->subMonth(),
            default => $to->copy()->subWeek(),
        };
    }
}
