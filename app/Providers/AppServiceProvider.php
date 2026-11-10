<?php

namespace App\Providers;

use App\Http\Middleware\PreventAuthenticationCaching;
use App\Services\Operations\ProtectLivewireFeature;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Livewire::componentHook(ProtectLivewireFeature::class);

        // OAuth endpoints exist only while the account MCP server is switched on.
        if (! config('studio.account_mcp_enabled')) {
            Passport::ignoreRoutes();
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // This database also holds the blog; reset/fresh commands would drop its tables too.
        DB::prohibitDestructiveCommands();

        Livewire::addPersistentMiddleware([AuthenticateSession::class, EnsureEmailIsVerified::class]);
        Livewire::setUpdateRoute(fn ($handle, $path) => Route::post($path, $handle)->middleware(PreventAuthenticationCaching::class));

        // OAuth serves only the account MCP server; bounded lifetimes keep revocation meaningful.
        Passport::authorizationView('mcp.authorize');
        Passport::tokensExpireIn(now()->addDay());
        Passport::refreshTokensExpireIn(now()->addDays(60));

        foreach ([Carbon::class, CarbonImmutable::class] as $dateClass) {
            $dateClass::macro('asDate', fn (): string => $this->format('Y/m/d'));
            $dateClass::macro('asDateTime', fn (): string => $this->format('Y/m/d H:i'));
        }
    }
}
