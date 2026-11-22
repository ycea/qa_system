<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('отчёт считает показатели за период', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $closedStatus = $project->statuses()->where('name', 'Закрыта')->first();

    // Жизнь 10 часов.
    $bugA = makeBug($project, $owner, ['status_id' => $closedStatus->id]);
    $bugA->created_at = now()->subHours(10);
    $bugA->closed_at = now();
    $bugA->save();

    // Жизнь 20 часов.
    $bugB = makeBug($project, $owner, ['status_id' => $closedStatus->id]);
    $bugB->created_at = now()->subHours(20);
    $bugB->closed_at = now();
    $bugB->save();

    // Незакрытый баг не влияет на среднее время жизни.
    makeBug($project, $owner);

    $response = fromFrontend($this)->actingAs($owner)
        ->getJson("/api/reports?project_id={$project->id}&period=week");

    $response->assertOk()
        ->assertJsonPath('total', 3)
        ->assertJsonPath('closed', 2);

    expect((float) $response->json('avg_lifetime_hours'))->toBe(15.0)
        ->and((float) $response->json('closed_percent'))->toBe(66.67);
});

test('баги вне периода не учитываются', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $closedStatus = $project->statuses()->where('name', 'Закрыта')->first();

    $bug = makeBug($project, $owner, ['status_id' => $closedStatus->id]);
    $bug->created_at = now()->subDays(40);
    $bug->closed_at = now()->subDays(40);
    $bug->save();

    $response = fromFrontend($this)->actingAs($owner)
        ->getJson("/api/reports?project_id={$project->id}&period=week");

    $response->assertOk()
        ->assertJsonPath('total', 0)
        ->assertJsonPath('closed', 0);

    expect((float) $response->json('avg_lifetime_hours'))->toBe(0.0);
});

test('недопустимый период отклоняется', function () {
    [$owner, $project] = bugProjectWithAdmin();

    fromFrontend($this)->actingAs($owner)
        ->getJson("/api/reports?project_id={$project->id}&period=year")
        ->assertStatus(422)
        ->assertJsonValidationErrors(['period']);
});

test('посторонний не может построить отчёт', function () {
    [, $project] = bugProjectWithAdmin();
    $stranger = User::factory()->create();

    fromFrontend($this)->actingAs($stranger)
        ->getJson("/api/reports?project_id={$project->id}&period=week")
        ->assertStatus(403);
});
