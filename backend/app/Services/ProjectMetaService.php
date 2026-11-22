<?php

namespace App\Services;

use App\Models\Project;
use App\Repositories\Contracts\DictionaryRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;

class ProjectMetaService
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projects,
        private readonly DictionaryRepositoryInterface $dictionaries,
    ) {}

    /**
     * Участники и справочники проекта для форм и фильтров (доступно всем участникам).
     *
     * @return array<string, mixed>
     */
    public function meta(Project $project): array
    {
        return [
            'members' => $this->projects->members($project)->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->pivot->role,
            ])->values(),
            'statuses' => $this->dictionaries->listForProject('statuses', $project),
            'priorities' => $this->dictionaries->listForProject('priorities', $project),
            'severities' => $this->dictionaries->listForProject('severities', $project),
        ];
    }
}
