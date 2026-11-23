<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Bug\StoreCommentRequest;
use App\Models\Bug;
use App\Models\Project;
use App\Services\BugService;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class BugCommentController extends Controller
{
    public function __construct(
        private readonly BugService $bugs,
        private readonly CommentService $comments,
    ) {}

    /**
     * BUG-07: комментарии бага в хронологическом порядке.
     */
    public function index(Project $project, Bug $bug): JsonResponse
    {
        Gate::authorize('view', $bug);
        $this->bugs->ensureBelongsToProject($project, $bug);

        return response()->json(['items' => $this->comments->list($bug)]);
    }

    /**
     * BUG-07: добавление комментария.
     */
    public function store(StoreCommentRequest $request, Project $project, Bug $bug): JsonResponse
    {
        Gate::authorize('view', $bug);
        $this->bugs->ensureBelongsToProject($project, $bug);

        $comment = $this->comments->add($bug, $request->user(), $request->validated('body'));

        return response()->json([
            'message' => 'Комментарий добавлен.',
            'comment' => $comment->load('user'),
        ], 201);
    }
}
