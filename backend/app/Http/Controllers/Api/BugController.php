<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Bug\AssignBugRequest;
use App\Http\Requests\Api\Bug\ChangeStatusRequest;
use App\Http\Requests\Api\Bug\StoreBugRequest;
use App\Http\Requests\Api\Bug\UpdateBugRequest;
use App\Models\Bug;
use App\Models\Project;
use App\Services\BugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class BugController extends Controller
{
    /**
     * Доступные размеры страницы.
     */
    private const PER_PAGE_OPTIONS = [30, 50, 100];

    private const DEFAULT_PER_PAGE = 30;

    public function __construct(private readonly BugService $bugs) {}

    /**
     * BUG-08: постраничный список багов проекта с поиском и фильтрами.
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
        ]);

        $filters = $request->only(['search', 'status_id', 'priority_id', 'severity_id', 'assignee_id']);
        $perPage = (int) ($validated['per_page'] ?? self::DEFAULT_PER_PAGE);

        return response()->json($this->bugs->list($project, $filters, $perPage));
    }

    /**
     * BUG-01: создание бага.
     */
    public function store(StoreBugRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        $bug = $this->bugs->create($project, $request->user(), $request->validated());

        return response()->json([
            'message' => 'Баг создан.',
            'bug' => $bug->load(['status', 'priority', 'severity', 'assignee']),
        ], 201);
    }

    /**
     * BUG-02: детали бага (поля + история + комментарии).
     */
    public function show(Project $project, Bug $bug): JsonResponse
    {
        Gate::authorize('view', $bug);

        return response()->json($this->bugs->get($project, $bug));
    }

    /**
     * BUG-03: редактирование бага.
     */
    public function update(UpdateBugRequest $request, Project $project, Bug $bug): JsonResponse
    {
        Gate::authorize('update', $bug);

        $updated = $this->bugs->update($project, $bug, $request->user(), $request->validated());

        return response()->json([
            'message' => 'Баг обновлён.',
            'bug' => $updated->load(['status', 'priority', 'severity', 'assignee']),
        ]);
    }

    /**
     * BUG-04: смена статуса.
     */
    public function changeStatus(ChangeStatusRequest $request, Project $project, Bug $bug): JsonResponse
    {
        Gate::authorize('update', $bug);

        $updated = $this->bugs->changeStatus($project, $bug, $request->user(), (int) $request->validated('status_id'));

        return response()->json(['message' => 'Статус изменён.', 'bug' => $updated]);
    }

    /**
     * BUG-05: назначение исполнителя.
     */
    public function assign(AssignBugRequest $request, Project $project, Bug $bug): JsonResponse
    {
        Gate::authorize('update', $bug);

        $updated = $this->bugs->assign($project, $bug, $request->user(), $request->validated('assignee_id'));

        return response()->json(['message' => 'Исполнитель назначен.', 'bug' => $updated]);
    }

    /**
     * BUG-02: лента истории изменений.
     */
    public function history(Project $project, Bug $bug): JsonResponse
    {
        Gate::authorize('view', $bug);

        return response()->json(['items' => $this->bugs->history($project, $bug)]);
    }
}
