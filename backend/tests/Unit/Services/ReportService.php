<?php

namespace Tests\Unit\Services;

use App\Models\Bug;
use App\Models\Project;
use App\Repositories\Contracts\ReportRepositoryInterface;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\TestCase;

class ReportServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();
        parent::tearDown();
    }

    public function test_build_calculates_metrics_correctly(): void
    {
        $now = Carbon::parse('2026-11-23 15:00:00');
        Carbon::setTestNow($now);

        $project = new Project(['id' => 1]);
        $repo = Mockery::mock(ReportRepositoryInterface::class);

        $repo->shouldReceive('countCreatedBetween')
            ->once()
            ->with($project, Mockery::on(fn ($from) => $from->equalTo($now->copy()->subWeek())), Mockery::on(fn ($to) => $to->equalTo($now)))
            ->andReturn(10);

        $bug1 = new Bug;
        $bug1->created_at = Carbon::parse('2026-11-20 10:00:00');
        $bug1->closed_at = Carbon::parse('2026-11-20 14:00:00'); // 4 часа

        $bug2 = new Bug;
        $bug2->created_at = Carbon::parse('2026-11-21 10:00:00');
        $bug2->closed_at = Carbon::parse('2026-11-21 16:00:00'); // 6 часов

        $repo->shouldReceive('closedBugsBetween')
            ->once()
            ->andReturn(new Collection([$bug1, $bug2]));

        $service = new ReportService($repo);
        $result = $service->build($project, 'week');

        $this->assertEquals([
            'period' => 'week',
            'total' => 10,
            'closed' => 2,
            'closed_percent' => 20.0,
            'avg_lifetime_hours' => 5.0,
        ], $result);
    }

    public function test_build_handles_zero_bugs_without_division_by_zero(): void
    {
        $project = new Project(['id' => 1]);
        $repo = Mockery::mock(ReportRepositoryInterface::class);

        $repo->shouldReceive('countCreatedBetween')->andReturn(0);
        $repo->shouldReceive('closedBugsBetween')->andReturn(new Collection);

        $service = new ReportService($repo);
        $result = $service->build($project, 'day');

        $this->assertEquals(0.0, $result['closed_percent']);
        $this->assertEquals(0.0, $result['avg_lifetime_hours']);
    }
}
