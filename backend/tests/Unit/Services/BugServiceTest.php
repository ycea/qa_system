<?php

namespace Tests\Unit\Services;

use App\Exceptions\DomainException;
use App\Models\Bug;
use App\Models\Project;
use App\Models\Status;
use App\Models\User;
use App\Repositories\Contracts\BugHistoryRepositoryInterface;
use App\Repositories\Contracts\BugRepositoryInterface;
use App\Services\BugService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function test_get_throws_domain_exception_when_bug_belongs_to_another_project(): void
    {
        $bug = new Bug;
        $bug->id = 10;
        $bug->project_id = 999;

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Баг не найден.');

        $this->service->get($this->project, $bug);
    }

    public function test_update_tracks_only_changed_fields_and_resolves_dictionary_names(): void
    {
        $project = Mockery::mock(Project::class)->makePartial();
        $project->id = 1;

        $priorityRelation = Mockery::mock(HasMany::class);
        $priorityRelation->shouldReceive('find')->with(1)->andReturn((object) ['name' => 'Низкий']);
        $priorityRelation->shouldReceive('find')->with(2)->andReturn((object) ['name' => 'Высокий']);
        $project->shouldReceive('priorities')->andReturn($priorityRelation);

        $bug = new Bug;
        $bug->id = 10;
        $bug->project_id = 1;
        $bug->title = 'Старый заголовок';
        $bug->description = 'Не менялось';
        $bug->priority_id = 1;

        $updatedBug = new Bug;
        $updatedBug->id = 10;
        $updatedBug->project_id = 1;
        $updatedBug->title = 'Новый заголовок';
        $updatedBug->priority_id = 2;

        $input = [
            'title' => 'Новый заголовок',
            'description' => 'Не менялось',
            'priority_id' => 2,
            'unknown_field' => 'Игнорируется',
        ];

        $this->bugRepo->shouldReceive('update')
            ->once()
            ->with($bug, [
                'title' => 'Новый заголовок',
                'description' => 'Не менялось',
                'priority_id' => 2,
            ])
            ->andReturn($updatedBug);

        $this->historyRepo->shouldReceive('add')
            ->once()
            ->with($updatedBug, $this->user, 'updated', 'title', 'Старый заголовок', 'Новый заголовок');

        $this->historyRepo->shouldReceive('add')
            ->once()
            ->with($updatedBug, $this->user, 'updated', 'priority_id', 'Низкий', 'Высокий');

        $result = $this->service->update($project, $bug, $this->user, $input);

        $this->assertSame($updatedBug, $result);
    }

    public function test_update_does_nothing_when_no_fields_changed(): void
    {
        $bug = new Bug;
        $bug->id = 10;
        $bug->project_id = 1;
        $bug->title = 'Тот же заголовок';

        $this->bugRepo->shouldNotReceive('update');
        $this->historyRepo->shouldNotReceive('add');

        $result = $this->service->update($this->project, $bug, $this->user, ['title' => 'Тот же заголовок']);

        $this->assertSame($bug, $result);
    }

    public function test_change_status_sets_closed_at_when_moving_to_closed_status(): void
    {
        $now = Carbon::parse('2026-11-23 12:00:00');
        Carbon::setTestNow($now);

        $oldStatus = new Status;
        $oldStatus->id = 1;
        $oldStatus->name = 'В работе';
        $oldStatus->is_closed = false;

        $closedStatus = new Status;
        $closedStatus->id = 2;
        $closedStatus->name = 'Закрыт';
        $closedStatus->is_closed = true;

        $project = Mockery::mock(Project::class)->makePartial();
        $project->id = 1;

        $statusRelation = Mockery::mock(HasMany::class);
        $statusRelation->shouldReceive('findOrFail')->with(2)->once()->andReturn($closedStatus);
        $project->shouldReceive('statuses')->andReturn($statusRelation);

        $bug = Mockery::mock(Bug::class)->makePartial();
        $bug->project_id = 1;
        $bug->status_id = 1;
        $bug->closed_at = null;
        $bug->setRelation('status', $oldStatus);

        $bug->shouldReceive('save')->once()->andReturn(true);
        $bug->shouldReceive('load')->with('status')->once()->andReturnSelf();

        $this->historyRepo->shouldReceive('add')
            ->once()
            ->with($bug, $this->user, 'status_changed', 'status', 'В работе', 'Закрыт');

        $this->service->changeStatus($project, $bug, $this->user, 2);

        $this->assertEquals(2, $bug->status_id);
        $this->assertTrue($now->equalTo($bug->closed_at));
    }

    public function test_assign_throws_exception_if_user_is_not_project_member(): void
    {
        $project = Mockery::mock(Project::class)->makePartial();
        $project->id = 1;

        $membersRelation = Mockery::mock(BelongsToMany::class);
        $membersRelation->shouldReceive('where')->with('users.id', 99)->andReturnSelf();
        $membersRelation->shouldReceive('first')->andReturn(null);
        $project->shouldReceive('members')->andReturn($membersRelation);

        $bug = new Bug;
        $bug->id = 10;
        $bug->project_id = 1;

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Исполнитель должен быть участником проекта.');

        $this->service->assign($project, $bug, $this->user, 99);
    }

    public function test_assign_updates_assignee_and_logs_history(): void
    {
        $project = Mockery::mock(Project::class)->makePartial();
        $project->id = 1;

        $newAssignee = new User;
        $newAssignee->id = 7;
        $newAssignee->name = 'Дмитрий';

        $oldAssignee = new User;
        $oldAssignee->id = 4;
        $oldAssignee->name = 'Олег';

        $membersRelation = Mockery::mock(BelongsToMany::class);
        $membersRelation->shouldReceive('where')->with('users.id', 7)->andReturnSelf();
        $membersRelation->shouldReceive('first')->andReturn($newAssignee);
        $project->shouldReceive('members')->andReturn($membersRelation);

        $bug = Mockery::mock(Bug::class)->makePartial();
        $bug->project_id = 1;
        $bug->assignee_id = 4;
        $bug->setRelation('assignee', $oldAssignee);

        $bug->shouldReceive('save')->once()->andReturn(true);
        $bug->shouldReceive('load')->with('assignee')->once()->andReturnSelf();

        $this->historyRepo->shouldReceive('add')
            ->once()
            ->with($bug, $this->user, 'assignee_assigned', 'assignee', 'Олег', 'Дмитрий');

        $this->service->assign($project, $bug, $this->user, 7);

        $this->assertEquals(7, $bug->assignee_id);
    }
}
