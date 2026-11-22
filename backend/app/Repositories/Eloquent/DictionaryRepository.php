<?php

namespace App\Repositories\Eloquent;

use App\Exceptions\DomainException;
use App\Models\Priority;
use App\Models\Project;
use App\Models\Severity;
use App\Models\Status;
use App\Repositories\Contracts\DictionaryRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class DictionaryRepository implements DictionaryRepositoryInterface
{
    /**
     * Карта: тип из URL → модель справочника.
     */
    private const TYPES = [
        'statuses' => Status::class,
        'priorities' => Priority::class,
        'severities' => Severity::class,
    ];

    public function listForProject(string $type, Project $project): Collection
    {
        $items = $this->modelClass($type)::where('project_id', $project->id)
            ->orderBy('id')
            ->get();

        $items->each(fn (Model $item) => $item->setAttribute('in_use', $this->isInUse($item)));

        return $items;
    }

    public function findInProject(string $type, Project $project, int $id): Model
    {
        return $this->modelClass($type)::where('project_id', $project->id)->findOrFail($id);
    }

    public function createItem(string $type, Project $project, array $attributes): Model
    {
        return $this->modelClass($type)::create([
            'project_id' => $project->id,
            'name' => $attributes['name'],
            // Новым статусам is_default=false: не конкурируют с дефолтным «Новая».
            ...($type === 'statuses' ? ['is_default' => false] : []),
        ]);
    }

    public function updateItem(Model $item, array $attributes): Model
    {
        $item->update(['name' => $attributes['name']]);

        return $item;
    }

    public function deleteItem(Model $item): void
    {
        $item->delete();
    }

    public function isInUse(Model $item): bool
    {
        return match (true) {
            $item instanceof Status => $item->bugs()->exists(),
            $item instanceof Priority => $item->bugs()->exists(),
            $item instanceof Severity => $item->bugs()->exists(),
            default => false,
        };
    }

    public function nameExists(string $type, Project $project, string $name, ?int $ignoreId = null): bool
    {
        $query = $this->modelClass($type)::where('project_id', $project->id)->where('name', $name);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }

    /**
     * Разрешает тип справочника в класс модели.
     */
    private function modelClass(string $type): string
    {
        if (! isset(self::TYPES[$type])) {
            throw new DomainException('Неизвестный тип справочника.', 404);
        }

        return self::TYPES[$type];
    }
}
