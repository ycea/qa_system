<?php

namespace App\Services;

use App\Models\Bug;
use App\Models\Comment;
use App\Models\User;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Support\Collection;

class CommentService
{
    public function __construct(private readonly CommentRepositoryInterface $comments) {}

    /**
     * Комментарии бага в хронологическом порядке (BUG-07).
     *
     * @return Collection<int, Comment>
     */
    public function list(Bug $bug): Collection
    {
        return $this->comments->listForBug($bug);
    }

    /**
     * Добавление комментария (BUG-07).
     */
    public function add(Bug $bug, User $author, string $body): Comment
    {
        return $this->comments->create($bug, $author, $body);
    }
}
