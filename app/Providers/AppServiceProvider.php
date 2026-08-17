<?php

namespace App\Providers;

use App\Contracts\Repositories\MemberRepositoryInterface;
use App\Contracts\Services\MemberServiceInterface;
use App\Repositories\MemberRepository;
use App\Services\Members\MemberService;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            MemberRepositoryInterface::class,
            MemberRepository::class,
        );

        $this->app->bind(
            MemberServiceInterface::class,
            MemberService::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
