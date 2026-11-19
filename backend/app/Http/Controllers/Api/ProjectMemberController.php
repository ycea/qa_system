<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Project\AddMemberRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectMemberController extends Controller
{
    /**
     * PROJ-04: Список участников проекта.
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        $members = $project->members()
            ->select('users.id', 'users.name', 'users.email')
            ->withPivot('role')
            ->get();

        return response()->json([
            'members' => $members->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->pivot->role,
            ]),
            'can_manage' => Gate::allows('manageMembers', $project),
        ]);
    }

    /**
     * PROJ-03: Добавление участника по логину.
     */
    public function store(AddMemberRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('manageMembers', $project);

        /** @var User $user */
        $user = User::where('name', $request->validated('name'))->firstOrFail();

        // Уже участник?
        if ($project->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Пользователь уже является участником проекта.',
            ], 422);
        }

        $project->members()->attach($user->id, ['role' => $request->validated('role')]);

        return response()->json([
            'message' => 'Участник добавлен.',
            'member' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $request->validated('role'),
            ],
        ], 201);
    }

    /**
     * PROJ-05: Удаление участника.
     */
    public function destroy(Request $request, Project $project, User $user): JsonResponse
    {
        Gate::authorize('manageMembers', $project);

        // Нельзя удалить владельца
        if ($project->owner_id === $user->id) {
            return response()->json([
                'message' => 'Нельзя удалить владельца проекта.',
            ], 422);
        }

        if (! $project->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Пользователь не является участником проекта.',
            ], 404);
        }

        $project->members()->detach($user->id);

        return response()->json(['message' => 'Участник удалён.']);
    }
}
