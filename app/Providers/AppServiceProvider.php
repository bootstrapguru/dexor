<?php

namespace App\Providers;

use App\Utils\OnBoardingSteps;
use Exception;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @throws Exception
     */
    public function boot(): void
    {
        $onboarding = new OnBoardingSteps();
        $onboarding->loadConfigFile();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
