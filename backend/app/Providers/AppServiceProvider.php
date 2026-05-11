<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Fly's edge terminates TLS and proxies to the container over HTTP.
        // Without forceScheme, Filament's asset() URLs render as http:// on an
        // https:// page → browser blocks mixed content → unstyled admin.
        if ($this->app->environment('production') || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
