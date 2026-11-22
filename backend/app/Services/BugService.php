<?php

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\Bug;
use App\Models\BugHistory;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\BugHistoryRepositoryInterface;
use App\Repositories\Contracts\BugRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BugService
{
    public function __construct(
        private readonly BugRepositoryInterface $bugs,
        private readonly BugHistoryRepositoryInterface $histories,
    ) {}

    /**
     * Постраничный список багов проекта с фильтрами (BUG-08).
     */
    public function list(Project $project, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->bugs->paginateForProject($project, $filters, $perPage);
    }

    /**
     * Создание бага (BUG-01) с записью в историю.
     */
    public function create(Project $project, User $author, array $data): Bug
    {
        $bug = $this->bugs->create($project, $author, $data);

        $this->histories->add($bug, $author, 'created');

        return $bug;
    }

    /**
     * Детали бага (BUG-02).
     */
    public function get(Project $project, Bug $bug): Bug
    {
        $this->ensureBelongsToProject($project, $bug);

        return $this->bugs->loadForDetails($bug);
    }

    /**
     * Редактирование бага (BUG-03) с записью изменений в историю.
     */
    public function update(Project $project, Bug $bug, User $user, array $data): Bug
    {
        $this->ensureBelongsToProject($project, $bug);

        $fields = ['title', 'description', 'priority_id', 'severity_id', 'steps_to_reproduce'];
        $changes = [];

        foreach ($fields as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }
            if ((string) $bug->{$field} === (string) $data[$field]) {
                continue;
            }
            $changes[$field] = [
                $this->displayValue($project, $field, $bug->{$field}),
                $this->displayValue($project, $field, $data[$field]),
            ];
        }

        if ($changes === []) {
            return $bug;
        }

        $updated = $this->bugs->update($bug, array_intersect_key($data, array_flip($fields)));

        foreach ($changes as $field => [$old, $new]) {
            $this->histories->add($updated, $user, 'updated', $field, $old, $new);
        }

        return $updated;
    }

    /**
     * Смена статуса (BUG-04). Управляет closed_at для отчётности.
     */
    public function changeStatus(Project $project, Bug $bug, User $user, int $statusId): Bug
    {
        $this->ensureBelongsToProject($project, $bug);

        $status = $project->statuses()->findOrFail($statusId);

        if ((int) $bug->status_id === (int) $status->id) {
            return $bug;
        }

        $oldName = $bug->status?->name;

        $bug->status_id = $status->id;
        $bug->closed_at = $status->is_closed
            ? ($bug->closed_at ?? now())
            : null;
        $bug->save();

        $this->histories->add($bug, $user, 'status_changed', 'status', $oldName, $status->name);

        return $bug->load('status');
    }

    /**
     * Назначение исполнителя (BUG-05).
     */
    public function assign(Project $project, Bug $bug, User $user, ?int $assigneeId): Bug
    {
        $this->ensureBelongsToProject($project, $bug);

        $assignee = null;
        if ($assigneeId !== null) {
            $assignee = $project->members()->where('users.id', $assigneeId)->first();

            if ($assignee === null) {
                throw new DomainException('Исполнитель должен быть участником проекта.');
            }
        }

        $oldName = $bug->assignee?->name;

        $bug->assignee_id = $assignee?->id;
        $bug->save();

        $this->histories->add($bug, $user, 'assignee_assigned', 'assignee', $oldName, $assignee?->name);

        return $bug->load('assignee');
    }

    /**
     * Лента истории изменений бага (BUG-02).
     *
     * @return Collection<int, BugHistory>
     */
    public function history(Project $project, Bug $bug): Collection
    {
        $this->ensureBelongsToProject($project, $bug);

        return $this->histories->listForBug($bug);
    }

    /**
     * Проверяет, что баг принадлежит проекту (иначе 404).
     */
    public function ensureBelongsToProject(Project $project, Bug $bug): void
    {
        if ((int) $bug->project_id !== (int) $project->id) {
            throw new DomainException('Баг не найден.', 404);
        }
    }

    /**
     * Человекочитаемое значение поля для ленты истории.
     */
    private function displayValue(Project $project, string $field, mixed $value): ?string
    {
        return match ($field) {
            'priority_id' => $value === null ? null : $project->priorities()->find($value)?->name,
            'severity_id' => $value === null ? null : $project->severities()->find($value)?->name,
            default => $value === null ? null : (string) $value,
        };
    }
}
