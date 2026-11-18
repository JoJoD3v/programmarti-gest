<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Build reset links from APP_URL, never from the request Host header.
        ResetPassword::createUrlUsing(fn (object $user, string $token): string => rtrim(config('app.url'), '/').route('password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ], false));

        // Cap paid OpenAI calls per user.
        RateLimiter::for('ai-enhance', fn (Request $request): array => [
            Limit::perMinute(5)->by($request->user()?->id ?: $request->ip()),
            Limit::perDay(50)->by($request->user()?->id ?: $request->ip()),
        ]);
    }
}
