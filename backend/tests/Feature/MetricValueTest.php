<?php

use App\Models\MetricValue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('участник заполняет числовое значение метрики с фиксацией времени', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $metric = $project->metrics()->create(['name' => 'Время', 'type' => 'numeric']);
    $bug = makeBug($project, $owner);

    fromFrontend($this)->actingAs($owner)->putJson(
        "/api/projects/{$project->id}/bugs/{$bug->id}/metrics/{$metric->id}",
        ['numeric_value' => 12.5]
    )->assertOk();

    $this->assertDatabaseHas('metric_values', [
        'metric_id' => $metric->id,
        'bug_id' => $bug->id,
    ]);
    expect(MetricValue::first()->recorded_at)->not->toBeNull();
});

test('участник заполняет категориальное значение метрики', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $metric = $project->metrics()->create([
        'name' => 'Компонент',
        'type' => 'categorical',
        'options' => ['UI', 'API'],
    ]);
    $bug = makeBug($project, $owner);

    fromFrontend($this)->actingAs($owner)->putJson(
        "/api/projects/{$project->id}/bugs/{$bug->id}/metrics/{$metric->id}",
        ['categorical_value' => 'UI']
    )->assertOk();

    $this->assertDatabaseHas('metric_values', [
        'metric_id' => $metric->id,
        'categorical_value' => 'UI',
    ]);
});

test('недопустимое категориальное значение отклоняется', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $metric = $project->metrics()->create([
        'name' => 'Компонент',
        'type' => 'categorical',
        'options' => ['UI'],
    ]);
    $bug = makeBug($project, $owner);

    fromFrontend($this)->actingAs($owner)->putJson(
        "/api/projects/{$project->id}/bugs/{$bug->id}/metrics/{$metric->id}",
        ['categorical_value' => 'Unknown']
    )->assertStatus(422)->assertJsonValidationErrors(['categorical_value']);
});

test('аналитика возвращает показатели метрик по статусам', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $metric = $project->metrics()->create(['name' => 'Время', 'type' => 'numeric']);
    $bug1 = makeBug($project, $owner);
    $bug2 = makeBug($project, $owner);

    foreach ([[$bug1, 10], [$bug2, 20]] as [$bug, $value]) {
        fromFrontend($this)->actingAs($owner)->putJson(
            "/api/projects/{$project->id}/bugs/{$bug->id}/metrics/{$metric->id}",
            ['numeric_value' => $value]
        )->assertOk();
    }

    $response = fromFrontend($this)->actingAs($owner)
        ->getJson("/api/projects/{$project->id}/metrics/analytics");

    $response->assertOk()
        ->assertJsonPath('items.0.metric.name', 'Время')
        ->assertJsonPath('items.0.count', 2)
        ->assertJsonPath('items.0.by_status.0.status', 'Новый');

    expect((float) $response->json('items.0.indicators.avg'))->toBe(15.0);
});

test('экспорт метрик отдаёт xlsx-файл', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $project->metrics()->create(['name' => 'Время', 'type' => 'numeric']);

    fromFrontend($this)->actingAs($owner)
        ->get("/api/projects/{$project->id}/metrics/export")
        ->assertOk()
        ->assertDownload("metrics-project-{$project->id}.xlsx");
});

test('посторонний не видит аналитику метрик', function () {
    [, $project] = bugProjectWithAdmin();
    $stranger = User::factory()->create();

    fromFrontend($this)->actingAs($stranger)
        ->getJson("/api/projects/{$project->id}/metrics/analytics")
        ->assertStatus(403);
});
