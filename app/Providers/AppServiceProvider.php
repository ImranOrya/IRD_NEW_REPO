<?php

namespace App\Providers;

use App\Repositories\ngo\NgoRepository;
use Illuminate\Support\ServiceProvider;
use App\Repositories\ngo\NgoRepositoryInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(NgoRepositoryInterface::class, NgoRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
