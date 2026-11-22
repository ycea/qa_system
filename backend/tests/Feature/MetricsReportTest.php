<?php

use App\Exports\MetricsExport;
use App\Models\MetricValue;
use App\Models\Project;
use App\Models\User;
use App\Services\MetricService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

uses(RefreshDatabase::class);

/**
 * Проект с числовой и категориальной метриками, значениями на двух багах
 * в разных статусах (для проверки формирования отчёта).
 *
 * @return array{0: User, 1: Project}
 */
function reportProjectWithMetrics(): array
{
    [$owner, $project] = bugProjectWithAdmin();

    $time = $project->metrics()->create(['name' => 'Время исправления, ч', 'type' => 'numeric']);
    $component = $project->metrics()->create([
        'name' => 'Компонент',
        'type' => 'categorical',
        'options' => ['UI', 'API'],
    ]);

    $newStatus = $project->statuses()->where('name', 'Новый')->value('id');
    $workStatus = $project->statuses()->where('name', 'В работе')->value('id');

    $rows = [
        [makeBug($project, $owner, ['status_id' => $newStatus]), 10, 'UI'],
        [makeBug($project, $owner, ['status_id' => $workStatus]), 20, 'API'],
    ];

    foreach ($rows as [$bug, $numeric, $categorical]) {
        MetricValue::create([
            'metric_id' => $time->id,
            'bug_id' => $bug->id,
            'user_id' => $owner->id,
            'numeric_value' => $numeric,
            'recorded_at' => now(),
        ]);
        MetricValue::create([
            'metric_id' => $component->id,
            'bug_id' => $bug->id,
            'user_id' => $owner->id,
            'categorical_value' => $categorical,
            'recorded_at' => now(),
        ]);
    }

    return [$owner, $project];
}

test('формирование данных отчёта по метрикам', function () {
    [, $project] = reportProjectWithMetrics();

    $report = app(MetricService::class)->reportData($project);

    expect($report['summary'])->toHaveCount(2);

    $numeric = collect($report['summary'])->firstWhere('metric', 'Время исправления, ч');
    expect($numeric['type'])->toBe('Числовая')
        ->and($numeric['count'])->toBe(2)
        ->and($numeric['avg'])->toBe(15.0)
        ->and($numeric['min'])->toBe(10.0)
        ->and($numeric['max'])->toBe(20.0)
        ->and($numeric['sum'])->toBe(30.0)
        ->and($numeric['distribution'])->toBe('—');

    $categorical = collect($report['summary'])->firstWhere('metric', 'Компонент');
    expect($categorical['type'])->toBe('Категориальная')
        ->and($categorical['count'])->toBe(2)
        ->and($categorical['avg'])->toBeNull()
        ->and($categorical['distribution'])->toBe('UI: 1; API: 1');

    $byStatus = collect($report['by_status']);
    expect($byStatus->pluck('status')->unique()->sort()->values()->all())
        ->toBe(['В работе', 'Новый'])
        ->and($byStatus->firstWhere('status', 'Новый')['avg'])->toBe(10.0)
        ->and($byStatus->firstWhere('status', 'В работе')['avg'])->toBe(20.0);
});

test('формирование Excel-файла отчёта: листы и строки', function () {
    [, $project] = reportProjectWithMetrics();

    Storage::fake('local');
    $path = 'metrics-report.xlsx';

    Excel::store(new MetricsExport(app(MetricService::class)->reportData($project)), $path, 'local');

    $spreadsheet = IOFactory::load(Storage::disk('local')->path($path));

    expect($spreadsheet->getSheetNames())->toBe(['Сводка', 'По статусам']);

    $summary = $spreadsheet->getSheetByName('Сводка');
    expect($summary->getCell('A1')->getValue())->toBe('Метрика')
        ->and($summary->getCell('H1')->getValue())->toBe('Распределение')
        ->and($summary->getCell('A2')->getValue())->toBe('Время исправления, ч');

    $byStatus = $spreadsheet->getSheetByName('По статусам');
    expect($byStatus->getCell('B1')->getValue())->toBe('Статус')
        ->and($byStatus->getCell('A2')->getValue())->toBe('Время исправления, ч')
        ->and($byStatus->getCell('B2')->getValue())->toBe('Новый');
});

test('эндпоинт экспорта метрик отдаёт xlsx-файл', function () {
    [$owner, $project] = reportProjectWithMetrics();

    fromFrontend($this)->actingAs($owner)
        ->get("/api/projects/{$project->id}/metrics/export")
        ->assertOk()
        ->assertDownload("metrics-project-{$project->id}.xlsx");
});
