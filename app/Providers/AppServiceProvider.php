<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\Notifications\EmailProviderRegistryService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\PaymentSuccessEvent::class,
            \App\Listeners\CreditWalletOnPaymentSuccess::class
        );

        \Illuminate\Support\Facades\Event::subscribe(
            \App\Listeners\DomainNotificationSubscriber::class
        );
    }
}
