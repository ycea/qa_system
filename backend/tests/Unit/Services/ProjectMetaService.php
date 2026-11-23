<?php

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\DictionaryRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Services\ProjectMetaService;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\TestCase;

class ProjectMetaServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_meta_returns_formatted_members_and_dictionaries(): void
    {
        $project = new Project(['id' => 1]);

        $user1 = new User;
        $user1->id = 10;
        $user1->name = 'Алексей';
        $user1->email = 'alex@example.com';
        $user1->setRelation('pivot', new Pivot(['role' => 'admin']));

        $user2 = new User;
        $user2->id = 20;
        $user2->name = 'Мария';
        $user2->email = 'maria@example.com';
        $user2->setRelation('pivot', new Pivot(['role' => 'tester']));

        // Имитируем коллекцию с нестандартными ключами, чтобы проверить работу ->values()
        $members = new Collection([5 => $user1, 9 => $user2]);

        $statuses = new Collection([['id' => 1, 'name' => 'Новый']]);
        $priorities = new Collection([['id' => 2, 'name' => 'Высокий']]);
        $severities = new Collection([['id' => 3, 'name' => 'Критическая']]);

        $projectRepo = Mockery::mock(ProjectRepositoryInterface::class);
        $projectRepo->shouldReceive('members')
            ->once()
            ->with($project)
            ->andReturn($members);

        $dictionaryRepo = Mockery::mock(DictionaryRepositoryInterface::class);
        $dictionaryRepo->shouldReceive('listForProject')
            ->once()
            ->with('statuses', $project)
            ->andReturn($statuses);
        $dictionaryRepo->shouldReceive('listForProject')
            ->once()
            ->with('priorities', $project)
            ->andReturn($priorities);
        $dictionaryRepo->shouldReceive('listForProject')
            ->once()
            ->with('severities', $project)
            ->andReturn($severities);

        $service = new ProjectMetaService($projectRepo, $dictionaryRepo);
        $result = $service->meta($project);

        $this->assertEquals([
            [
                'id' => 10,
                'name' => 'Алексей',
                'email' => 'alex@example.com',
                'role' => 'admin',
            ],
            [
                'id' => 20,
                'name' => 'Мария',
                'email' => 'maria@example.com',
                'role' => 'tester',
            ],
        ], $result['members']->all());

        $this->assertSame($statuses, $result['statuses']);
        $this->assertSame($priorities, $result['priorities']);
        $this->assertSame($severities, $result['severities']);
    }
}
