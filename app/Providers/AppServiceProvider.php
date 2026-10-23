<?php

namespace App\Providers;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
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
        // This database also holds the blog; reset/fresh commands would drop its tables too.
        DB::prohibitDestructiveCommands();

        foreach ([Carbon::class, CarbonImmutable::class] as $dateClass) {
            $dateClass::macro('asDate', fn (): string => $this->format('Y/m/d'));
            $dateClass::macro('asDateTime', fn (): string => $this->format('Y/m/d H:i'));
        }
    }
}
