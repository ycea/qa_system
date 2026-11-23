<?php

use App\Models\Bug;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
/**
 * Хелпер для запросов от имени SPA-фронтенда.
 *
 * Устанавливает заголовок Referer/Origin, чтобы Sanctum распознал запрос
 * как stateful и подключил сессию (как это делает настоящий браузер).
 *
 * Использование:
 *   $response = fromFrontend($this)->postJson('/api/register', [...]);
 *   $response = fromFrontend($this)->actingAs($user)->getJson('/api/me');
 */
function fromFrontend($test, string $url = 'http://localhost:3000')
{
    return $test->from($url)->withHeader('Origin', $url);
}

/**
 * Владелец-админ и его проект.
 *
 * @return array{0: User, 1: Project}
 */
function bugProjectWithAdmin(): array
{
    $owner = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $owner->id]);
    $project->members()->attach($owner->id, ['role' => 'admin']);

    return [$owner, $project];
}

/**
 * Валидная полезная нагрузка для создания бага.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function bugPayload(Project $project, array $overrides = []): array
{
    return array_merge([
        'title' => 'Тестовый баг',
        'description' => 'Описание',
        'steps_to_reproduce' => 'Шаги',
        'priority_id' => $project->priorities()->first()->id,
        'severity_id' => $project->severities()->first()->id,
        'status_id' => $project->statuses()->where('is_default', true)->first()->id,
    ], $overrides);
}

/**
 * Создаёт баг напрямую в БД.
 *
 * @param  array<string, mixed>  $overrides
 */
function makeBug(Project $project, User $author, array $overrides = []): Bug
{
    return Bug::create(array_merge([
        'project_id' => $project->id,
        'status_id' => $project->statuses()->where('is_default', true)->first()->id,
        'priority_id' => $project->priorities()->first()->id,
        'severity_id' => $project->severities()->first()->id,
        'created_by' => $author->id,
        'title' => 'Баг',
        'description' => 'Описание',
        'steps_to_reproduce' => 'Шаги',
    ], $overrides));
}
