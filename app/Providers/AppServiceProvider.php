<?php

namespace App\Providers;

use App\Repositories\ngo\NgoRepository;
use Illuminate\Support\ServiceProvider;
use App\Repositories\User\UserRepository;
use App\Repositories\ngo\NgoRepositoryInterface;
use App\Repositories\Task\PendingTaskRepository;
use App\Repositories\Approval\ApprovalRepository;
use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\Permission\PermissionRepository;
use App\Repositories\Notification\NotificationRepository;
use App\Repositories\Task\PendingTaskRepositoryInterface;
use App\Repositories\Approval\ApprovalRepositoryInterface;
use App\Repositories\Director\DirectorRepository;
use App\Repositories\Director\DirectorRepositoryInterface;
use App\Repositories\Permission\PermissionRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(NgoRepositoryInterface::class, NgoRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class, PermissionRepository::class);
        $this->app->bind(PendingTaskRepositoryInterface::class, PendingTaskRepository::class);
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
        $this->app->bind(ApprovalRepositoryInterface::class, ApprovalRepository::class);
        $this->app->bind(DirectorRepositoryInterface::class, DirectorRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
