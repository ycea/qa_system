<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Bug\SetMetricValueRequest;
use App\Models\Bug;
use App\Models\Metric;
use App\Models\Project;
use App\Services\MetricService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class BugMetricValueController extends Controller
{
    public function __construct(private readonly MetricService $metrics) {}

    /**
     * METR-02: заполнение значения метрики на баг-репорте (любой участник).
     */
    public function update(SetMetricValueRequest $request, Project $project, Bug $bug, Metric $metric): JsonResponse
    {
        Gate::authorize('update', $bug);

        $value = $this->metrics->setValue($project, $bug, $metric, $request->user(), $request->validated());

        return response()->json(['message' => 'Значение сохранено.', 'metric_value' => $value]);
    }
}
