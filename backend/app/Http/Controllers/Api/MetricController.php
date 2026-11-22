<?php

namespace App\Http\Controllers\Api;

use App\Exports\MetricsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Project\StoreMetricRequest;
use App\Http\Requests\Api\Project\UpdateMetricRequest;
use App\Models\Metric;
use App\Models\Project;
use App\Services\MetricService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MetricController extends Controller
{
    public function __construct(private readonly MetricService $metrics) {}

    /**
     * METR-01/METR-03: список метрик проекта (доступен участникам).
     */
    public function index(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        return response()->json(['items' => $this->metrics->list($project)]);
    }

    /**
     * METR-01: создание метрики (только админ).
     */
    public function store(StoreMetricRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('manageMetrics', $project);

        $metric = $this->metrics->create($project, $request->validated());

        return response()->json(['message' => 'Метрика создана.', 'metric' => $metric], 201);
    }

    /**
     * METR-01: редактирование метрики (только админ).
     */
    public function update(UpdateMetricRequest $request, Project $project, Metric $metric): JsonResponse
    {
        Gate::authorize('manageMetrics', $project);

        return response()->json([
            'message' => 'Метрика обновлена.',
            'metric' => $this->metrics->update($project, $metric, $request->validated()),
        ]);
    }

    /**
     * METR-01: удаление метрики (только админ).
     */
    public function destroy(Project $project, Metric $metric): JsonResponse
    {
        Gate::authorize('manageMetrics', $project);

        $this->metrics->delete($project, $metric);

        return response()->json(['message' => 'Метрика удалена.']);
    }

    /**
     * METR-03: аналитика по метрикам.
     */
    public function analytics(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        return response()->json(['items' => $this->metrics->analytics($project)]);
    }

    /**
     * METR-03: экспорт показателей в Excel.
     */
    public function export(Project $project): BinaryFileResponse
    {
        Gate::authorize('view', $project);

        $fileName = 'metrics-project-'.$project->id.'.xlsx';

        return Excel::download(new MetricsExport($this->metrics->reportData($project)), $fileName);
    }
}
