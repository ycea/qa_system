<?php

namespace Tests\Unit\Services;

use App\Exceptions\DomainException;
use App\Models\Bug;
use App\Models\BugHistory;
use App\Models\Metric;
use App\Models\MetricValue;
use App\Models\Project;
use App\Models\Status;
use App\Models\User;
use App\Repositories\Contracts\BugHistoryRepositoryInterface;
use App\Repositories\Contracts\BugRepositoryInterface;
use App\Repositories\Contracts\MetricRepositoryInterface;
use App\Repositories\Contracts\MetricValueRepositoryInterface;
use App\Services\BugService;
use App\Services\MetricService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class BugServiceTest extends TestCase
{
    private BugRepositoryInterface&MockInterface $bugRepo;

    private BugHistoryRepositoryInterface&MockInterface $historyRepo;

    private BugService $service;

    private Project $project;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bugRepo = Mockery::mock(BugRepositoryInterface::class);
        $this->historyRepo = Mockery::mock(BugHistoryRepositoryInterface::class);
        $this->service = new BugService($this->bugRepo, $this->historyRepo);

        $this->project = new Project;
        $this->project->id = 1;

        $this->user = new User;
        $this->user->id = 5;
        $this->user->name = 'Иван';
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // 1. Покрытие метода list()
    // ==========================================
    public function test_list_returns_paginated_bugs(): void
    {
        $filters = ['status_id' => 1];
        $perPage = 15;
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $this->bugRepo->shouldReceive('paginateForProject')
            ->once()
            ->with($this->project, $filters, $perPage)
            ->andReturn($paginator);

        $result = $this->service->list($this->project, $filters, $perPage);

        $this->assertSame($paginator, $result);
    }

    // ==========================================
    // 2. Покрытие метода get()
    // ==========================================
    public function test_get_returns_bug_details_successfully(): void
    {
        $bug = new Bug;
        $bug->id = 10;
        $bug->project_id = 1;

        $loadedBug = new Bug;
        $loadedBug->id = 10;
        $loadedBug->project_id = 1;

        $this->bugRepo->shouldReceive('loadForDetails')
            ->once()
            ->with($bug)
            ->andReturn($loadedBug);

        $result = $this->service->get($this->project, $bug);

        $this->assertSame($loadedBug, $result);
    }

    // ==========================================
    // 3. Покрытие метода update() (дополнение)
    // ==========================================
    public function test_update_tracks_severity_id_changes_and_resolves_dictionary_names(): void
    {
        $project = Mockery::mock(Project::class)->makePartial();
        $project->id = 1;

        $severityRelation = Mockery::mock(HasMany::class);
        $severityRelation->shouldReceive('find')->with(1)->andReturn((object) ['name' => 'Низкая']);
        $severityRelation->shouldReceive('find')->with(3)->andReturn((object) ['name' => 'Критическая']);
        $project->shouldReceive('severities')->andReturn($severityRelation);

        $bug = new Bug;
        $bug->id = 10;
        $bug->project_id = 1;
        $bug->severity_id = 1;

        $updatedBug = new Bug;
        $updatedBug->id = 10;
        $updatedBug->project_id = 1;
        $updatedBug->severity_id = 3;

        $input = ['severity_id' => 3];

        $this->bugRepo->shouldReceive('update')
            ->once()
            ->with($bug, ['severity_id' => 3])
            ->andReturn($updatedBug);

        $this->historyRepo->shouldReceive('add')
            ->once()
            ->with($updatedBug, $this->user, 'updated', 'severity_id', 'Низкая', 'Критическая');

        $result = $this->service->update($project, $bug, $this->user, $input);

        $this->assertSame($updatedBug, $result);
    }

    // ==========================================
    // 4. Покрытие метода changeStatus() (все ветки)
    // ==========================================
    public function test_change_status_clears_closed_at_when_moving_to_open_status(): void
    {
        $now = Carbon::parse('2026-11-23 12:00:00');
        Carbon::setTestNow($now);

        $oldStatus = new Status;
        $oldStatus->id = 2;
        $oldStatus->name = 'Закрыт';
        $oldStatus->is_closed = true;

        $openStatus = new Status;
        $openStatus->id = 1;
        $openStatus->name = 'В работе';
        $openStatus->is_closed = false;

        $project = Mockery::mock(Project::class)->makePartial();
        $project->id = 1;

        $statusRelation = Mockery::mock(HasMany::class);
        $statusRelation->shouldReceive('findOrFail')->with(1)->once()->andReturn($openStatus);
        $project->shouldReceive('statuses')->andReturn($statusRelation);

        $bug = Mockery::mock(Bug::class)->makePartial();
        $bug->project_id = 1;
        $bug->status_id = 2;
        $bug->closed_at = $now; // Был закрыт
        $bug->setRelation('status', $oldStatus);

        $bug->shouldReceive('save')->once()->andReturn(true);
        $bug->shouldReceive('load')->with('status')->once()->andReturnSelf();

        $this->historyRepo->shouldReceive('add')
            ->once()
            ->with($bug, $this->user, 'status_changed', 'status', 'Закрыт', 'В работе');

        $this->service->changeStatus($project, $bug, $this->user, 1);

        $this->assertEquals(1, $bug->status_id);
        $this->assertNull($bug->closed_at);
    }

    public function test_change_status_preserves_closed_at_when_moving_between_closed_statuses(): void
    {
        $originalClosedAt = Carbon::parse('2026-11-20 10:00:00');
        $now = Carbon::parse('2026-11-23 12:00:00');
        Carbon::setTestNow($now);

        $oldStatus = new Status;
        $oldStatus->id = 2;
        $oldStatus->name = 'Закрыт';
        $oldStatus->is_closed = true;

        $newClosedStatus = new Status;
        $newClosedStatus->id = 3;
        $newClosedStatus->name = 'Отклонен';
        $newClosedStatus->is_closed = true;

        $project = Mockery::mock(Project::class)->makePartial();
        $project->id = 1;

        $statusRelation = Mockery::mock(HasMany::class);
        $statusRelation->shouldReceive('findOrFail')->with(3)->once()->andReturn($newClosedStatus);
        $project->shouldReceive('statuses')->andReturn($statusRelation);

        $bug = Mockery::mock(Bug::class)->makePartial();
        $bug->project_id = 1;
        $bug->status_id = 2;
        $bug->closed_at = $originalClosedAt;
        $bug->setRelation('status', $oldStatus);

        $bug->shouldReceive('save')->once()->andReturn(true);
        $bug->shouldReceive('load')->with('status')->once()->andReturnSelf();

        $this->historyRepo->shouldReceive('add')
            ->once()
            ->with($bug, $this->user, 'status_changed', 'status', 'Закрыт', 'Отклонен');

        $this->service->changeStatus($project, $bug, $this->user, 3);

        $this->assertEquals(3, $bug->status_id);
        // Дата закрытия должна остаться исходной, а не обновиться на $now
        $this->assertTrue($originalClosedAt->equalTo($bug->closed_at));
    }

    public function test_change_status_does_nothing_when_status_is_the_same(): void
    {
        $status = new Status;
        $status->id = 1;
        $status->name = 'В работе';
        $status->is_closed = false;

        $project = Mockery::mock(Project::class)->makePartial();
        $project->id = 1;

        $statusRelation = Mockery::mock(HasMany::class);
        $statusRelation->shouldReceive('findOrFail')->with(1)->once()->andReturn($status);
        $project->shouldReceive('statuses')->andReturn($statusRelation);

        $bug = Mockery::mock(Bug::class)->makePartial();
        $bug->project_id = 1;
        $bug->status_id = 1;
        $bug->setRelation('status', $status);

        $bug->shouldNotReceive('save');
        $this->historyRepo->shouldNotReceive('add');

        $result = $this->service->changeStatus($project, $bug, $this->user, 1);

        $this->assertSame($bug, $result);
    }

    // ==========================================
    // 5. Покрытие метода assign() (дополнение)
    // ==========================================
    public function test_assign_unassigns_user_when_assignee_id_is_null(): void
    {
        $project = Mockery::mock(Project::class)->makePartial();
        $project->id = 1;

        $oldAssignee = new User;
        $oldAssignee->id = 4;
        $oldAssignee->name = 'Олег';

        $bug = Mockery::mock(Bug::class)->makePartial();
        $bug->project_id = 1;
        $bug->assignee_id = 4;
        $bug->setRelation('assignee', $oldAssignee);

        $bug->shouldReceive('save')->once()->andReturn(true);
        $bug->shouldReceive('load')->with('assignee')->once()->andReturnSelf();

        $this->historyRepo->shouldReceive('add')
            ->once()
            ->with($bug, $this->user, 'assignee_assigned', 'assignee', 'Олег', null);

        $result = $this->service->assign($project, $bug, $this->user, null);

        $this->assertNull($bug->assignee_id);
        $this->assertSame($bug, $result);
    }

    // ==========================================
    // 6. Покрытие метода history()
    // ==========================================
    public function test_history_returns_bug_history_collection(): void
    {
        $bug = new Bug;
        $bug->id = 10;
        $bug->project_id = 1;

        $historyCollection = new Collection([
            Mockery::mock(BugHistory::class),
        ]);

        $this->historyRepo->shouldReceive('listForBug')
            ->once()
            ->with($bug)
            ->andReturn($historyCollection);

        $result = $this->service->history($this->project, $bug);

        $this->assertSame($historyCollection, $result);
    }

    // ==========================================
    // Существующие тесты (оставлены без изменений)
    // ==========================================
    public function test_create_saves_bug_and_adds_history_record(): void
    {
        $data = ['title' => 'Ошибка 500'];
        $createdBug = new Bug;
        $createdBug->id = 10;
        $createdBug->project_id = 1;

        $this->bugRepo->shouldReceive('create')
            ->once()
            ->with($this->project, $this->user, $data)
            ->andReturn($createdBug);

        $this->historyRepo->shouldReceive('add')
            ->once()
            ->with($createdBug, $this->user, 'created');

        $result = $this->service->create($this->project, $this->user, $data);

        $this->assertSame($createdBug, $result);
    }

    // ==========================================
    // 7. Покрытие метода setValue (исправленные тесты)
    // ==========================================
    public function test_set_value_numeric_successfully(): void
    {
        $project = new Project;
        $project->id = 1;

        $bug = new Bug;
        $bug->id = 10;
        $bug->project_id = 1;

        $user = new User;
        $user->id = 5;

        $metric = new Metric;
        $metric->id = 2;
        $metric->project_id = 1;
        $metric->type = 'numeric';

        $metricRepo = Mockery::mock(MetricRepositoryInterface::class);
        $valueRepo = Mockery::mock(MetricValueRepositoryInterface::class);

        $expectedMetricValue = new MetricValue;
        $expectedMetricValue->numeric_value = 42.5;

        $valueRepo->shouldReceive('upsert')
            ->with($bug, $metric, $user, ['numeric_value' => 42.5])
            ->andReturn($expectedMetricValue);

        $service = new MetricService($metricRepo, $valueRepo);
        $result = $service->setValue($project, $bug, $metric, $user, ['numeric_value' => 42.5]);

        $this->assertSame($expectedMetricValue, $result);
    }

    public function test_set_value_throws_domain_exception_if_bug_not_in_project(): void
    {
        $project = new Project;
        $project->id = 1;

        $bug = new Bug;
        $bug->id = 10;
        $bug->project_id = 99; // Баг из другого проекта

        $user = new User;
        $user->id = 1;

        $metric = new Metric;
        $metric->project_id = 1;
        $metric->type = 'numeric';

        $metricRepo = Mockery::mock(MetricRepositoryInterface::class);
        $valueRepo = Mockery::mock(MetricValueRepositoryInterface::class);
        $service = new MetricService($metricRepo, $valueRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Баг не найден.');
        $service->setValue($project, $bug, $metric, $user, ['numeric_value' => 10]);
    }

    public function test_set_value_validates_numeric_is_required(): void
    {
        $project = new Project;
        $project->id = 1;

        $bug = new Bug;
        $bug->project_id = 1;

        $user = new User;
        $user->id = 1;

        $metric = new Metric;
        $metric->project_id = 1;
        $metric->type = 'numeric';

        $metricRepo = Mockery::mock(MetricRepositoryInterface::class);
        $valueRepo = Mockery::mock(MetricValueRepositoryInterface::class);
        $service = new MetricService($metricRepo, $valueRepo);

        $this->expectException(ValidationException::class);
        // Передаем строку вместо числа
        $service->setValue($project, $bug, $metric, $user, ['numeric_value' => 'not-a-number']);
    }

    public function test_set_value_validates_categorical_is_required(): void
    {
        $project = new Project;
        $project->id = 1;

        $bug = new Bug;
        $bug->project_id = 1;

        $user = new User;
        $user->id = 1;

        $metric = new Metric;
        $metric->project_id = 1;
        $metric->type = 'categorical';
        $metric->options = ['A', 'B'];

        $metricRepo = Mockery::mock(MetricRepositoryInterface::class);
        $valueRepo = Mockery::mock(MetricValueRepositoryInterface::class);
        $service = new MetricService($metricRepo, $valueRepo);

        $this->expectException(ValidationException::class);
        // Передаем пустую строку
        $service->setValue($project, $bug, $metric, $user, ['categorical_value' => '   ']);
    }

    // ==========================================
    // 8. Покрытие граничных случаев analytics и reportData (пустые коллекции)
    // ==========================================
    public function test_analytics_handles_empty_values_gracefully(): void
    {
        $project = new Project(['id' => 1]);
        $metric = new Metric(['id' => 1, 'name' => 'Empty Metric', 'type' => 'numeric', 'options' => null]);

        $metricRepo = Mockery::mock(MetricRepositoryInterface::class);
        $valueRepo = Mockery::mock(MetricValueRepositoryInterface::class);

        $metricRepo->shouldReceive('listForProject')->with($project)->andReturn(new Collection([$metric]));
        $valueRepo->shouldReceive('valuesForMetric')->with($metric)->andReturn(new Collection([]));

        $service = new MetricService($metricRepo, $valueRepo);
        $analytics = $service->analytics($project);

        $this->assertEquals(0, $analytics[0]['count']);
        $this->assertEquals(['avg' => 0.0, 'min' => 0.0, 'max' => 0.0, 'sum' => 0.0], $analytics[0]['indicators']);
        $this->assertEquals([], $analytics[0]['by_status']);
    }

    public function test_report_data_handles_empty_values_and_missing_status(): void
    {
        $project = new Project(['id' => 1]);
        $metric = new Metric(['id' => 1, 'name' => 'No Data Metric', 'type' => 'categorical', 'options' => ['A']]);

        $metricRepo = Mockery::mock(MetricRepositoryInterface::class);
        $valueRepo = Mockery::mock(MetricValueRepositoryInterface::class);

        $metricRepo->shouldReceive('listForProject')->with($project)->andReturn(new Collection([$metric]));
        $valueRepo->shouldReceive('valuesForMetric')->with($metric)->andReturn(new Collection([]));

        $service = new MetricService($metricRepo, $valueRepo);
        $report = $service->reportData($project);

        $this->assertCount(1, $report['summary']);
        $this->assertEquals('—', $report['summary'][0]['distribution']); // Проверка приватного метода distributionText
        $this->assertEquals([], $report['by_status']);
    }
}
