<?php

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\Bug;
use App\Models\Metric;
use App\Models\MetricValue;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\MetricRepositoryInterface;
use App\Repositories\Contracts\MetricValueRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MetricService
{
    public function __construct(
        private readonly MetricRepositoryInterface $metrics,
        private readonly MetricValueRepositoryInterface $values,
    ) {}

    public function list(Project $project): Collection
    {
        return $this->metrics->listForProject($project);
    }

    public function create(Project $project, array $data): Metric
    {
        $this->ensureUniqueName($project, $data['name']);

        return $this->metrics->create($project, [
            'name' => $data['name'],
            'type' => $data['type'],
            'options' => $data['type'] === 'categorical' ? array_values($data['options'] ?? []) : null,
        ]);
    }

    public function update(Project $project, Metric $metric, array $data): Metric
    {
        $this->ensureMetricInProject($project, $metric);
        $this->ensureUniqueName($project, $data['name'], (int) $metric->getKey());

        return $this->metrics->update($metric, [
            'name' => $data['name'],
            'type' => $data['type'],
            'options' => $data['type'] === 'categorical' ? array_values($data['options'] ?? []) : null,
        ]);
    }

    public function delete(Project $project, Metric $metric): void
    {
        $this->ensureMetricInProject($project, $metric);

        $this->metrics->delete($metric);
    }

    public function setValue(Project $project, Bug $bug, Metric $metric, User $user, array $payload): MetricValue
    {
        $this->ensureMetricInProject($project, $metric);

        if ((int) $bug->project_id !== (int) $project->id) {
            throw new DomainException('Баг не найден.', 404);
        }

        $attributes = $metric->type === 'numeric'
            ? ['numeric_value' => $this->requireNumeric($payload)]
            : ['categorical_value' => $this->requireCategorical($metric, $payload)];

        return $this->values->upsert($bug, $metric, $user, $attributes);
    }

    /**
     * Аналитика по метрикам проекта (METR-03).
     *
     * @return array<int, array<string, mixed>>
     */
    public function analytics(Project $project): array
    {
        return $this->metrics->listForProject($project)
            ->map(function (Metric $metric) {
                $values = $this->values->valuesForMetric($metric);

                return [
                    'metric' => [
                        'id' => $metric->id,
                        'name' => $metric->name,
                        'type' => $metric->type,
                        'options' => $metric->options,
                    ],
                    'count' => $values->count(),
                    'indicators' => $metric->type === 'numeric'
                        ? $this->numericIndicators($values)
                        : $this->categoricalIndicators($values),
                    'by_status' => $this->byStatus($values),
                ];
            })
            ->all();
    }

    /**
     * Данные Excel-отчёта по метрикам проекта (METR-03).
     *
     * @return array{summary: array<int, array<string, mixed>>, by_status: array<int, array<string, mixed>>}
     */
    public function reportData(Project $project): array
    {
        $summary = [];
        $byStatus = [];

        foreach ($this->metrics->listForProject($project) as $metric) {
            $values = $this->values->valuesForMetric($metric);
            $numeric = $metric->type === 'numeric';
            $indicators = $numeric ? $this->numericIndicators($values) : null;

            $summary[] = [
                'metric' => $metric->name,
                'type' => $numeric ? 'Числовая' : 'Категориальная',
                'count' => $values->count(),
                'avg' => $indicators['avg'] ?? null,
                'min' => $indicators['min'] ?? null,
                'max' => $indicators['max'] ?? null,
                'sum' => $indicators['sum'] ?? null,
                'distribution' => $numeric ? '—' : $this->distributionText($values),
            ];

            foreach ($this->byStatus($values) as $row) {
                $byStatus[] = [
                    'metric' => $metric->name,
                    'status' => $row['status'],
                    'count' => $row['count'],
                    'avg' => $row['avg'],
                ];
            }
        }

        return ['summary' => $summary, 'by_status' => $byStatus];
    }

    private function ensureMetricInProject(Project $project, Metric $metric): void
    {
        if ((int) $metric->project_id !== (int) $project->id) {
            throw new DomainException('Метрика не найдена.', 404);
        }
    }

    private function ensureUniqueName(Project $project, string $name, ?int $ignoreId = null): void
    {
        if ($this->metrics->nameExists($project, $name, $ignoreId)) {
            throw ValidationException::withMessages([
                'name' => ['Метрика с таким названием уже существует.'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function requireNumeric(array $payload): float
    {
        $value = $payload['numeric_value'] ?? null;

        if ($value === null || $value === '' || ! is_numeric($value)) {
            throw ValidationException::withMessages([
                'numeric_value' => ['Введите числовое значение метрики.'],
            ]);
        }

        return (float) $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function requireCategorical(Metric $metric, array $payload): string
    {
        $value = $payload['categorical_value'] ?? null;

        if ($value === null || trim((string) $value) === '') {
            throw ValidationException::withMessages([
                'categorical_value' => ['Выберите значение метрики.'],
            ]);
        }

        $options = $metric->options ?? [];

        if ($options !== [] && ! in_array($value, $options, true)) {
            throw ValidationException::withMessages([
                'categorical_value' => ['Недопустимое значение метрики.'],
            ]);
        }

        return (string) $value;
    }

    /**
     * @param  Collection<int, MetricValue>  $values
     * @return array{avg: float, min: float, max: float, sum: float}
     */
    private function numericIndicators(Collection $values): array
    {
        $numbers = $values->pluck('numeric_value')
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (float) $value);

        if ($numbers->isEmpty()) {
            return ['avg' => 0.0, 'min' => 0.0, 'max' => 0.0, 'sum' => 0.0];
        }

        return [
            'avg' => round((float) $numbers->avg(), 4),
            'min' => round((float) $numbers->min(), 4),
            'max' => round((float) $numbers->max(), 4),
            'sum' => round((float) $numbers->sum(), 4),
        ];
    }

    /**
     * @param  Collection<int, MetricValue>  $values
     * @return array<int, array{value: string, count: int}>
     */
    private function categoricalIndicators(Collection $values): array
    {
        return $values->pluck('categorical_value')
            ->filter()
            ->countBy()
            ->map(fn (int $count, string $value) => ['value' => $value, 'count' => $count])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, MetricValue>  $values
     */
    private function distributionText(Collection $values): string
    {
        $distribution = $this->categoricalIndicators($values);

        if ($distribution === []) {
            return '—';
        }

        return implode(
            '; ',
            array_map(fn (array $entry) => $entry['value'].': '.$entry['count'], $distribution),
        );
    }

    /**
     * @param  Collection<int, MetricValue>  $values
     * @return array<int, array{status: string, count: int, avg: float|null}>
     */
    private function byStatus(Collection $values): array
    {
        return $values
            ->groupBy(fn (MetricValue $value) => $value->bug?->status?->name ?? '—')
            ->map(function (Collection $group, string $status) {
                $numbers = $group->pluck('numeric_value')
                    ->filter(fn ($value) => $value !== null)
                    ->map(fn ($value) => (float) $value);

                return [
                    'status' => $status,
                    'count' => $group->count(),
                    'avg' => $numbers->isEmpty() ? null : round((float) $numbers->avg(), 4),
                ];
            })
            ->values()
            ->all();
    }
}
