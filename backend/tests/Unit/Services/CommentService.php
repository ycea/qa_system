<?php

namespace Tests\Unit\Services;

use App\Models\Bug;
use App\Models\Comment;
use App\Models\User;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Services\CommentService;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\TestCase;

class CommentServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_list_returns_comments_for_bug(): void
    {
        $bug = new Bug(['id' => 1]);
        $expectedComments = new Collection([
            new Comment(['id' => 1, 'body' => 'Первый комментарий']),
            new Comment(['id' => 2, 'body' => 'Второй комментарий']),
        ]);

        $repo = Mockery::mock(CommentRepositoryInterface::class);
        $repo->shouldReceive('listForBug')
            ->once()
            ->with($bug)
            ->andReturn($expectedComments);

        $service = new CommentService($repo);
        $result = $service->list($bug);

        $this->assertSame($expectedComments, $result);
    }

    public function test_add_creates_comment_and_returns_it(): void
    {
        $bug = new Bug(['id' => 1]);
        $author = new User(['id' => 5]);
        $body = 'Текст нового комментария';
        $createdComment = new Comment(['id' => 10, 'body' => $body]);

        $repo = Mockery::mock(CommentRepositoryInterface::class);
        $repo->shouldReceive('create')
            ->once()
            ->with($bug, $author, $body)
            ->andReturn($createdComment);

        $service = new CommentService($repo);
        $result = $service->add($bug, $author, $body);

        $this->assertSame($createdComment, $result);
    }
}
