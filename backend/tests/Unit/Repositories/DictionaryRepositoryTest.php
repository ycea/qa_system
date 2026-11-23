<?php

namespace Tests\Unit\Repositories;

use App\Exceptions\DomainException;
use App\Models\Bug;
use App\Models\Priority;
use App\Models\Project;
use App\Models\Severity;
use App\Models\Status;
use App\Models\User;
use App\Repositories\Eloquent\DictionaryRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DictionaryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private DictionaryRepository $repository;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new DictionaryRepository;
        $this->project = Project::factory()->create();
    }

    public static function dictionaryTypesProvider(): array
    {
        return [
            'statuses' => ['statuses', Status::class],
            'priorities' => ['priorities', Priority::class],
            'severities' => ['severities', Severity::class],
        ];
    }

    #[DataProvider('dictionaryTypesProvider')]
    public function test_list_for_project_returns_sorted_items_with_in_use_flag(string $type, string $modelClass): void
    {
        $initialIds = $modelClass::where('project_id', $this->project->id)->orderBy('id')->pluck('id')->all();
        $otherProject = Project::factory()->create();
        $modelClass::factory()->create(['project_id' => $otherProject->id]);

        $unusedItem = $modelClass::factory()->create(['project_id' => $this->project->id, 'name' => 'Custom First']);
        $usedItem = $modelClass::factory()->create(['project_id' => $this->project->id, 'name' => 'Custom Second']);

        $statusId = $type === 'statuses'
            ? $usedItem->id
            : Status::factory()->create(['project_id' => $this->project->id])->id;

        $priorityId = $type === 'priorities'
            ? $usedItem->id
            : Priority::factory()->create(['project_id' => $this->project->id])->id;

        $severityId = $type === 'severities'
            ? $usedItem->id
            : Severity::factory()->create(['project_id' => $this->project->id])->id;

        $user = User::factory()->create();

        Bug::factory()->create([
            'project_id' => $this->project->id,
            'status_id' => $statusId,
            'priority_id' => $priorityId,
            'severity_id' => $severityId,
            'created_by' => $user->id,
        ]);

        $result = $this->repository->listForProject($type, $this->project);

        $expectedIds = [...$initialIds, $unusedItem->id, $usedItem->id];
        $this->assertEquals($expectedIds, $result->pluck('id')->all());

        $foundUnused = $result->firstWhere('id', $unusedItem->id);
        $foundUsed = $result->firstWhere('id', $usedItem->id);

        $this->assertFalse($foundUnused->in_use);
        $this->assertTrue($foundUsed->in_use);
    }

    #[DataProvider('dictionaryTypesProvider')]
    public function test_find_in_project_returns_item_or_throws_not_found(string $type, string $modelClass): void
    {
        $otherProject = Project::factory()->create();
        $item = $modelClass::factory()->create(['project_id' => $this->project->id]);
        $otherProjectItem = $modelClass::factory()->create(['project_id' => $otherProject->id]);

        $found = $this->repository->findInProject($type, $this->project, $item->id);
        $this->assertTrue($item->is($found));

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findInProject($type, $this->project, $otherProjectItem->id);
    }

    public function test_create_item_sets_is_default_false_only_for_statuses(): void
    {
        $status = $this->repository->createItem('statuses', $this->project, ['name' => 'Кастомный статус']);
        $this->assertInstanceOf(Status::class, $status);
        $this->assertEquals('Кастомный статус', $status->name);
        $this->assertFalse($status->is_default);

        $priority = $this->repository->createItem('priorities', $this->project, ['name' => 'Кастомный приоритет']);
        $this->assertInstanceOf(Priority::class, $priority);
        $this->assertEquals('Кастомный приоритет', $priority->name);
        $this->assertArrayNotHasKey('is_default', $priority->getAttributes());
    }

    #[DataProvider('dictionaryTypesProvider')]
    public function test_update_and_delete_item(string $type, string $modelClass): void
    {
        $item = $modelClass::factory()->create(['project_id' => $this->project->id, 'name' => 'Old Name']);

        $updated = $this->repository->updateItem($item, ['name' => 'New Name']);
        $this->assertEquals('New Name', $updated->name);
        $this->assertDatabaseHas($item->getTable(), ['id' => $item->id, 'name' => 'New Name']);

        $this->repository->deleteItem($item);
        $this->assertDatabaseMissing($item->getTable(), ['id' => $item->id]);
    }

    #[DataProvider('dictionaryTypesProvider')]
    public function test_name_exists_checks_uniqueness_within_project_and_ignores_self(string $type, string $modelClass): void
    {
        $item = $modelClass::factory()->create(['project_id' => $this->project->id, 'name' => 'Duplicate']);
        $otherProject = Project::factory()->create();
        $modelClass::factory()->create(['project_id' => $otherProject->id, 'name' => 'OtherProjectName']);

        $this->assertTrue($this->repository->nameExists($type, $this->project, 'Duplicate'));
        $this->assertFalse($this->repository->nameExists($type, $this->project, 'Duplicate', $item->id));
        $this->assertFalse($this->repository->nameExists($type, $this->project, 'OtherProjectName'));
    }

    #[DataProvider('dictionaryTypesProvider')]
    public function test_count_for_project_counts_only_project_items(string $type, string $modelClass): void
    {
        $initialCount = $modelClass::where('project_id', $this->project->id)->count();
        $otherProject = Project::factory()->create();

        $modelClass::factory()->count(3)->create(['project_id' => $this->project->id]);
        $modelClass::factory()->count(2)->create(['project_id' => $otherProject->id]);

        $this->assertEquals($initialCount + 3, $this->repository->countForProject($type, $this->project));
    }

    public function test_throws_domain_exception_on_invalid_type(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Неизвестный тип справочника.');

        $this->repository->countForProject('invalid_type', $this->project);
    }
}
