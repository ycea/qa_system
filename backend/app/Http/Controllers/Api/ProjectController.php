<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    /**
     * Список проектов пользователя (PROJ-02).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Проекты, где пользователь участник
        $memberProjects = $user->projects()
            ->withCount('bugs')
            ->get();

        // Проекты, где пользователь владелец
        $ownedProjects = $user->ownedProjects()
            ->withCount('bugs')
            ->get();

        // Объединяем и убираем дубликаты
        $allProjects = $memberProjects->merge($ownedProjects)->unique('id')->values();

        return response()->json($allProjects);
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

        $user = $request->user();

        $project = Project::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'owner_id' => $user->id,
        ]);

        // Владелец автоматически становится админом
        $project->members()->attach($user->id, ['role' => 'admin']);

        return response()->json([
            'message' => 'Проект успешно создан.',
            'project' => $project,
        ], 201);
    }

    /**
     * Просмотр одного проекта (задел под PROJ-04 / DASH-01).
     */
    public function show(Request $request, Project $project): JsonResponse
    {
        $user = $request->user();

        $isMember = $project->members()->where('user_id', $user->id)->exists();
        $isOwner = $project->owner_id === $user->id;

        if (! $isMember && ! $isOwner) {
            abort(403, 'Доступ запрещён.');
        }

        $project->load('members');
        $project->loadCount('bugs');

        return response()->json($project);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('delete', $project);

        $project->delete(); // каскад сработает на уровне БД

        return response()->json(['message' => 'Проект удалён.']);
    }
}
