<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| PROJ-01: Создание проекта
|--------------------------------------------------------------------------
*/

test('авторизованный пользователь может создать проект', function () {
    $user = User::factory()->create();

    $response = fromFrontend($this)->actingAs($user)->postJson('/api/projects', [
        'name' => 'Новый проект',
        'description' => 'Описание нового проекта',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['message', 'project' => ['id', 'name', 'description']])
        ->assertJsonPath('project.name', 'Новый проект');

    $this->assertDatabaseHas('projects', [
        'name' => 'Новый проект',
        'owner_id' => $user->id,
    ]);
});

test('создатель проекта автоматически становится администратором', function () {
    $user = User::factory()->create();

    fromFrontend($this)->actingAs($user)->postJson('/api/projects', [
        'name' => 'Проект с админом',
    ])->assertStatus(201);

    $project = Project::where('name', 'Проект с админом')->first();

    $this->assertDatabaseHas('project_user', [
        'project_id' => $project->id,
        'user_id' => $user->id,
        'role' => 'admin',
    ]);
});

test('создание проекта не проходит без названия', function () {
    $user = User::factory()->create();

    $response = fromFrontend($this)->actingAs($user)->postJson('/api/projects', [
        'description' => 'Без названия',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('неавторизованный пользователь не может создать проект', function () {
    $response = fromFrontend($this)->postJson('/api/projects', [
        'name' => 'Анонимный проект',
    ]);

    $response->assertStatus(401);
});

/*
|--------------------------------------------------------------------------
| PROJ-02: Список проектов
|--------------------------------------------------------------------------
*/

test('пользователь видит проекты, где он владелец или участник', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Проект, где пользователь — владелец
    $ownedProject = Project::factory()->create(['owner_id' => $user->id]);
    $ownedProject->members()->attach($user->id, ['role' => 'admin']);

    // Проект, где пользователь — участник
    $memberProject = Project::factory()->create(['owner_id' => $otherUser->id]);
    $memberProject->members()->attach($user->id, ['role' => 'developer']);

    // Чужой проект
    $foreignProject = Project::factory()->create(['owner_id' => $otherUser->id]);
    $foreignProject->members()->attach($otherUser->id, ['role' => 'admin']);

    $response = fromFrontend($this)->actingAs($user)->getJson('/api/projects');

    $response->assertOk()
        ->assertJsonCount(2)
        ->assertJsonFragment(['name' => $ownedProject->name])
        ->assertJsonFragment(['name' => $memberProject->name])
        ->assertJsonMissing(['name' => $foreignProject->name]);
});

test('список проектов возвращает количество багов', function () {
    $user = User::factory()->create();

    $project = Project::factory()->create(['owner_id' => $user->id]);
    $project->members()->attach($user->id, ['role' => 'admin']);

    $response = fromFrontend($this)->actingAs($user)->getJson('/api/projects');

    $response->assertOk()
        ->assertJsonPath('0.bugs_count', 0);
});

test('неавторизованный пользователь не может получить список проектов', function () {
    $response = fromFrontend($this)->getJson('/api/projects');
    $response->assertStatus(401);
});

/*
|--------------------------------------------------------------------------
| Просмотр одного проекта
|--------------------------------------------------------------------------
*/

test('участник может просмотреть проект', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $project->members()->attach($user->id, ['role' => 'admin']);

    $response = fromFrontend($this)->actingAs($user)->getJson("/api/projects/{$project->id}");

    $response->assertOk()
        ->assertJsonPath('id', $project->id)
        ->assertJsonPath('name', $project->name)
        ->assertJsonStructure(['id', 'name', 'description', 'members', 'bugs_count']);
});

test('посторонний пользователь не может просмотреть проект', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();

    $project = Project::factory()->create(['owner_id' => $owner->id]);
    $project->members()->attach($owner->id, ['role' => 'admin']);

    $response = fromFrontend($this)->actingAs($user)->getJson("/api/projects/{$project->id}");

    $response->assertStatus(403);
});

test('неавторизованный пользователь не может просмотреть проект', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $owner->id]);

    $response = fromFrontend($this)->getJson("/api/projects/{$project->id}");

    $response->assertStatus(401);
});
