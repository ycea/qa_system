<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    /**
     * Доступные размеры страницы.
     */
    private const PER_PAGE_OPTIONS = [30, 50, 100];

    private const DEFAULT_PER_PAGE = 30;

    public function __construct(private readonly ProjectService $projects) {}

    /**
     * Список проектов пользователя с пагинацией (PROJ-02).
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
        ]);

        $perPage = (int) ($validated['per_page'] ?? self::DEFAULT_PER_PAGE);

        return response()->json($this->projects->listForUser($request->user(), $perPage));
    }

    /**
     * Создание проекта (PROJ-01).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project = $this->projects->create($request->user(), $validated);

        return response()->json([
            'message' => 'Проект успешно создан.',
            'project' => $project,
        ], 201);
    }

    /**
     * Просмотр одного проекта (PROJ-04 / DASH-01).
     */
    public function show(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        return response()->json($this->projects->getForUser($project));
    }

    public function destroy(Project $project): JsonResponse
    {
        Gate::authorize('delete', $project);

        $this->projects->delete($project);

        return response()->json(['message' => 'Проект удалён.']);
    }
}
