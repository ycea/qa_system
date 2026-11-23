<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BugCommentController;
use App\Http\Controllers\Api\BugController;
use App\Http\Controllers\Api\BugMetricValueController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DictionaryController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\MetricController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectMemberController;
use App\Http\Controllers\Api\ProjectMetaController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/reports', [ReportController::class, 'index']);
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

    Route::get('/projects/{project}/meta', [ProjectMetaController::class, 'show']);
    Route::get('/projects/{project}/dashboard', [DashboardController::class, 'show']);

    Route::get('/projects/{project}/metrics', [MetricController::class, 'index']);
    Route::post('/projects/{project}/metrics', [MetricController::class, 'store']);
    Route::get('/projects/{project}/metrics/analytics', [MetricController::class, 'analytics']);
    Route::get('/projects/{project}/metrics/export', [MetricController::class, 'export']);
    Route::put('/projects/{project}/metrics/{metric}', [MetricController::class, 'update']);
    Route::delete('/projects/{project}/metrics/{metric}', [MetricController::class, 'destroy']);
    Route::put('/projects/{project}/bugs/{bug}/metrics/{metric}', [BugMetricValueController::class, 'update']);

    Route::get('/projects/{project}/bugs', [BugController::class, 'index']);
    Route::post('/projects/{project}/bugs', [BugController::class, 'store']);
    Route::get('/projects/{project}/bugs/{bug}', [BugController::class, 'show']);
    Route::put('/projects/{project}/bugs/{bug}', [BugController::class, 'update']);
    Route::patch('/projects/{project}/bugs/{bug}/status', [BugController::class, 'changeStatus']);
    Route::patch('/projects/{project}/bugs/{bug}/assignee', [BugController::class, 'assign']);
    Route::get('/projects/{project}/bugs/{bug}/history', [BugController::class, 'history']);
    Route::get('/projects/{project}/bugs/{bug}/comments', [BugCommentController::class, 'index']);
    Route::post('/projects/{project}/bugs/{bug}/comments', [BugCommentController::class, 'store']);
});
