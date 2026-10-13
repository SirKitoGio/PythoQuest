<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
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
        Gate::define('review-records', fn (User $user) => $user->isReviewer());
        Gate::define('administer', fn (User $user) => $user->isAdmin());

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('identifications', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('api-token', fn (Request $request) => Limit::perMinute(5)->by($request->string('email')->lower().'|'.$request->ip()));

        Http::macro('mlService', fn (): PendingRequest => Http::baseUrl(config('services.ml.url'))
            ->connectTimeout(5)
            ->when(config('services.ml.token'), fn (PendingRequest $request, string $token) => $request->withToken($token)));

        DevCommands::register('uv run --directory ml-service uvicorn app.main:app --port 8001', 'ml');
    }
}
