<?php

namespace App\Repositories\Eloquent;

use App\Models\Metric;
use App\Models\Project;
use App\Repositories\Contracts\MetricRepositoryInterface;
use Illuminate\Support\Collection;

class MetricRepository implements MetricRepositoryInterface
{
    public function listForProject(Project $project): Collection
    {
        return $project->metrics()->orderBy('id')->get();
    }

    public function findInProject(Project $project, int $id): Metric
    {
        return $project->metrics()->findOrFail($id);
    }

    public function nameExists(Project $project, string $name, ?int $ignoreId = null): bool
    {
        $query = $project->metrics()->where('name', $name);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }

    public function create(Project $project, array $attributes): Metric
    {
        return $project->metrics()->create($attributes);
    }

    public function update(Metric $metric, array $attributes): Metric
    {
        $metric->update($attributes);

        return $metric;
    }

    public function delete(Metric $metric): void
    {
        $metric->delete();
    }
}
