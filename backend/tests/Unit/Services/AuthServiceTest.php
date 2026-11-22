<?php

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\AuthService;
use Mockery as m;
use Mockery\MockInterface;

afterEach(fn () => m::close());

test('register создаёт пользователя через репозиторий', function () {
    $user = new User(['name' => 'test']);

    /** @var UserRepositoryInterface&MockInterface $repo */
    $repo = m::mock(UserRepositoryInterface::class);
    $repo->shouldReceive('create')
        ->once()
        ->with(['name' => 'test', 'email' => 'test@example.com', 'password' => 'secret123'])
        ->andReturn($user);

    $service = new AuthService($repo);

    $result = $service->register([
        'name' => 'test',
        'email' => 'test@example.com',
        'password' => 'secret123',
    ]);

    expect($result)->toBe($user);
});
