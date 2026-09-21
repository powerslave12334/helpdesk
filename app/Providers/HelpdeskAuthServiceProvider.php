<?php

namespace App\Providers;

use App\Auth\HelpdeskUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class HelpdeskAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Auth::provider('helpdesk', function ($app, array $config) {
            return new HelpdeskUserProvider($app['hash'], $config['model'] ?? \App\Models\User::class);
        });
    }
}
