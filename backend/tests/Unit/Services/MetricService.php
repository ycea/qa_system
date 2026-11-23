<?php

namespace Tests\Unit\Services;

use App\Exceptions\DomainException;
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

    // ==========================================
    // 1. Покрытие метода list()
    // ==========================================
    public function test_list_returns_metrics_for_project(): void
    {
        $project = new Project(['id' => 1]);
        $metric = new Metric(['id' => 1, 'name' => 'Test Metric']);
        $expectedCollection = new Collection([$metric]);

        $metricRepo = Mockery::mock(MetricRepositoryInterface::class);
        $metricRepo->shouldReceive('listForProject')->with($project)->andReturn($expectedCollection);

        $valueRepo = Mockery::mock(MetricValueRepositoryInterface::class);

        $service = new MetricService($metricRepo, $valueRepo);
        $result = $service->list($project);

        $this->assertSame($expectedCollection, $result);
    }

    // ==========================================
    // 2. Покрытие метода create()
    // ==========================================
    public function test_create_metric_successfully(): void
    {
        $project = new Project(['id' => 1]);
        $data = ['name' => 'New Metric', 'type' => 'numeric'];
        $createdMetric = new Metric(['id' => 1, 'name' => 'New Metric', 'type' => 'numeric', 'options' => null]);

        $metricRepo = Mockery::mock(MetricRepositoryInterface::class);
        $metricRepo->shouldReceive('nameExists')->with($project, 'New Metric', null)->andReturn(false);
        $metricRepo->shouldReceive('create')->with($project, Mockery::on(function ($arg) use ($data) {
            return $arg['name'] === $data['name']
                && $arg['type'] === $data['type']
                && $arg['options'] === null;
        }))->andReturn($createdMetric);

        $valueRepo = Mockery::mock(MetricValueRepositoryInterface::class);

        $service = new MetricService($metricRepo, $valueRepo);
        $result = $service->create($project, $data);

        $this->assertSame($createdMetric, $result);
    }

    public function test_create_categorical_metric_formats_options_with_array_values(): void
    {
        $project = new Project(['id' => 1]);
        // Имитируем данные из формы, где ключи массива могут быть не по порядку
        $data = ['name' => 'Severity', 'type' => 'categorical', 'options' => [1 => 'High', 0 => 'Low']];
        $createdMetric = new Metric(['id' => 2, 'name' => 'Severity', 'type' => 'categorical', 'options' => ['High', 'Low']]);

        $metricRepo = Mockery::mock(MetricRepositoryInterface::class);
        $metricRepo->shouldReceive('nameExists')->with($project, 'Severity', null)->andReturn(false);
        $metricRepo->shouldReceive('create')->with($project, Mockery::on(function ($arg) {
            // Проверяем, что array_values сработал и ключи стали [0 => 'High', 1 => 'Low']
            return $arg['options'] === ['High', 'Low'];
        }))->andReturn($createdMetric);

        $valueRepo = Mockery::mock(MetricValueRepositoryInterface::class);

        $service = new MetricService($metricRepo, $valueRepo);
        $result = $service->create($project, $data);

        $this->assertSame($createdMetric, $result);
    }

    public function test_create_throws_validation_exception_if_name_exists(): void
    {
        $project = new Project(['id' => 1]);
        $data = ['name' => 'Existing Metric', 'type' => 'numeric'];

        $metricRepo = Mockery::mock(MetricRepositoryInterface::class);
        $metricRepo->shouldReceive('nameExists')->with($project, 'Existing Metric', null)->andReturn(true);
        $valueRepo = Mockery::mock(MetricValueRepositoryInterface::class);

        $service = new MetricService($metricRepo, $valueRepo);

        $this->expectException(ValidationException::class);
        $service->create($project, $data);
    }

    // ==========================================
    // 3. Покрытие метода update()
    // ==========================================
    public function test_update_metric_successfully(): void
    {
        $project = new Project(['id' => 1]);
        $metric = new Metric(['id' => 1, 'project_id' => 1, 'name' => 'Old', 'type' => 'numeric']);
        $data = ['name' => 'New', 'type' => 'numeric'];
        $updatedMetric = new Metric(['id' => 1, 'project_id' => 1, 'name' => 'New', 'type' => 'numeric']);

        $metricRepo = Mockery::mock(MetricRepositoryInterface::class);
        $metricRepo->shouldReceive('nameExists')->with($project, 'New', 1)->andReturn(false);
        $metricRepo->shouldReceive('update')->with($metric, Mockery::on(function ($arg) {
            return $arg['name'] === 'New';
        }))->andReturn($updatedMetric);

        $valueRepo = Mockery::mock(MetricValueRepositoryInterface::class);

        $service = new MetricService($metricRepo, $valueRepo);
        $result = $service->update($project, $metric, $data);

        $this->assertSame($updatedMetric, $result);
    }

    public function test_update_throws_domain_exception_if_metric_not_in_project(): void
    {
        $project = new Project(['id' => 1]);
        $metric = new Metric(['id' => 2, 'project_id' => 99]); // Другой проект
        $data = ['name' => 'New', 'type' => 'numeric'];

        $metricRepo = Mockery::mock(MetricRepositoryInterface::class);
        $valueRepo = Mockery::mock(MetricValueRepositoryInterface::class);

        $service = new MetricService($metricRepo, $valueRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Метрика не найдена.');
        $service->update($project, $metric, $data);
    }

    // ==========================================
    // 4. Покрытие метода delete()
    // ==========================================
    public function test_delete_metric_successfully(): void
    {
        $project = new Project(['id' => 1]);
        $metric = new Metric(['id' => 1, 'project_id' => 1]);

        $metricRepo = Mockery::mock(MetricRepositoryInterface::class);
        $metricRepo->shouldReceive('delete')->with($metric)->once();
        $valueRepo = Mockery::mock(MetricValueRepositoryInterface::class);

        $service = new MetricService($metricRepo, $valueRepo);
        $service->delete($project, $metric);

        $this->assertTrue(true); // Если исключение не выброшено и mock вызван, тест пройден
    }

    public function test_delete_throws_domain_exception_if_metric_not_in_project(): void
    {
        $project = new Project(['id' => 1]);
        $metric = new Metric(['id' => 2, 'project_id' => 99]);

        $metricRepo = Mockery::mock(MetricRepositoryInterface::class);
        $valueRepo = Mockery::mock(MetricValueRepositoryInterface::class);

        $service = new MetricService($metricRepo, $valueRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Метрика не найдена.');
        $service->delete($project, $metric);
    }

    // ==========================================
    // 5. Покрытие метода analytics() (дополнение)
    // ==========================================
    public function test_analytics_calculates_categorical_indicators_and_by_status(): void
    {
        $project = new Project(['id' => 1]);
        $metric = new Metric([
            'id' => 2,
            'name' => 'Сложность',
            'type' => 'categorical',
            'options' => ['Low', 'High'],
        ]);

        // Имитируем связанные баги со статусами для проверки byStatus
        $bugOpen = (object) ['status' => (object) ['name' => 'Open']];
        $bugClosed = (object) ['status' => (object) ['name' => 'Closed']];

        $v1 = new MetricValue(['categorical_value' => 'High', 'bug' => $bugOpen]);
        $v2 = new MetricValue(['categorical_value' => 'Low', 'bug' => $bugOpen]);
        $v3 = new MetricValue(['categorical_value' => 'High', 'bug' => $bugClosed]);

        $metricRepo = Mockery::mock(MetricRepositoryInterface::class);
        $valueRepo = Mockery::mock(MetricValueRepositoryInterface::class);

        $metricRepo->shouldReceive('listForProject')->with($project)->andReturn(new Collection([$metric]));
        $valueRepo->shouldReceive('valuesForMetric')->with($metric)->andReturn(new Collection([$v1, $v2, $v3]));

        $service = new MetricService($metricRepo, $valueRepo);
        $analytics = $service->analytics($project);

        $this->assertCount(1, $analytics);
        $this->assertEquals(3, $analytics[0]['count']);

        // Проверка индикаторов для категориальных значений
        $indicators = $analytics[0]['indicators'];
        $this->assertCount(2, $indicators);
        $values = array_column($indicators, 'value');
        $counts = array_column($indicators, 'count');
        $this->assertContains('High', $values);
        $this->assertContains('Low', $values);
        $this->assertContains(2, $counts); // High встречается 2 раза

        // Проверка группировки by_status
        $byStatus = $analytics[0]['by_status'];
        $this->assertCount(2, $byStatus);
        $statusNames = array_column($byStatus, 'status');
        $this->assertContains('Open', $statusNames);
        $this->assertContains('Closed', $statusNames);
    }

    // ==========================================
    // 6. Покрытие метода reportData()
    // ==========================================
    public function test_report_data_generates_correct_summary_and_by_status(): void
    {
        $project = new Project(['id' => 1]);
        $metricNumeric = new Metric(['id' => 1, 'name' => 'Время', 'type' => 'numeric', 'options' => null]);
        $metricCategorical = new Metric(['id' => 2, 'name' => 'Тип', 'type' => 'categorical', 'options' => ['A', 'B']]);

        $bugOpen = (object) ['status' => (object) ['name' => 'Open']];
        $bugClosed = (object) ['status' => (object) ['name' => 'Closed']];

        $v1 = new MetricValue(['numeric_value' => 10.0, 'bug' => $bugOpen]);
        $v2 = new MetricValue(['numeric_value' => 20.0, 'bug' => $bugOpen]);
        $v3 = new MetricValue(['categorical_value' => 'A', 'bug' => $bugClosed]);

        $metricRepo = Mockery::mock(MetricRepositoryInterface::class);
        $valueRepo = Mockery::mock(MetricValueRepositoryInterface::class);

        $metricRepo->shouldReceive('listForProject')->with($project)->andReturn(new Collection([$metricNumeric, $metricCategorical]));

        $valueRepo->shouldReceive('valuesForMetric')->with($metricNumeric)->andReturn(new Collection([$v1, $v2]));
        $valueRepo->shouldReceive('valuesForMetric')->with($metricCategorical)->andReturn(new Collection([$v3]));

        $service = new MetricService($metricRepo, $valueRepo);
        $report = $service->reportData($project);

        // Проверка секции summary
        $this->assertCount(2, $report['summary']);

        // Числовая метрика
        $this->assertEquals('Время', $report['summary'][0]['metric']);
        $this->assertEquals('Числовая', $report['summary'][0]['type']);
        $this->assertEquals(2, $report['summary'][0]['count']);
        $this->assertEquals(15.0, $report['summary'][0]['avg']);
        $this->assertEquals(10.0, $report['summary'][0]['min']);
        $this->assertEquals(20.0, $report['summary'][0]['max']);
        $this->assertEquals(30.0, $report['summary'][0]['sum']);

        // Категориальная метрика (проверка distributionText)
        $this->assertEquals('Тип', $report['summary'][1]['metric']);
        $this->assertEquals('Категориальная', $report['summary'][1]['type']);
        $this->assertEquals('A: 1', $report['summary'][1]['distribution']); // Ожидаемый формат distributionText

        // Проверка секции by_status
        $this->assertCount(2, $report['by_status']);
        $this->assertEquals('Время', $report['by_status'][0]['metric']);
        $this->assertEquals('Open', $report['by_status'][0]['status']);
        $this->assertEquals(15.0, $report['by_status'][0]['avg']);
    }

    // ==========================================
    // Существующие тесты (оставлены без изменений)
    // ==========================================
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
