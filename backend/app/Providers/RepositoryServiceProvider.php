<?php

namespace App\Providers;

use App\Repositories\Contracts\BugHistoryRepositoryInterface;
use App\Repositories\Contracts\BugRepositoryInterface;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Repositories\Contracts\DictionaryRepositoryInterface;
use App\Repositories\Contracts\MetricRepositoryInterface;
use App\Repositories\Contracts\MetricValueRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\ReportRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\BugHistoryRepository;
use App\Repositories\Eloquent\BugRepository;
use App\Repositories\Eloquent\CommentRepository;
use App\Repositories\Eloquent\DashboardRepository;
use App\Repositories\Eloquent\DictionaryRepository;
use App\Repositories\Eloquent\MetricRepository;
use App\Repositories\Eloquent\MetricValueRepository;
use App\Repositories\Eloquent\ProjectRepository;
use App\Repositories\Eloquent\ReportRepository;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Привязка интерфейсов репозиториев к их Eloquent-реализациям.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ProjectRepositoryInterface::class, ProjectRepository::class);
        $this->app->bind(DictionaryRepositoryInterface::class, DictionaryRepository::class);
        $this->app->bind(BugRepositoryInterface::class, BugRepository::class);
        $this->app->bind(CommentRepositoryInterface::class, CommentRepository::class);
        $this->app->bind(BugHistoryRepositoryInterface::class, BugHistoryRepository::class);
        $this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);
        $this->app->bind(MetricRepositoryInterface::class, MetricRepository::class);
        $this->app->bind(MetricValueRepositoryInterface::class, MetricValueRepository::class);
        $this->app->bind(ReportRepositoryInterface::class, ReportRepository::class);
    }
}
