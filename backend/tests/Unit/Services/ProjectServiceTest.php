<?php

use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Services\ProjectService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Mockery as m;
use Mockery\MockInterface;

afterEach(fn () => m::close());

test('create создаёт проект и назначает создателя администратором', function () {
    $user = new User(['name' => 'owner']);
    $user->id = 10;

    $project = new Project(['name' => 'Проект']);
    $project->id = 1;

    /** @var ProjectRepositoryInterface&MockInterface $repo */
    $repo = m::mock(ProjectRepositoryInterface::class);
    $repo->shouldReceive('create')
        ->once()
        ->with(['name' => 'Проект', 'description' => null, 'owner_id' => 10])
        ->andReturn($project);
    $repo->shouldReceive('attachMember')->once()->with($project, $user, 'admin');

    $service = new ProjectService($repo);

    expect($service->create($user, ['name' => 'Проект']))->toBe($project);
});

test('create передаёт описание, если оно задано', function () {
    $user = new User;
    $user->id = 3;

    $project = new Project(['name' => 'P']);

    /** @var ProjectRepositoryInterface&MockInterface $repo */
    $repo = m::mock(ProjectRepositoryInterface::class);
    $repo->shouldReceive('create')
        ->once()
        ->with(['name' => 'P', 'description' => 'desc', 'owner_id' => 3])
        ->andReturn($project);
    $repo->shouldReceive('attachMember')->once()->with($project, $user, 'admin');

    (new ProjectService($repo))->create($user, ['name' => 'P', 'description' => 'desc']);
});

test('listForUser делегирует в репозиторий с пагинацией', function () {
    $user = new User;
    $paginator = m::mock(LengthAwarePaginator::class);

    /** @var ProjectRepositoryInterface&MockInterface $repo */
    $repo = m::mock(ProjectRepositoryInterface::class);
    $repo->shouldReceive('paginateForUser')->once()->with($user, 30)->andReturn($paginator);

    expect((new ProjectService($repo))->listForUser($user, 30))->toBe($paginator);
});

test('getForUser загружает участников и количество багов', function () {
    $project = new Project(['name' => 'P']);

    /** @var ProjectRepositoryInterface&MockInterface $repo */
    $repo = m::mock(ProjectRepositoryInterface::class);
    $repo->shouldReceive('loadWithMembersAndBugCount')->once()->with($project)->andReturn($project);

    expect((new ProjectService($repo))->getForUser($project))->toBe($project);
});
