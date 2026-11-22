<?php

namespace App\Repositories\Contracts;

use App\Models\Bug;
use App\Models\Metric;
use App\Models\MetricValue;
use App\Models\User;
use Illuminate\Support\Collection;

interface MetricValueRepositoryInterface
{
    /**
     * Значения метрики вместе с багом и его статусом.
     *
     * @return Collection<int, MetricValue>
     */
    public function valuesForMetric(Metric $metric): Collection;

    /**
     * Создаёт или обновляет значение метрики для бага (уникально metric_id + bug_id).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function upsert(Bug $bug, Metric $metric, User $user, array $attributes): MetricValue;
}
