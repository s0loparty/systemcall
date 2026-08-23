<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
        $this->configureTestingAssets();
        $this->configureRoomRateLimiting();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    private function configureTestingAssets(): void
    {
        if ($this->app->environment('testing')) {
            Vite::useHotFile(storage_path('framework/testing-vite.hot'));
        }
    }

    private function configureRoomRateLimiting(): void
    {
        RateLimiter::for('room-chat', function (Request $request) {
            return Limit::perMinute(12)->by($this->roomRateLimitKey($request));
        });

        RateLimiter::for('room-state', function (Request $request) {
            return Limit::perMinute(30)->by($this->roomRateLimitKey($request));
        });
    }

    private function roomRateLimitKey(Request $request): string
    {
        $room = $request->route('room');
        $roomKey = is_object($room) && method_exists($room, 'getRouteKey')
            ? (string) $room->getRouteKey()
            : (string) $room;
        $identity = $request->user()?->getAuthIdentifier()
            ?? $request->cookie('video_call_guest_id')
            ?? $request->ip();

        return $roomKey.'|'.$identity;
    }
}
