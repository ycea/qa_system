<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * @group Система
 *
 * Проверка доступности API.
 */
class HealthController extends Controller
{
    /**
     * Проверка состояния API
     *
     *
     * @response 200 scenario="Успех" {"status":"ok"}
     */
    public function __invoke(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}
