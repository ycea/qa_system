<?php

namespace App\Repositories\Contracts;

use App\Models\Metric;
use App\Models\Project;
use Illuminate\Support\Collection;

interface MetricRepositoryInterface
{
    /**
     * @return Collection<int, Metric>
     */
    public function listForProject(Project $project): Collection;

    public function findInProject(Project $project, int $id): Metric;

    /**
     * Существует ли метрика с таким названием (кроме ignoreId).
     */
    public function nameExists(Project $project, string $name, ?int $ignoreId = null): bool;

    public function create(Project $project, array $attributes): Metric;

    public function update(Metric $metric, array $attributes): Metric;

    public function delete(Metric $metric): void;
}
