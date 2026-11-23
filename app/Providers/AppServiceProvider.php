<?php

namespace App\Providers;

use App\Models\GreetingCardEvent;
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
        // Greeting card download counter: limit per visitor (cookie), not per IP —
        // mobile carriers put many users behind one IP (CGNAT).
        $byVisitor = function (Request $request): string {
            $visitor = $request->cookie(GreetingCardEvent::VISITOR_COOKIE);

            return is_string($visitor) && $visitor !== '' ? 'v:' . $visitor : 'ip:' . $request->ip();
        };
        RateLimiter::for('greeting-card-download', fn (Request $request) => Limit::perMinute(20)->by($byVisitor($request)));
        // Card uploads for social app browsers (stored for 1 hour).
        RateLimiter::for('greeting-card-file', fn (Request $request) => Limit::perMinute(10)->by($byVisitor($request)));
    }
}
