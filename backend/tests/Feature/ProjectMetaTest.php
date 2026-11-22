<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('участник получает справочники и команду проекта', function () {
    [$owner, $project] = bugProjectWithAdmin();

    $response = fromFrontend($this)->actingAs($owner)
        ->getJson("/api/projects/{$project->id}/meta");

    $response->assertOk()
        ->assertJsonCount(6, 'statuses')
        ->assertJsonCount(4, 'priorities')
        ->assertJsonCount(5, 'severities')
        ->assertJsonCount(1, 'members');
});

test('посторонний не получает meta проекта', function () {
    [, $project] = bugProjectWithAdmin();
    $stranger = User::factory()->create();

    fromFrontend($this)->actingAs($stranger)
        ->getJson("/api/projects/{$project->id}/meta")
        ->assertStatus(403);
});
