<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| PROJ-03: Добавление участников
|--------------------------------------------------------------------------
*/

test('админ может добавить участника по логину', function () {
    $owner = User::factory()->create();
    $newMember = User::factory()->create(['name' => 'developer1']);

    $project = Project::factory()->create(['owner_id' => $owner->id]);
    $project->members()->attach($owner->id, ['role' => 'admin']);

    $response = fromFrontend($this)->actingAs($owner)->postJson(
        "/api/projects/{$project->id}/members",
        ['name' => 'developer1', 'role' => 'developer']
    );

    $response->assertStatus(201)
        ->assertJsonPath('member.name', 'developer1')
        ->assertJsonPath('member.role', 'developer');

    $this->assertDatabaseHas('project_user', [
        'project_id' => $project->id,
        'user_id' => $newMember->id,
        'role' => 'developer',
    ]);
});

test('нельзя добавить несуществующего пользователя', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $owner->id]);
    $project->members()->attach($owner->id, ['role' => 'admin']);

    $response = fromFrontend($this)->actingAs($owner)->postJson(
        "/api/projects/{$project->id}/members",
        ['name' => 'ghost', 'role' => 'developer']
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name'])
        ->assertJsonPath('errors.name.0', 'Пользователь не найден.');
});

test('нельзя добавить участника повторно', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create(['name' => 'tester1']);

    $project = Project::factory()->create(['owner_id' => $owner->id]);
    $project->members()->attach($owner->id, ['role' => 'admin']);
    $project->members()->attach($member->id, ['role' => 'tester']);

    $response = fromFrontend($this)->actingAs($owner)->postJson(
        "/api/projects/{$project->id}/members",
        ['name' => 'tester1', 'role' => 'developer']
    );

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Пользователь уже является участником проекта.');
});

test('разработчик не может добавить участника', function () {
    $owner = User::factory()->create();
    $developer = User::factory()->create(['name' => 'dev']);
    $candidate = User::factory()->create(['name' => 'candidate']);

    $project = Project::factory()->create(['owner_id' => $owner->id]);
    $project->members()->attach($owner->id, ['role' => 'admin']);
    $project->members()->attach($developer->id, ['role' => 'developer']);

    $response = fromFrontend($this)->actingAs($developer)->postJson(
        "/api/projects/{$project->id}/members",
        ['name' => 'candidate', 'role' => 'tester']
    );

    $response->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| PROJ-04: Просмотр состава команды
|--------------------------------------------------------------------------
*/

test('участник может просмотреть список участников', function () {
    $owner = User::factory()->create(['name' => 'owner']);
    $dev = User::factory()->create(['name' => 'dev']);

    $project = Project::factory()->create(['owner_id' => $owner->id]);
    $project->members()->attach($owner->id, ['role' => 'admin']);
    $project->members()->attach($dev->id, ['role' => 'developer']);

    $response = fromFrontend($this)->actingAs($dev)
        ->getJson("/api/projects/{$project->id}/members");

    $response->assertOk()
        ->assertJsonCount(2, 'members')
        ->assertJsonPath('can_manage', false)
        ->assertJsonFragment(['name' => 'owner', 'role' => 'admin'])
        ->assertJsonFragment(['name' => 'dev', 'role' => 'developer']);
});

test('админ видит флаг can_manage = true', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $owner->id]);
    $project->members()->attach($owner->id, ['role' => 'admin']);

    $response = fromFrontend($this)->actingAs($owner)
        ->getJson("/api/projects/{$project->id}/members");

    $response->assertOk()->assertJsonPath('can_manage', true);
});

test('посторонний не может просмотреть участников', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $owner->id]);
    $project->members()->attach($owner->id, ['role' => 'admin']);

    $response = fromFrontend($this)->actingAs($stranger)
        ->getJson("/api/projects/{$project->id}/members");

    $response->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| PROJ-05: Удаление участника
|--------------------------------------------------------------------------
*/

test('админ может удалить участника', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $project = Project::factory()->create(['owner_id' => $owner->id]);
    $project->members()->attach($owner->id, ['role' => 'admin']);
    $project->members()->attach($member->id, ['role' => 'developer']);

    $response = fromFrontend($this)->actingAs($owner)
        ->deleteJson("/api/projects/{$project->id}/members/{$member->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('project_user', [
        'project_id' => $project->id,
        'user_id' => $member->id,
    ]);
});

test('нельзя удалить владельца проекта', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $owner->id]);
    $project->members()->attach($owner->id, ['role' => 'admin']);

    $response = fromFrontend($this)->actingAs($owner)
        ->deleteJson("/api/projects/{$project->id}/members/{$owner->id}");

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Нельзя удалить владельца проекта.');
});

test('разработчик не может удалить участника', function () {
    $owner = User::factory()->create();
    $dev = User::factory()->create();
    $tester = User::factory()->create();

    $project = Project::factory()->create(['owner_id' => $owner->id]);
    $project->members()->attach($owner->id, ['role' => 'admin']);
    $project->members()->attach($dev->id, ['role' => 'developer']);
    $project->members()->attach($tester->id, ['role' => 'tester']);

    $response = fromFrontend($this)->actingAs($dev)
        ->deleteJson("/api/projects/{$project->id}/members/{$tester->id}");

    $response->assertStatus(403);
});

test('удалённый участник больше не видит проект в списке', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $project = Project::factory()->create(['owner_id' => $owner->id]);
    $project->members()->attach($owner->id, ['role' => 'admin']);
    $project->members()->attach($member->id, ['role' => 'developer']);

    // До удаления — видит
    fromFrontend($this)->actingAs($member)->getJson('/api/projects')
        ->assertOk()
        ->assertJsonFragment(['id' => $project->id]);

    // Удаляем
    fromFrontend($this)->actingAs($owner)
        ->deleteJson("/api/projects/{$project->id}/members/{$member->id}")
        ->assertOk();

    // После удаления — не видит
    fromFrontend($this)->actingAs($member)->getJson('/api/projects')
        ->assertOk()
        ->assertJsonMissing(['id' => $project->id]);
});
