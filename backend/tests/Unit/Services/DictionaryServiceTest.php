<?php

use App\Exceptions\DomainException;
use App\Models\Project;
use App\Models\Status;
use App\Repositories\Contracts\DictionaryRepositoryInterface;
use App\Services\DictionaryService;
use Illuminate\Validation\ValidationException;
use Mockery as m;
use Mockery\MockInterface;

afterEach(fn () => m::close());

test('create создаёт элемент при уникальном названии', function () {
    $project = new Project;
    $project->id = 1;
    $status = new Status(['name' => 'На проверке']);

    /** @var DictionaryRepositoryInterface&MockInterface $repo */
    $repo = m::mock(DictionaryRepositoryInterface::class);
    $repo->shouldReceive('nameExists')->once()->with('statuses', $project, 'На проверке', null)->andReturnFalse();
    $repo->shouldReceive('createItem')->once()->with('statuses', $project, ['name' => 'На проверке'])->andReturn($status);

    expect((new DictionaryService($repo))->create($project, 'statuses', 'На проверке'))->toBe($status);
});

test('create бросает ValidationException при дубликате названия', function () {
    $project = new Project;
    $project->id = 1;

    /** @var DictionaryRepositoryInterface&MockInterface $repo */
    $repo = m::mock(DictionaryRepositoryInterface::class);
    $repo->shouldReceive('nameExists')->once()->andReturnTrue();

    expect(fn () => (new DictionaryService($repo))->create($project, 'statuses', 'Новая'))
        ->toThrow(ValidationException::class);
});

test('update проверяет уникальность с игнорированием текущего элемента', function () {
    $project = new Project;
    $project->id = 1;
    $status = new Status(['name' => 'Старое']);
    $status->id = 8;

    /** @var DictionaryRepositoryInterface&MockInterface $repo */
    $repo = m::mock(DictionaryRepositoryInterface::class);
    $repo->shouldReceive('findInProject')->once()->with('statuses', $project, 8)->andReturn($status);
    $repo->shouldReceive('nameExists')->once()->with('statuses', $project, 'Новое', 8)->andReturnFalse();
    $repo->shouldReceive('updateItem')->once()->with($status, ['name' => 'Новое'])->andReturn($status);

    expect((new DictionaryService($repo))->update($project, 'statuses', 8, 'Новое'))->toBe($status);
});

test('delete запрещает удаление используемого элемента', function () {
    $project = new Project;
    $project->id = 1;
    $status = new Status(['name' => 'Новая']);

    /** @var DictionaryRepositoryInterface&MockInterface $repo */
    $repo = m::mock(DictionaryRepositoryInterface::class);
    $repo->shouldReceive('findInProject')->once()->with('statuses', $project, 1)->andReturn($status);
    $repo->shouldReceive('isInUse')->once()->with($status)->andReturnTrue();

    expect(fn () => (new DictionaryService($repo))->delete($project, 'statuses', 1))
        ->toThrow(DomainException::class, 'Элемент используется и не может быть удалён.');
});

test('delete запрещает удаление дефолтного статуса', function () {
    $project = new Project;
    $project->id = 1;
    $status = new Status(['name' => 'Новая', 'is_default' => true]);

    /** @var DictionaryRepositoryInterface&MockInterface $repo */
    $repo = m::mock(DictionaryRepositoryInterface::class);
    $repo->shouldReceive('findInProject')->once()->andReturn($status);
    $repo->shouldReceive('isInUse')->once()->andReturnFalse();

    expect(fn () => (new DictionaryService($repo))->delete($project, 'statuses', 1))
        ->toThrow(DomainException::class, 'Нельзя удалить дефолтный статус.');
});

test('delete удаляет неиспользуемый не-дефолтный элемент', function () {
    $project = new Project;
    $project->id = 1;
    $status = new Status(['name' => 'Пустой', 'is_default' => false]);

    /** @var DictionaryRepositoryInterface&MockInterface $repo */
    $repo = m::mock(DictionaryRepositoryInterface::class);
    $repo->shouldReceive('findInProject')->once()->andReturn($status);
    $repo->shouldReceive('isInUse')->once()->andReturnFalse();
    $repo->shouldReceive('deleteItem')->once()->with($status);

    (new DictionaryService($repo))->delete($project, 'statuses', 1);

    expect(true)->toBeTrue();
});
