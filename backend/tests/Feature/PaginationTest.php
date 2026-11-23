<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Создаёт пользователю N проектов (владелец + админ).
 */
function makeProjectsFor(User $user, int $count): void
{
    for ($i = 0; $i < $count; $i++) {
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $project->members()->attach($user->id, ['role' => 'admin']);
    }
}

test('проекты возвращаются постранично (по 30 по умолчанию)', function () {
    $user = User::factory()->create();
    makeProjectsFor($user, 31);

    $response = fromFrontend($this)->actingAs($user)->getJson('/api/projects');

    $response->assertOk()
        ->assertJsonCount(30, 'data')
        ->assertJsonPath('total', 31)
        ->assertJsonPath('per_page', 30)
        ->assertJsonPath('last_page', 2);

    fromFrontend($this)->actingAs($user)
        ->getJson('/api/projects?page=2')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('размер страницы проектов можно выбрать (50)', function () {
    $user = User::factory()->create();
    makeProjectsFor($user, 31);

    fromFrontend($this)->actingAs($user)
        ->getJson('/api/projects?per_page=50')
        ->assertOk()
        ->assertJsonCount(31, 'data')
        ->assertJsonPath('per_page', 50);
});

test('недопустимый размер страницы отклоняется', function () {
    $user = User::factory()->create();

    fromFrontend($this)->actingAs($user)
        ->getJson('/api/projects?per_page=10')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['per_page']);
});

test('баги возвращаются постранично', function () {
    [$owner, $project] = bugProjectWithAdmin();

    for ($i = 0; $i < 31; $i++) {
        makeBug($project, $owner, ['title' => 'Баг '.$i]);
    }

    $response = fromFrontend($this)->actingAs($owner)
        ->getJson("/api/projects/{$project->id}/bugs");

    $response->assertOk()
        ->assertJsonCount(30, 'data')
        ->assertJsonPath('total', 31)
        ->assertJsonPath('last_page', 2);

    fromFrontend($this)->actingAs($owner)
        ->getJson("/api/projects/{$project->id}/bugs?per_page=100")
        ->assertOk()
        ->assertJsonCount(31, 'data')
        ->assertJsonPath('per_page', 100);
});

test('справочник ограничен 30 элементами', function () {
    [$owner, $project] = bugProjectWithAdmin();

    // 6 дефолтных статусов + 24 = 30 (лимит достигнут).
    for ($i = 0; $i < 24; $i++) {
        $project->statuses()->create(['name' => 'Статус '.$i, 'is_default' => false]);
    }

    fromFrontend($this)->actingAs($owner)
        ->postJson("/api/projects/{$project->id}/dictionaries/statuses", ['name' => 'Лишний'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});
