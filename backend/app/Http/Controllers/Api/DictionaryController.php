<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Project\DictionaryRequest;
use App\Models\Project;
use App\Services\DictionaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class DictionaryController extends Controller
{
    public function __construct(private readonly DictionaryService $dictionaries) {}

    /**
     * Список элементов справочника.
     */
    public function index(Project $project, string $type): JsonResponse
    {
        Gate::authorize('manageDictionaries', $project);

        return response()->json([
            'items' => $this->dictionaries->list($project, $type),
        ]);
    }

    /**
     * Создание элемента справочника.
     */
    public function store(DictionaryRequest $request, Project $project, string $type): JsonResponse
    {
        Gate::authorize('manageDictionaries', $project);

        $item = $this->dictionaries->create($project, $type, $request->validated('name'));

        return response()->json([
            'message' => 'Элемент создан.',
            'item' => $item,
        ], 201);
    }

    /**
     * Обновление названия элемента.
     */
    public function update(DictionaryRequest $request, Project $project, string $type, int $id): JsonResponse
    {
        Gate::authorize('manageDictionaries', $project);

        $item = $this->dictionaries->update($project, $type, $id, $request->validated('name'));

        return response()->json([
            'message' => 'Элемент обновлён.',
            'item' => $item,
        ]);
    }

    /**
     * Удаление элемента (запрещено для используемых и дефолтных).
     */
    public function destroy(Project $project, string $type, int $id): JsonResponse
    {
        Gate::authorize('manageDictionaries', $project);

        $this->dictionaries->delete($project, $type, $id);

        return response()->json(['message' => 'Элемент удалён.']);
    }
}
