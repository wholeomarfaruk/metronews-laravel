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
        RateLimiter::for('greeting-card-download', function (Request $request) {
            $visitor = $request->cookie(GreetingCardEvent::VISITOR_COOKIE);

            return Limit::perMinute(20)->by(is_string($visitor) && $visitor !== '' ? 'v:' . $visitor : 'ip:' . $request->ip());
        });
    }
}
