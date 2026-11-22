<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    /**
     * DASH-01: виджеты статистики проекта.
     */
    public function show(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        return response()->json($this->dashboard->stats($project));
    }
}
