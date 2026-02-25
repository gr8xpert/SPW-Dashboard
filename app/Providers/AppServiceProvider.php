<?php

namespace App\Providers;

use App\Services\CreditService;
use App\Services\ImpersonationService;
use App\Services\TenantService;
use App\Services\WidgetSubscriptionService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantService::class);
        $this->app->singleton(WidgetSubscriptionService::class);
        $this->app->singleton(CreditService::class);
        $this->app->singleton(ImpersonationService::class);
    }

    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
