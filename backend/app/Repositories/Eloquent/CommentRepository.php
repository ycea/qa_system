<?php

namespace App\Repositories\Eloquent;

use App\Models\Bug;
use App\Models\Comment;
use App\Models\User;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Support\Collection;

class CommentRepository implements CommentRepositoryInterface
{
    public function listForBug(Bug $bug): Collection
    {
        return $bug->comments()->with('user')->orderBy('created_at')->orderBy('id')->get();
    }

    public function create(Bug $bug, User $author, string $body): Comment
    {
        return $bug->comments()->create([
            'user_id' => $author->id,
            'body' => $body,
        ]);
    }
}
