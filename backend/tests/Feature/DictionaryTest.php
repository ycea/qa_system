<?php

use App\Models\Bug;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Хелпер: проект с владельцем-админом.
 */
function makeProjectWithAdmin(): array
{
    $owner = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $owner->id]);
    $project->members()->attach($owner->id, ['role' => 'admin']);

    return [$owner, $project];
}

/*
|--------------------------------------------------------------------------
| Справочники: просмотр (index)
|--------------------------------------------------------------------------
*/

test('админ видит список статусов проекта', function () {
    [$owner, $project] = makeProjectWithAdmin();

    $response = fromFrontend($this)->actingAs($owner)
        ->getJson("/api/projects/{$project->id}/dictionaries/statuses");

    $response->assertOk()
        ->assertJsonCount(6, 'items')
        ->assertJsonFragment(['name' => 'Новая', 'is_default' => true])
        ->assertJsonFragment(['name' => 'Закрыта']);
});

test('админ видит список приоритетов проекта', function () {
    [$owner, $project] = makeProjectWithAdmin();

    $response = fromFrontend($this)->actingAs($owner)
        ->getJson("/api/projects/{$project->id}/dictionaries/priorities");

    $response->assertOk()
        ->assertJsonCount(4, 'items');
});

test('разработчик не видит справочники', function () {
    $owner = User::factory()->create();
    $dev = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $owner->id]);
    $project->members()->attach($owner->id, ['role' => 'admin']);
    $project->members()->attach($dev->id, ['role' => 'developer']);

    $response = fromFrontend($this)->actingAs($dev)
        ->getJson("/api/projects/{$project->id}/dictionaries/statuses");

    $response->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| Создание (store)
|--------------------------------------------------------------------------
*/

test('админ может создать новый статус', function () {
    [$owner, $project] = makeProjectWithAdmin();

    $response = fromFrontend($this)->actingAs($owner)
        ->postJson("/api/projects/{$project->id}/dictionaries/statuses", [
            'name' => 'На проверке',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('item.name', 'На проверке')
        ->assertJsonPath('item.is_default', false);

    $this->assertDatabaseHas('statuses', [
        'project_id' => $project->id,
        'name' => 'На проверке',
    ]);
});

test('нельзя создать статус с дублирующимся названием', function () {
    [$owner, $project] = makeProjectWithAdmin();

    $response = fromFrontend($this)->actingAs($owner)
        ->postJson("/api/projects/{$project->id}/dictionaries/statuses", [
            'name' => 'Новая', // уже есть
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('нельзя создать элемент без названия', function () {
    [$owner, $project] = makeProjectWithAdmin();

    $response = fromFrontend($this)->actingAs($owner)
        ->postJson("/api/projects/{$project->id}/dictionaries/statuses", []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

/*
|--------------------------------------------------------------------------
| Редактирование (update)
|--------------------------------------------------------------------------
*/

test('админ может переименовать статус', function () {
    [$owner, $project] = makeProjectWithAdmin();
    $status = $project->statuses()->where('name', 'В работе')->first();

    $response = fromFrontend($this)->actingAs($owner)
        ->putJson("/api/projects/{$project->id}/dictionaries/statuses/{$status->id}", [
            'name' => 'В процессе',
        ]);

    $response->assertOk()
        ->assertJsonPath('item.name', 'В процессе');

    $this->assertDatabaseHas('statuses', ['id' => $status->id, 'name' => 'В процессе']);
});

test('нельзя переименовать статус в существующее название', function () {
    [$owner, $project] = makeProjectWithAdmin();
    $status = $project->statuses()->where('name', 'В работе')->first();

    $response = fromFrontend($this)->actingAs($owner)
        ->putJson("/api/projects/{$project->id}/dictionaries/statuses/{$status->id}", [
            'name' => 'Новая',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

/*
|--------------------------------------------------------------------------
| Удаление (destroy)
|--------------------------------------------------------------------------
*/

test('админ может удалить неиспользуемый статус', function () {
    [$owner, $project] = makeProjectWithAdmin();
    $status = $project->statuses()->create(['name' => 'Пустой статус', 'is_default' => false]);

    $response = fromFrontend($this)->actingAs($owner)
        ->deleteJson("/api/projects/{$project->id}/dictionaries/statuses/{$status->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('statuses', ['id' => $status->id]);
});

test('нельзя удалить используемый статус', function () {
    [$owner, $project] = makeProjectWithAdmin();

    // Создаём баг — статус «Новая» уже используется
    Bug::create([
        'project_id' => $project->id,
        'status_id' => $project->statuses()->where('name', 'Новая')->first()->id,
        'priority_id' => $project->priorities()->first()->id,
        'severity_id' => $project->severities()->first()->id,
        'created_by' => $owner->id,
        'title' => 'Тестовый баг',
    ]);

    $status = $project->statuses()->where('name', 'Новая')->first();

    $response = fromFrontend($this)->actingAs($owner)
        ->deleteJson("/api/projects/{$project->id}/dictionaries/statuses/{$status->id}");

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Элемент используется и не может быть удалён.');

    $this->assertDatabaseHas('statuses', ['id' => $status->id]);
});

test('нельзя удалить дефолтный статус', function () {
    [$owner, $project] = makeProjectWithAdmin();
    $default = $project->statuses()->where('is_default', true)->first();

    $response = fromFrontend($this)->actingAs($owner)
        ->deleteJson("/api/projects/{$project->id}/dictionaries/statuses/{$default->id}");

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Нельзя удалить дефолтный статус.');
});

/*
|--------------------------------------------------------------------------
| Разграничение доступа
|--------------------------------------------------------------------------
*/

test('разработчик не может создать статус', function () {
    $owner = User::factory()->create();
    $dev = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $owner->id]);
    $project->members()->attach($owner->id, ['role' => 'admin']);
    $project->members()->attach($dev->id, ['role' => 'developer']);

    $response = fromFrontend($this)->actingAs($dev)
        ->postJson("/api/projects/{$project->id}/dictionaries/statuses", ['name' => 'Хак']);

    $response->assertStatus(403);
});

test('посторонний не может управлять справочниками', function () {
    [$owner, $project] = makeProjectWithAdmin();
    $stranger = User::factory()->create();

    $response = fromFrontend($this)->actingAs($stranger)
        ->getJson("/api/projects/{$project->id}/dictionaries/statuses");

    $response->assertStatus(403);
});
