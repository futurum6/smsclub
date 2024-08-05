<?php

namespace Futurum\SmsClub;

use Illuminate\Support\ServiceProvider;

class SmsClubServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(SmsClub::class, function ($app) {
            return new SmsClub(config('services.smsclub.login'), config('services.smsclub.token'));
        });
    }

    public function boot()
    {
        // Optional: Load routes, views, translations, etc.
    }
}
