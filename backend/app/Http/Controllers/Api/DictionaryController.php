<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Project\DictionaryRequest;
use App\Models\Priority;
use App\Models\Project;
use App\Models\Severity;
use App\Models\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class DictionaryController extends Controller
{
    /**
     * Карта: тип из URL → модель + проверка использования.
     */
    private const TYPES = [
        'statuses' => Status::class,
        'priorities' => Priority::class,
        'severities' => Severity::class,
    ];

    /**
     * Проверка, что тип валиден. Возвращает класс модели.
     */
    private function resolveModel(string $type): string
    {
        abort_unless(isset(self::TYPES[$type]), 404, 'Неизвестный тип справочника.');

        return self::TYPES[$type];
    }

    /**
     * Список элементов справочника.
     */
    public function index(Project $project, string $type): JsonResponse
    {
        Gate::authorize('manageDictionaries', $project);

        $modelClass = $this->resolveModel($type);

        $items = $modelClass::where('project_id', $project->id)
            ->orderBy('id')
            ->get();

        // Для каждого элемента — флаг "используется ли"
        $items->each(function (Model $item) {
            $item->setAttribute('in_use', $this->isInUse($item));
        });

        return response()->json(['items' => $items]);
    }

    /**
     * Создание элемента.
     */
    public function store(DictionaryRequest $request, Project $project, string $type): JsonResponse
    {
        Gate::authorize('manageDictionaries', $project);

        $modelClass = $this->resolveModel($type);

        // Уникальность в рамках проекта
        $request->validate([
            'name' => [
                Rule::unique($modelClass::make()->getTable())
                    ->where('project_id', $project->id),
            ],
        ], [
            'name.unique' => 'Элемент с таким названием уже существует.',
        ]);

        $item = $modelClass::create([
            'project_id' => $project->id,
            'name' => $request->validated('name'),
            // is_default ставим false для новых статусов — он не должен конкурировать
            // с дефолтным «Новая»
            ...($type === 'statuses' ? ['is_default' => false] : []),
        ]);

        return response()->json([
            'message' => 'Элемент создан.',
            'item' => $item,
        ], 201);
    }

    /**
     * Обновление названия.
     */
    public function update(DictionaryRequest $request, Project $project, string $type, int $id): JsonResponse
    {
        Gate::authorize('manageDictionaries', $project);

        $modelClass = $this->resolveModel($type);

        $item = $modelClass::where('project_id', $project->id)->findOrFail($id);

        $request->validate([
            'name' => [
                Rule::unique($modelClass::make()->getTable())
                    ->where('project_id', $project->id)
                    ->ignore($item->id),
            ],
        ], [
            'name.unique' => 'Элемент с таким названием уже существует.',
        ]);

        $item->update(['name' => $request->validated('name')]);

        return response()->json([
            'message' => 'Элемент обновлён.',
            'item' => $item,
        ]);
    }

    /**
     * Удаление. Запрещено, если элемент используется.
     */
    public function destroy(Project $project, string $type, int $id): JsonResponse
    {
        Gate::authorize('manageDictionaries', $project);

        $modelClass = $this->resolveModel($type);
        $item = $modelClass::where('project_id', $project->id)->findOrFail($id);

        if ($this->isInUse($item)) {
            return response()->json([
                'message' => 'Элемент используется и не может быть удалён.',
            ], 422);
        }

        // Нельзя удалить единственный дефолтный статус
        if ($type === 'statuses' && $item->is_default) {
            return response()->json([
                'message' => 'Нельзя удалить дефолтный статус.',
            ], 422);
        }

        $item->delete();

        return response()->json(['message' => 'Элемент удалён.']);
    }

    /**
     * Используется ли элемент в багах.
     */
    private function isInUse(Model $item): bool
    {
        return match (true) {
            $item instanceof Status => $item->bugs()->exists(),
            $item instanceof Priority => $item->bugs()->exists(),
            $item instanceof Severity => $item->bugs()->exists(),
            default => false,
        };
    }
}
