<?php

namespace App\Repositories\Eloquent;

use App\Models\Bug;
use App\Models\Metric;
use App\Models\MetricValue;
use App\Models\User;
use App\Repositories\Contracts\MetricValueRepositoryInterface;
use Illuminate\Support\Collection;

class MetricValueRepository implements MetricValueRepositoryInterface
{
    public function valuesForMetric(Metric $metric): Collection
    {
        return $metric->values()->with('bug.status')->get();
    }

    public function upsert(Bug $bug, Metric $metric, User $user, array $attributes): MetricValue
    {
        return MetricValue::updateOrCreate(
            ['metric_id' => $metric->id, 'bug_id' => $bug->id],
            [
                'user_id' => $user->id,
                'numeric_value' => $attributes['numeric_value'] ?? null,
                'categorical_value' => $attributes['categorical_value'] ?? null,
                'recorded_at' => now(),
            ],
        );
    }
}
