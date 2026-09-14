<?php

namespace App\Providers;

use App\Contracts\Repositories\MemberRepositoryInterface;
use App\Contracts\Repositories\MembershipPlanRepositoryInterface;
use App\Contracts\Services\CheckInServiceInterface;
use App\Contracts\Services\MemberAccessCredentialServiceInterface;
use App\Contracts\Services\MemberMembershipServiceInterface;
use App\Contracts\Services\MemberServiceInterface;
use App\Contracts\Services\MembershipPlanServiceInterface;
use App\Contracts\Services\PaymentServiceInterface;
use App\Repositories\MemberRepository;
use App\Repositories\MembershipPlanRepository;
use App\Services\CheckIns\CheckInService;
use App\Services\MemberAccessCredentials\MemberAccessCredentialService;
use App\Services\MemberMemberships\MemberMembershipService;
use App\Services\Members\MemberService;
use App\Services\MembershipPlans\MembershipPlanService;
use App\Services\Payments\PaymentService;
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
            CheckInServiceInterface::class,
            CheckInService::class
        );

        $this->app->bind(
            MemberAccessCredentialServiceInterface::class,
            MemberAccessCredentialService::class
        );

        $this->app->bind(
            MemberRepositoryInterface::class,
            MemberRepository::class,
        );

        $this->app->bind(
            MemberServiceInterface::class,
            MemberService::class,
        );

        $this->app->bind(
            MembershipPlanRepositoryInterface::class,
            MembershipPlanRepository::class
        );

        $this->app->bind(
            MembershipPlanServiceInterface::class,
            MembershipPlanService::class
        );

        $this->app->bind(
            MemberMembershipServiceInterface::class,
            MemberMembershipService::class
        );

        $this->app->bind(
            PaymentServiceInterface::class,
            PaymentService::class
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
