<?php

namespace Tests\Unit\Services;

use App\Models\Bug;
use App\Models\Metric;
use App\Models\MetricValue;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\MetricRepositoryInterface;
use App\Repositories\Contracts\MetricValueRepositoryInterface;
use App\Services\MetricService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\TestCase;

class MetricServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_set_value_validates_categorical_options(): void
    {
        $project = new Project(['id' => 1]);
        $bug = new Bug(['project_id' => 1]);
        $user = new User(['id' => 1]);
        $metric = new Metric([
            'project_id' => 1,
            'type' => 'categorical',
            'options' => ['Low', 'High'],
        ]);

        $metricRepo = Mockery::mock(MetricRepositoryInterface::class);
        $valueRepo = Mockery::mock(MetricValueRepositoryInterface::class);

        $service = new MetricService($metricRepo, $valueRepo);

        $this->expectException(ValidationException::class);
        $service->setValue($project, $bug, $metric, $user, ['categorical_value' => 'InvalidOption']);
    }

    public function test_analytics_calculates_numeric_indicators(): void
    {
        $project = new Project(['id' => 1]);
        $metric = new Metric([
            'id' => 5,
            'name' => 'Время фикса',
            'type' => 'numeric',
            'options' => null,
        ]);

        $v1 = new MetricValue(['numeric_value' => 10]);
        $v2 = new MetricValue(['numeric_value' => 20]);

        $metricRepo = Mockery::mock(MetricRepositoryInterface::class);
        $valueRepo = Mockery::mock(MetricValueRepositoryInterface::class);

        $metricRepo->shouldReceive('listForProject')->with($project)->andReturn(new Collection([$metric]));
        $valueRepo->shouldReceive('valuesForMetric')->with($metric)->andReturn(new Collection([$v1, $v2]));

        $service = new MetricService($metricRepo, $valueRepo);
        $analytics = $service->analytics($project);

        $this->assertCount(1, $analytics);
        $this->assertEquals(2, $analytics[0]['count']);
        $this->assertEquals([
            'avg' => 15.0,
            'min' => 10.0,
            'max' => 20.0,
            'sum' => 30.0,
        ], $analytics[0]['indicators']);
    }
}
