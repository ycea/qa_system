<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| BUG-01: создание бага
|--------------------------------------------------------------------------
*/

test('создание бага сохраняет его и пишет запись в историю', function () {
    [$owner, $project] = bugProjectWithAdmin();

    $response = fromFrontend($this)->actingAs($owner)->postJson(
        "/api/projects/{$project->id}/bugs",
        bugPayload($project, ['title' => 'Краш при входе'])
    );

    $response->assertStatus(201)->assertJsonPath('bug.title', 'Краш при входе');

    $this->assertDatabaseHas('bugs', [
        'project_id' => $project->id,
        'title' => 'Краш при входе',
        'created_by' => $owner->id,
    ]);
    $this->assertDatabaseHas('bug_histories', [
        'action' => 'created',
        'user_id' => $owner->id,
    ]);
});

test('создание бага без обязательных полей отклоняется', function () {
    [$owner, $project] = bugProjectWithAdmin();

    fromFrontend($this)->actingAs($owner)
        ->postJson("/api/projects/{$project->id}/bugs", ['title' => ''])
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'title', 'description', 'steps_to_reproduce', 'priority_id', 'severity_id', 'status_id',
        ]);
});

test('посторонний не может создать баг', function () {
    [, $project] = bugProjectWithAdmin();
    $stranger = User::factory()->create();

    fromFrontend($this)->actingAs($stranger)
        ->postJson("/api/projects/{$project->id}/bugs", bugPayload($project))
        ->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| BUG-08: список, поиск, фильтры
|--------------------------------------------------------------------------
*/

test('список багов проекта с фильтром по статусу и поиском', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $statusNew = $project->statuses()->where('is_default', true)->first();
    $statusWork = $project->statuses()->where('name', 'В работе')->first();

    makeBug($project, $owner, ['title' => 'Login broken', 'status_id' => $statusNew->id]);
    makeBug($project, $owner, ['title' => 'Payment issue', 'status_id' => $statusWork->id]);

    fromFrontend($this)->actingAs($owner)
        ->getJson("/api/projects/{$project->id}/bugs")
        ->assertOk()->assertJsonCount(2);

    fromFrontend($this)->actingAs($owner)
        ->getJson("/api/projects/{$project->id}/bugs?search=Login")
        ->assertOk()->assertJsonCount(1)->assertJsonFragment(['title' => 'Login broken']);

    fromFrontend($this)->actingAs($owner)
        ->getJson("/api/projects/{$project->id}/bugs?status_id={$statusWork->id}")
        ->assertOk()->assertJsonCount(1)->assertJsonFragment(['title' => 'Payment issue']);
});

/*
|--------------------------------------------------------------------------
| BUG-02: детали + BUG-03: редактирование
|--------------------------------------------------------------------------
*/

test('детали бага содержат связи и историю', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $bug = makeBug($project, $owner);

    fromFrontend($this)->actingAs($owner)->postJson(
        "/api/projects/{$project->id}/bugs/{$bug->id}/comments",
        ['body' => 'Первый комментарий']
    )->assertStatus(201);

    fromFrontend($this)->actingAs($owner)
        ->getJson("/api/projects/{$project->id}/bugs/{$bug->id}")
        ->assertOk()
        ->assertJsonPath('id', $bug->id)
        ->assertJsonStructure(['id', 'title', 'status', 'priority', 'severity', 'comments', 'histories']);
});

test('редактирование бага пишет запись в историю', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $bug = makeBug($project, $owner, ['title' => 'Старый заголовок']);

    fromFrontend($this)->actingAs($owner)->putJson(
        "/api/projects/{$project->id}/bugs/{$bug->id}",
        bugPayload($project, ['title' => 'Новый заголовок'])
    )->assertOk()->assertJsonPath('bug.title', 'Новый заголовок');

    $this->assertDatabaseHas('bug_histories', [
        'bug_id' => $bug->id,
        'action' => 'updated',
        'field' => 'title',
        'new_value' => 'Новый заголовок',
    ]);
});
