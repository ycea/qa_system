<?php

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\Project;
use App\Repositories\Contracts\DictionaryRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DictionaryService
{
    /**
     * Максимум элементов на один справочник проекта.
     */
    private const MAX_ITEMS = 30;

    public function __construct(private readonly DictionaryRepositoryInterface $dictionaries) {}

    /**
     * Список элементов справочника (PROJ-06).
     *
     * @return Collection<int, Model>
     */
    public function list(Project $project, string $type): Collection
    {
        return $this->dictionaries->listForProject($type, $project);
    }

    public function create(Project $project, string $type, string $name): Model
    {
        if ($this->dictionaries->countForProject($type, $project) >= self::MAX_ITEMS) {
            throw ValidationException::withMessages([
                'name' => ['Достигнут лимит '.self::MAX_ITEMS.' элементов справочника.'],
            ]);
        }

        $this->ensureUniqueName($type, $project, $name);

        return $this->dictionaries->createItem($type, $project, ['name' => $name]);
    }

    public function update(Project $project, string $type, int $id, string $name): Model
    {
        $item = $this->dictionaries->findInProject($type, $project, $id);

        $this->ensureUniqueName($type, $project, $name, (int) $item->getKey());

        return $this->dictionaries->updateItem($item, ['name' => $name]);
    }

    public function delete(Project $project, string $type, int $id): void
    {
        $item = $this->dictionaries->findInProject($type, $project, $id);

        if ($this->dictionaries->isInUse($item)) {
            throw new DomainException('Элемент используется и не может быть удалён.');
        }

        if ($type === 'statuses' && $item->is_default) {
            throw new DomainException('Нельзя удалить дефолтный статус.');
        }

        $this->dictionaries->deleteItem($item);
    }

    /**
     * Проверка уникальности названия в рамках проекта.
     */
    private function ensureUniqueName(string $type, Project $project, string $name, ?int $ignoreId = null): void
    {
        if ($this->dictionaries->nameExists($type, $project, $name, $ignoreId)) {
            throw ValidationException::withMessages([
                'name' => ['Элемент с таким названием уже существует.'],
            ]);
        }
    }
}
