<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('дашборд возвращает виджеты статистики', function () {
    [$owner, $project] = bugProjectWithAdmin();
    $new = $project->statuses()->where('name', 'Новый')->first();
    $work = $project->statuses()->where('name', 'В работе')->first();
    $closed = $project->statuses()->where('name', 'Закрыт')->first();

    makeBug($project, $owner, ['status_id' => $new->id]);
    makeBug($project, $owner, ['status_id' => $work->id]);
    makeBug($project, $owner, ['status_id' => $closed->id, 'closed_at' => now()->subDays(2)]);
    makeBug($project, $owner, ['status_id' => $closed->id, 'closed_at' => now()->subDays(30)]);

    $response = fromFrontend($this)->actingAs($owner)
        ->getJson("/api/projects/{$project->id}/dashboard");

    $response->assertOk()
        ->assertJsonPath('total', 4)
        ->assertJsonPath('open', 2)
        ->assertJsonPath('closed_week', 1);
});

test('баг без закрытия не попадает в closed_week', function () {
    [$owner, $project] = bugProjectWithAdmin();
    makeBug($project, $owner);

    fromFrontend($this)->actingAs($owner)
        ->getJson("/api/projects/{$project->id}/dashboard")
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('open', 1)
        ->assertJsonPath('closed_week', 0);
});

test('посторонний не получает дашборд проекта', function () {
    [, $project] = bugProjectWithAdmin();
    $stranger = User::factory()->create();

    fromFrontend($this)->actingAs($stranger)
        ->getJson("/api/projects/{$project->id}/dashboard")
        ->assertStatus(403);
});
