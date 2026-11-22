<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    /**
     * Регистрация нового пользователя.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->auth->register($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Регистрация успешна.',
            'user' => $user,
        ], 201);
    }

    /**
     * Авторизация пользователя.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $this->auth->attemptLogin($request->validated('name'), $request->validated('password'));

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Вход выполнен успешно.',
            'user' => $request->user(),
        ]);
    }

    /**
     * Выход из системы.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Вы вышли из системы.']);
    }

    /**
     * Получение текущего авторизованного пользователя.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
