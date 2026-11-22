<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    public function create(array $attributes): User;

    public function findByName(string $name): ?User;

    public function findByNameOrFail(string $name): User;
}
