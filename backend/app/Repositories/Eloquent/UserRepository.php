<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function create(array $attributes): User
    {
        return User::create($attributes);
    }

    public function findByName(string $name): ?User
    {
        return User::where('name', $name)->first();
    }

    public function findByNameOrFail(string $name): User
    {
        return User::where('name', $name)->firstOrFail();
    }
}
