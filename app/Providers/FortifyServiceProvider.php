<?php

namespace App\Providers;

use App\Models\Passkey;
use App\Models\User;
use App\Policies\PasskeyPolicy;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\VerifyEmailResponse;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Passkeys;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(VerifyEmailResponse::class, \App\Http\Responses\VerifyEmailResponse::class);
        Fortify::ignoreRoutes();
        Passkeys::ignoreRoutes();
    }

    public function boot(): void
    {
        Passkeys::usePasskeyModel(Passkey::class);
        Gate::policy(Passkey::class, PasskeyPolicy::class);
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::where('email', Str::lower($request->string('email')->toString()))->first();

            return $user && Hash::check($request->string('password')->toString(), $user->password) && $user->can('account.view') ? $user : null;
        });
        Passkeys::authorizeLoginUsing(fn (Request $request, ?User $user): bool => $user?->can('account.view') ?? false);

        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(Str::lower($request->string('email')->toString()).'|'.$request->ip()),
            Limit::perMinute(30)->by($request->ip()),
        ]);
        RateLimiter::for('two-factor', fn (Request $request) => [
            Limit::perMinute(5)->by('two-factor-user:'.($request->session()->get('login.id') ?? 'guest')),
            Limit::perMinute(20)->by('two-factor-ip:'.$request->ip()),
        ]);
        RateLimiter::for('passkeys', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('security', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?? $request->ip()));

        Event::listen(Login::class, function (Login $event): void {
            if ($event->guard === 'web' && request()->hasSession()) {
                request()->session()->put('password_hash_web', auth('web')->hashPasswordForCookie($event->user->getAuthPassword()));
            }
        });
    }
}
