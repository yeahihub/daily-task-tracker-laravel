<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Database\LazyLoadingViolationException;

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
        $loginRateLimitResponse = function(Request $request) {
            if ($request->expectsJson()) {
                return response()->json(
                    [
                        'message' => 'Too many login attempts. Please try again later.',
                    ],
                    429
                );
            }

            return back()
                ->withErrors(['email' => 'Too many login attempts. Please try again later.'])
                ->withInput($request->except('password'));
        };

        RateLimiter::for(
            'login',
            fn(Request $request) => [
                Limit::perMinute(100)->by($request->ip())->response($loginRateLimitResponse),

                Limit::perMinute(5)->by($request->input('email'))->response($loginRateLimitResponse),
            ]
        );

        RateLimiter::for('password-reset-request', fn(Request $request) => [
            Limit::perHour(10)->by($request->ip()),
            Limit::perHour(3)->by($request->input('email')),
        ]);

        RateLimiter::for('password-reset', fn(Request $request) => [
            Limit::perHour(5)->by($request->ip()),
            Limit::perHour(3)->by($request->input('email')),
        ]);

        Password::defaults(function() {
            if ($this->app->isLocal()) {
                return Password::min(8);
            }

            return Password::min(8)
                ->mixedCase()
                ->uncompromised()
                ->letters()
                ->numbers()
                ->symbols();
        });

        Model::shouldBeStrict();

        Model::handleLazyLoadingViolationUsing(function($model, $relation): void {
            $class = get_class($model);

            if (app()->isLocal()) {
                throw new LazyLoadingViolationException($model, $relation);
            }

            info('Attempted to lazy load "' . $relation . '" on model "' . $class . '"');
        });

        DB::prohibitDestructiveCommands(app()->isProduction());
        Date::use(CarbonImmutable::class);
    }
}
