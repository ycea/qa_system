<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('отчёт за день учитывает только баги за сутки', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $closed = $project->statuses()->where('name', 'Закрыт')->first();

    $recent = makeBug($project, $owner, ['status_id' => $closed->id]);
    $recent->created_at = now()->subHours(2);
    $recent->closed_at = now();
    $recent->save();

    $old = makeBug($project, $owner, ['status_id' => $closed->id]);
    $old->created_at = now()->subDays(3);
    $old->closed_at = now()->subDays(2);
    $old->save();

    fromFrontend($this)->actingAs($owner)
        ->getJson("/api/reports?project_id={$project->id}&period=day")
        ->assertOk()
        ->assertJsonPath('period', 'day')
        ->assertJsonPath('total', 1)
        ->assertJsonPath('closed', 1);
});

test('отчёт за месяц включает баги за месяц и считает среднее', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $closed = $project->statuses()->where('name', 'Закрыт')->first();

    $bug = makeBug($project, $owner, ['status_id' => $closed->id]);
    $bug->created_at = now()->subDays(20);
    $bug->closed_at = now()->subDays(19);
    $bug->save();

    $response = fromFrontend($this)->actingAs($owner)
        ->getJson("/api/reports?project_id={$project->id}&period=month");

    $response->assertOk()
        ->assertJsonPath('period', 'month')
        ->assertJsonPath('total', 1)
        ->assertJsonPath('closed', 1);

    expect((float) $response->json('avg_lifetime_hours'))->toBe(24.0);
});

test('пустой проект даёт нулевые показатели без деления на ноль', function () {
    [$owner, $project] = bugProjectWithAdmin();

    $response = fromFrontend($this)->actingAs($owner)
        ->getJson("/api/reports?project_id={$project->id}&period=week");

    $response->assertOk()
        ->assertJsonPath('total', 0)
        ->assertJsonPath('closed', 0);

    expect((float) $response->json('closed_percent'))->toBe(0.0)
        ->and((float) $response->json('avg_lifetime_hours'))->toBe(0.0);
});

test('несуществующий проект отклоняется', function () {
    $user = User::factory()->create();

    fromFrontend($this)->actingAs($user)
        ->getJson('/api/reports?project_id=99999&period=week')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['project_id']);
});
