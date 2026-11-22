<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\ProjectMetaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ProjectMetaController extends Controller
{
    public function __construct(private readonly ProjectMetaService $meta) {}

    /**
     * Участники и справочники проекта (для всех участников).
     */
    public function show(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        return response()->json($this->meta->meta($project));
    }
}
