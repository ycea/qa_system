<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    /**
     * REPT-01: аналитический отчёт по проекту за период.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'period' => ['required', 'string', Rule::in(['day', 'week', 'month'])],
        ], [
            'project_id.required' => 'Выберите проект.',
            'project_id.exists' => 'Проект не найден.',
            'period.required' => 'Выберите период.',
            'period.in' => 'Недопустимый период.',
        ]);

        $project = Project::findOrFail($validated['project_id']);

        Gate::authorize('view', $project);

        return response()->json($this->reports->build($project, $validated['period']));
    }
}
