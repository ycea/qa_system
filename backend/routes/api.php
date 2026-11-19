<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DictionaryController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectMemberController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::apiResource('projects', ProjectController::class)->only(['index', 'store', 'show', 'destroy']);
    Route::get('/projects/{project}/members', [ProjectMemberController::class, 'index']);
    Route::post('/projects/{project}/members', [ProjectMemberController::class, 'store']);
    Route::delete('/projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy']);
    Route::prefix('/projects/{project}/dictionaries/{type}')
        ->whereIn('type', ['statuses', 'priorities', 'severities'])
        ->group(function () {
            Route::get('/', [DictionaryController::class, 'index']);
            Route::post('/', [DictionaryController::class, 'store']);
            Route::put('/{id}', [DictionaryController::class, 'update']);
            Route::delete('/{id}', [DictionaryController::class, 'destroy']);
        });
});
