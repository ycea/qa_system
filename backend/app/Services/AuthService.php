<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    /**
     * Регистрация нового пользователя.
     *
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function register(array $data): User
    {
        // Пароль хешируется кастом модели User (cast "hashed").
        return $this->users->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
    }

    /**
     * Проверка учётных данных. Бросает ValidationException при неверных данных.
     */
    public function attemptLogin(string $name, string $password): User
    {
        if (! Auth::attempt(['name' => $name, 'password' => $password])) {
            throw ValidationException::withMessages([
                'name' => ['Неверный логин или пароль.'],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
