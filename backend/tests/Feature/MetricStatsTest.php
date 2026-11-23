<?php

use App\Models\MetricValue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('числовые индикаторы считают min/max/sum/avg', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $metric = $project->metrics()->create(['name' => 'Время', 'type' => 'numeric']);

    foreach ([10, 20, 30] as $value) {
        $bug = makeBug($project, $owner);
        fromFrontend($this)->actingAs($owner)->putJson(
            "/api/projects/{$project->id}/bugs/{$bug->id}/metrics/{$metric->id}",
            ['numeric_value' => $value]
        )->assertOk();
    }

    $response = fromFrontend($this)->actingAs($owner)
        ->getJson("/api/projects/{$project->id}/metrics/analytics");

    $response->assertOk()->assertJsonPath('items.0.count', 3);

    expect((float) $response->json('items.0.indicators.min'))->toBe(10.0)
        ->and((float) $response->json('items.0.indicators.max'))->toBe(30.0)
        ->and((float) $response->json('items.0.indicators.sum'))->toBe(60.0)
        ->and((float) $response->json('items.0.indicators.avg'))->toBe(20.0);
});

test('категориальная метрика строит распределение по значениям', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $metric = $project->metrics()->create([
        'name' => 'Компонент',
        'type' => 'categorical',
        'options' => ['UI', 'API'],
    ]);

    foreach (['UI', 'UI', 'API'] as $value) {
        $bug = makeBug($project, $owner);
        fromFrontend($this)->actingAs($owner)->putJson(
            "/api/projects/{$project->id}/bugs/{$bug->id}/metrics/{$metric->id}",
            ['categorical_value' => $value]
        )->assertOk();
    }

    $distribution = collect(
        fromFrontend($this)->actingAs($owner)
            ->getJson("/api/projects/{$project->id}/metrics/analytics")
            ->json('items.0.indicators')
    );

    expect($distribution->firstWhere('value', 'UI')['count'])->toBe(2)
        ->and($distribution->firstWhere('value', 'API')['count'])->toBe(1);
});

test('повторный ввод обновляет значение и время фиксации', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $metric = $project->metrics()->create(['name' => 'Время', 'type' => 'numeric']);
    $bug = makeBug($project, $owner);

    fromFrontend($this)->actingAs($owner)->putJson(
        "/api/projects/{$project->id}/bugs/{$bug->id}/metrics/{$metric->id}",
        ['numeric_value' => 5]
    )->assertOk();

    $firstTime = MetricValue::first()->recorded_at->copy();

    $this->travel(5)->minutes();

    fromFrontend($this)->actingAs($owner)->putJson(
        "/api/projects/{$project->id}/bugs/{$bug->id}/metrics/{$metric->id}",
        ['numeric_value' => 9]
    )->assertOk();

    expect(MetricValue::count())->toBe(1);

    $value = MetricValue::first();
    expect((float) $value->numeric_value)->toBe(9.0)
        ->and($value->recorded_at->greaterThan($firstTime))->toBeTrue();
});

test('метрика чужого проекта не применяется к багу', function () {
    [$owner, $projectA] = bugProjectWithAdmin();
    $other = User::factory()->create();
    $projectB = Project::factory()->create(['owner_id' => $other->id]);
    $projectB->members()->attach($other->id, ['role' => 'admin']);
    $projectB->members()->attach($owner->id, ['role' => 'developer']);

    $metricB = $projectB->metrics()->create(['name' => 'М', 'type' => 'numeric']);
    $bug = makeBug($projectA, $owner);

    fromFrontend($this)->actingAs($owner)->putJson(
        "/api/projects/{$projectA->id}/bugs/{$bug->id}/metrics/{$metricB->id}",
        ['numeric_value' => 1]
    )->assertStatus(404);
});
