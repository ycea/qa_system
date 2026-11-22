<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface DictionaryRepositoryInterface
{
    /**
     * Элементы справочника проекта с проставленным флагом in_use.
     *
     * @return Collection<int, Model>
     */
    public function listForProject(string $type, Project $project): Collection;

    public function findInProject(string $type, Project $project, int $id): Model;

    public function createItem(string $type, Project $project, array $attributes): Model;

    public function updateItem(Model $item, array $attributes): Model;

    public function deleteItem(Model $item): void;

    /**
     * Используется ли элемент справочника в багах.
     */
    public function isInUse(Model $item): bool;

    /**
     * Существует ли в проекте элемент с таким названием (кроме ignoreId).
     */
    public function nameExists(string $type, Project $project, string $name, ?int $ignoreId = null): bool;
}
