<?php

namespace App\Repositories\Eloquent;

use App\Models\Bug;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\BugRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BugRepository implements BugRepositoryInterface
{
    public function paginateForProject(Project $project, array $filters, int $perPage): LengthAwarePaginator
    {
        return Bug::query()
            ->where('project_id', $project->id)
            ->with(['status', 'priority', 'severity', 'assignee'])
            ->when(! empty($filters['search']), fn ($query) => $query->where('title', 'like', '%'.$filters['search'].'%'))
            ->when(! empty($filters['status_id']), fn ($query) => $query->where('status_id', $filters['status_id']))
            ->when(! empty($filters['priority_id']), fn ($query) => $query->where('priority_id', $filters['priority_id']))
            ->when(! empty($filters['severity_id']), fn ($query) => $query->where('severity_id', $filters['severity_id']))
            ->when(! empty($filters['assignee_id']), fn ($query) => $query->where('assignee_id', $filters['assignee_id']))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function create(Project $project, User $author, array $attributes): Bug
    {
        return $project->bugs()->create([
            'status_id' => $attributes['status_id'],
            'priority_id' => $attributes['priority_id'],
            'severity_id' => $attributes['severity_id'],
            'assignee_id' => $attributes['assignee_id'] ?? null,
            'created_by' => $author->id,
            'title' => $attributes['title'],
            'description' => $attributes['description'],
            'steps_to_reproduce' => $attributes['steps_to_reproduce'],
        ]);
    }

    public function update(Bug $bug, array $attributes): Bug
    {
        $bug->update($attributes);

        return $bug;
    }

    public function delete(Bug $bug): void
    {
        $bug->delete();
    }

    public function loadForDetails(Bug $bug): Bug
    {
        return $bug->load([
            'status',
            'priority',
            'severity',
            'assignee',
            'creator',
            'comments.user',
            'histories.user',
        ]);
    }
}
