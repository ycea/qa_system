<?php

use App\Exceptions\DomainException;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\ProjectMemberService;
use Mockery as m;

afterEach(fn () => m::close());

/**
 * @return array{0: ProjectMemberService, 1: ProjectRepositoryInterface, 2: UserRepositoryInterface}
 */
function memberServiceWithMocks(): array
{
    $projects = m::mock(ProjectRepositoryInterface::class);
    $users = m::mock(UserRepositoryInterface::class);

    return [new ProjectMemberService($projects, $users), $projects, $users];
}

test('add привязывает нового участника', function () {
    [$service, $projects, $users] = memberServiceWithMocks();

    $project = new Project;
    $project->id = 1;
    $user = new User;
    $user->id = 5;

    $users->shouldReceive('findByNameOrFail')->once()->with('dev')->andReturn($user);
    $projects->shouldReceive('hasMember')->once()->with($project, $user)->andReturnFalse();
    $projects->shouldReceive('attachMember')->once()->with($project, $user, 'developer');

    expect($service->add($project, 'dev', 'developer'))->toBe($user);
});

test('add бросает DomainException, если пользователь уже участник', function () {
    [$service, $projects, $users] = memberServiceWithMocks();

    $project = new Project;
    $project->id = 1;
    $user = new User;
    $user->id = 5;

    $users->shouldReceive('findByNameOrFail')->once()->andReturn($user);
    $projects->shouldReceive('hasMember')->once()->andReturnTrue();

    expect(fn () => $service->add($project, 'dev', 'developer'))
        ->toThrow(DomainException::class, 'Пользователь уже является участником проекта.');
});

test('list делегирует в репозиторий', function () {
    [$service, $projects] = memberServiceWithMocks();

    $project = new Project;
    $collection = collect();

    $projects->shouldReceive('members')->once()->with($project)->andReturn($collection);

    expect($service->list($project))->toBe($collection);
});

test('remove запрещает удаление владельца проекта', function () {
    [$service] = memberServiceWithMocks();

    $project = new Project;
    $project->owner_id = 7;
    $owner = new User;
    $owner->id = 7;

    expect(fn () => $service->remove($project, $owner))
        ->toThrow(DomainException::class, 'Нельзя удалить владельца проекта.');
});

test('remove бросает DomainException 404, если пользователь не участник', function () {
    [$service, $projects] = memberServiceWithMocks();

    $project = new Project;
    $project->owner_id = 1;
    $user = new User;
    $user->id = 5;

    $projects->shouldReceive('hasMember')->once()->with($project, $user)->andReturnFalse();

    try {
        $service->remove($project, $user);
        $this->fail('Ожидалось DomainException');
    } catch (DomainException $e) {
        expect($e->statusCode())->toBe(404)
            ->and($e->getMessage())->toBe('Пользователь не является участником проекта.');
    }
});

test('remove отвязывает участника', function () {
    [$service, $projects] = memberServiceWithMocks();

    $project = new Project;
    $project->owner_id = 1;
    $user = new User;
    $user->id = 5;

    $projects->shouldReceive('hasMember')->once()->with($project, $user)->andReturnTrue();
    $projects->shouldReceive('detachMember')->once()->with($project, $user);

    $service->remove($project, $user);

    expect(true)->toBeTrue();
});
