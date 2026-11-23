<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('админ создаёт числовую метрику', function () {
    [$owner, $project] = bugProjectWithAdmin();

    $response = fromFrontend($this)->actingAs($owner)->postJson(
        "/api/projects/{$project->id}/metrics",
        ['name' => 'Время жизни', 'type' => 'numeric']
    );

    $response->assertStatus(201)->assertJsonPath('metric.name', 'Время жизни');
    $this->assertDatabaseHas('metrics', [
        'project_id' => $project->id,
        'name' => 'Время жизни',
        'type' => 'numeric',
    ]);
});

test('админ создаёт категориальную метрику с вариантами', function () {
    [$owner, $project] = bugProjectWithAdmin();

    $response = fromFrontend($this)->actingAs($owner)->postJson(
        "/api/projects/{$project->id}/metrics",
        ['name' => 'Компонент', 'type' => 'categorical', 'options' => ['UI', 'API']]
    );

    $response->assertStatus(201)->assertJsonPath('metric.type', 'categorical');
    $this->assertDatabaseHas('metrics', ['project_id' => $project->id, 'name' => 'Компонент']);
});

test('категориальная метрика без вариантов отклоняется', function () {
    [$owner, $project] = bugProjectWithAdmin();

    fromFrontend($this)->actingAs($owner)->postJson(
        "/api/projects/{$project->id}/metrics",
        ['name' => 'Компонент', 'type' => 'categorical']
    )->assertStatus(422)->assertJsonValidationErrors(['options']);
});

test('разработчик не может создать метрику', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $dev = User::factory()->create();
    $project->members()->attach($dev->id, ['role' => 'developer']);

    fromFrontend($this)->actingAs($dev)->postJson(
        "/api/projects/{$project->id}/metrics",
        ['name' => 'М', 'type' => 'numeric']
    )->assertStatus(403);
});

test('метрика с дублирующимся названием отклоняется', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $project->metrics()->create(['name' => 'М', 'type' => 'numeric']);

    fromFrontend($this)->actingAs($owner)->postJson(
        "/api/projects/{$project->id}/metrics",
        ['name' => 'М', 'type' => 'numeric']
    )->assertStatus(422)->assertJsonValidationErrors(['name']);
});

test('админ меняет тип метрики и удаляет её', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $metric = $project->metrics()->create(['name' => 'М', 'type' => 'numeric']);

    fromFrontend($this)->actingAs($owner)->putJson(
        "/api/projects/{$project->id}/metrics/{$metric->id}",
        ['name' => 'Измерение', 'type' => 'categorical', 'options' => ['a']]
    )->assertOk()
        ->assertJsonPath('metric.name', 'Измерение')
        ->assertJsonPath('metric.type', 'categorical');

    fromFrontend($this)->actingAs($owner)
        ->deleteJson("/api/projects/{$project->id}/metrics/{$metric->id}")
        ->assertOk();

    $this->assertDatabaseMissing('metrics', ['id' => $metric->id]);
});

test('участник видит список метрик проекта', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $dev = User::factory()->create();
    $project->members()->attach($dev->id, ['role' => 'developer']);
    $project->metrics()->create(['name' => 'М', 'type' => 'numeric']);

    fromFrontend($this)->actingAs($dev)
        ->getJson("/api/projects/{$project->id}/metrics")
        ->assertOk()
        ->assertJsonCount(1, 'items');
});
