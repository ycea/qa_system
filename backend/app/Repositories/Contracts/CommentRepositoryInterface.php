<?php

namespace App\Repositories\Contracts;

use App\Models\Bug;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Collection;

interface CommentRepositoryInterface
{
    /**
     * @return Collection<int, Comment>
     */
    public function listForBug(Bug $bug): Collection;

    public function create(Bug $bug, User $author, string $body): Comment;
}
