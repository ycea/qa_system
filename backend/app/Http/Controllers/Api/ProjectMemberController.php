<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Project\AddMemberRequest;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectMemberController extends Controller
{
    public function __construct(private readonly ProjectMemberService $members) {}

    /**
     * PROJ-04: Список участников проекта.
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        $members = $this->members->list($project)->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->pivot->role,
        ]);

        return response()->json([
            'members' => $members,
            'can_manage' => Gate::allows('manageMembers', $project),
        ]);
    }

    /**
     * PROJ-03: Добавление участника по логину.
     */
    public function store(AddMemberRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('manageMembers', $project);

        $role = $request->validated('role');
        $user = $this->members->add($project, $request->validated('name'), $role);

        return response()->json([
            'message' => 'Участник добавлен.',
            'member' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
            ],
        ], 201);
    }

    /**
     * PROJ-05: Удаление участника.
     */
    public function destroy(Project $project, User $user): JsonResponse
    {
        Gate::authorize('manageMembers', $project);

        $this->members->remove($project, $user);

        return response()->json(['message' => 'Участник удалён.']);
    }
}
