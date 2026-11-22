<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| BUG-04: смена статуса
|--------------------------------------------------------------------------
*/

test('смена статуса на «Закрыт» фиксирует closed_at и историю', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $bug = makeBug($project, $owner);
    $closed = $project->statuses()->where('name', 'Закрыт')->first();

    fromFrontend($this)->actingAs($owner)->patchJson(
        "/api/projects/{$project->id}/bugs/{$bug->id}/status",
        ['status_id' => $closed->id]
    )->assertOk();

    $bug->refresh();
    expect($bug->status_id)->toBe($closed->id)
        ->and($bug->closed_at)->not->toBeNull();

    $this->assertDatabaseHas('bug_histories', [
        'bug_id' => $bug->id,
        'action' => 'status_changed',
        'new_value' => 'Закрыт',
    ]);
});

test('смена статуса на промежуточный сбрасывает closed_at', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $bug = makeBug($project, $owner, ['closed_at' => now()]);
    $work = $project->statuses()->where('name', 'В работе')->first();

    fromFrontend($this)->actingAs($owner)->patchJson(
        "/api/projects/{$project->id}/bugs/{$bug->id}/status",
        ['status_id' => $work->id]
    )->assertOk();

    expect($bug->refresh()->closed_at)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| BUG-05: назначение исполнителя
|--------------------------------------------------------------------------
*/

test('назначение исполнителя из участников проекта пишет историю', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $dev = User::factory()->create();
    $project->members()->attach($dev->id, ['role' => 'developer']);
    $bug = makeBug($project, $owner);

    fromFrontend($this)->actingAs($owner)->patchJson(
        "/api/projects/{$project->id}/bugs/{$bug->id}/assignee",
        ['assignee_id' => $dev->id]
    )->assertOk()->assertJsonPath('bug.assignee.id', $dev->id);

    $this->assertDatabaseHas('bug_histories', [
        'bug_id' => $bug->id,
        'action' => 'assignee_assigned',
        'new_value' => $dev->name,
    ]);
});

test('нельзя назначить исполнителя, не являющегося участником', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $stranger = User::factory()->create();
    $bug = makeBug($project, $owner);

    fromFrontend($this)->actingAs($owner)->patchJson(
        "/api/projects/{$project->id}/bugs/{$bug->id}/assignee",
        ['assignee_id' => $stranger->id]
    )->assertStatus(422);
});

/*
|--------------------------------------------------------------------------
| BUG-07: комментарии
|--------------------------------------------------------------------------
*/

test('комментарии сохраняются и возвращаются в хронологическом порядке', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $bug = makeBug($project, $owner);

    foreach (['Первый', 'Второй'] as $body) {
        fromFrontend($this)->actingAs($owner)->postJson(
            "/api/projects/{$project->id}/bugs/{$bug->id}/comments",
            ['body' => $body]
        )->assertStatus(201);
    }

    $response = fromFrontend($this)->actingAs($owner)
        ->getJson("/api/projects/{$project->id}/bugs/{$bug->id}/comments");

    $response->assertOk()->assertJsonCount(2, 'items');
    expect($response->json('items.0.body'))->toBe('Первый')
        ->and($response->json('items.0.user.name'))->toBe($owner->name);
});

test('пустой комментарий отклоняется', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $bug = makeBug($project, $owner);

    fromFrontend($this)->actingAs($owner)->postJson(
        "/api/projects/{$project->id}/bugs/{$bug->id}/comments",
        ['body' => '   ']
    )->assertStatus(422)->assertJsonValidationErrors(['body']);
});

/*
|--------------------------------------------------------------------------
| Разграничение доступа и скоуп проекта
|--------------------------------------------------------------------------
*/

test('посторонний не видит баги проекта', function () {
    [, $project] = bugProjectWithAdmin();
    $stranger = User::factory()->create();

    fromFrontend($this)->actingAs($stranger)
        ->getJson("/api/projects/{$project->id}/bugs")
        ->assertStatus(403);
});

test('баг из другого проекта недоступен через чужой проект', function () {
    [$owner, $projectA] = bugProjectWithAdmin();
    $other = User::factory()->create();
    $projectB = Project::factory()->create(['owner_id' => $other->id]);
    $projectB->members()->attach($other->id, ['role' => 'admin']);
    // owner состоит и в проекте B, чтобы Policy пропустила его
    $projectB->members()->attach($owner->id, ['role' => 'developer']);

    $bugB = makeBug($projectB, $other);

    fromFrontend($this)->actingAs($owner)
        ->getJson("/api/projects/{$projectA->id}/bugs/{$bugB->id}")
        ->assertStatus(404);
});
