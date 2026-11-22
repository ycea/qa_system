<?php

use App\Models\BugHistory;
use App\Models\Comment;
use App\Models\Metric;
use App\Models\MetricValue;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('демо-сидер наполняет систему данными для показа', function () {
    $this->seed(DemoSeeder::class);

    expect(User::whereIn('name', ['admin', 'developer', 'tester'])->count())->toBe(3);
    expect(Project::count())->toBe(2);

    $shop = Project::where('name', 'Интернет-магазин')->firstOrFail();
    expect($shop->statuses()->count())->toBe(6)
        ->and($shop->bugs()->count())->toBe(15)
        ->and($shop->metrics()->count())->toBe(2);

    // Есть закрытые баги, в том числе за последнюю неделю (для DASH-01 и REPT-01).
    expect($shop->bugs()->whereNotNull('closed_at')->count())->toBeGreaterThan(0)
        ->and($shop->bugs()->where('closed_at', '>=', now()->subDays(7))->count())->toBeGreaterThan(0);

    expect(BugHistory::count())->toBeGreaterThan(0)
        ->and(Comment::count())->toBeGreaterThan(0)
        ->and(MetricValue::count())->toBeGreaterThan(0);

    // Метрики: одна числовая и одна категориальная.
    expect(Metric::where('type', 'numeric')->count())->toBe(1)
        ->and(Metric::where('type', 'categorical')->count())->toBe(1);
});
