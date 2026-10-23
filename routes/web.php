<?php

use App\Http\Middleware\PreventAuthenticationCaching;
use Illuminate\Support\Facades\Route;

Route::middleware(PreventAuthenticationCaching::class)->group(__DIR__.'/auth.php');

Route::middleware([PreventAuthenticationCaching::class, 'auth:web', 'auth.session', 'verified', 'can:dashboard.access'])->group(function (): void {
    Route::view('/dashboard', 'app');
    foreach (['collections', 'poems', 'books', 'chapters'] as $resource) {
        Route::view('/dashboard/'.$resource, 'app')->middleware('can:'.$resource.'.view');
        Route::view('/dashboard/'.$resource.'/new', 'app')->middleware('can:'.$resource.'.create');
        Route::view('/dashboard/'.$resource.'/{id}/edit', 'app')->whereNumber('id')->middleware('can:'.$resource.'.update');
    }
    Route::view('/dashboard/trash', 'app')->middleware('can:trash.view');
});
Route::view('/dashboard/security', 'app')->middleware([PreventAuthenticationCaching::class, 'auth:web', 'auth.session', 'verified', 'can:account.security.manage']);
Route::view('/preview/collections/{slug}', 'app')->middleware(['auth:web', 'auth.session', 'verified', 'can:collections.preview']);

// Reserved server paths must never turn into a successful SPA response by accident.
Route::view('/{path?}', 'app')
    ->where('path', '^(?!api(?:/|$)|sanctum(?:/|$)|up(?:/|$)|dashboard(?:/|$)|preview(?:/|$)|user(?:/|$)|passkeys(?:/|$)|login(?:/|$)|logout(?:/|$)|register(?:/|$)|forgot-password(?:/|$)|reset-password(?:/|$)|two-factor-challenge(?:/|$)|email(?:/|$)|verify-email(?:/|$)).*');
