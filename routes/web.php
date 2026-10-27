<?php

use App\Http\Controllers\Studio\DownloadCompileController;
use App\Http\Controllers\Studio\DraftMapImageController;
use App\Http\Controllers\Studio\ExportDraftController;
use App\Http\Controllers\Studio\MapImageController;
use App\Http\Middleware\PreventAuthenticationCaching;
use App\Livewire\Studio\Manuscripts\Compile;
use App\Livewire\Studio\Manuscripts\Details;
use App\Livewire\Studio\Manuscripts\Index;
use App\Livewire\Studio\Manuscripts\Publishing;
use App\Livewire\Studio\Manuscripts\RestoreDraft;
use App\Livewire\Studio\Manuscripts\ReviewAccess;
use App\Livewire\Studio\Manuscripts\Workspace;
use App\Livewire\Studio\Worlds\Calendars;
use App\Livewire\Studio\Worlds\Development;
use App\Livewire\Studio\Worlds\Maps;
use App\Livewire\Studio\Worlds\Thinking;
use Illuminate\Support\Facades\Route;

Route::middleware(PreventAuthenticationCaching::class)->group(__DIR__.'/auth.php');

Route::prefix('studio')->name('studio.')->middleware([PreventAuthenticationCaching::class, 'auth:web', 'auth.session', 'verified', 'can:dashboard.access'])->group(function (): void {
    Route::get('/', Index::class)->middleware('can:manuscripts.view')->name('index');
    Route::get('/reviews', App\Livewire\Studio\Reviews\Index::class)->middleware('can:reviews.view')->name('reviews');
    Route::get('/reviews/{grant}', App\Livewire\Studio\Reviews\Workspace::class)->whereNumber('grant')->middleware('can:reviews.view')->name('review');
    Route::get('/worlds', App\Livewire\Studio\Worlds\Index::class)->middleware('can:worlds.view')->name('worlds');
    Route::get('/worlds/{world}/calendars', Calendars::class)->whereNumber('world')->middleware('can:worlds.view')->name('world.calendars');
    Route::get('/worlds/{world}/maps', Maps::class)->whereNumber('world')->middleware('can:worlds.view')->name('world.maps');
    Route::get('/worlds/{world}/maps/{map}/image', MapImageController::class)->whereNumber(['world', 'map'])->middleware('can:worlds.view')->name('world.map.image');
    Route::get('/worlds/{world}/reasoning', App\Livewire\Studio\Worlds\Reasoning::class)->whereNumber('world')->middleware('can:worlds.view')->name('world.reasoning');
    Route::get('/worlds/{world}/thinking', Thinking::class)->whereNumber('world')->middleware('can:worlds.view')->name('world.thinking');
    Route::get('/worlds/{world}/develop', Development::class)->whereNumber('world')->middleware('can:worlds.view')->name('world.develop');
    Route::get('/worlds/{world}', App\Livewire\Studio\Worlds\Workspace::class)->whereNumber('world')->middleware('can:worlds.view')->name('world');
    Route::get('/manuscripts/{manuscript}/review-access', ReviewAccess::class)->middleware('can:manuscripts.view')->whereNumber('manuscript')->name('manuscript.review-access');
    Route::get('/manuscripts/{manuscript}/publishing', Publishing::class)->middleware('can:manuscripts.view')->whereNumber('manuscript')->name('manuscript.publishing');
    Route::get('/manuscripts/{manuscript}/drafts/{draft}/maps/{map}/image', DraftMapImageController::class)->middleware('can:manuscripts.view')->whereNumber(['manuscript', 'draft', 'map'])->name('draft.map.image');
    Route::get('/manuscripts/{manuscript}/details', Details::class)->middleware('can:manuscripts.view')->whereNumber('manuscript')->name('manuscript.details');
    Route::get('/manuscripts/{manuscript}/drafts/{draft}/restore', RestoreDraft::class)->middleware('can:manuscripts.view')->whereNumber(['manuscript', 'draft'])->name('draft.restore');
    Route::get('/manuscripts/{manuscript}/compile', Compile::class)->middleware('can:manuscripts.view')->whereNumber('manuscript')->name('compile');
    Route::get('/manuscripts/{manuscript}/compiles/{compile}/download', DownloadCompileController::class)->middleware(['can:manuscripts.view', 'signed'])->whereNumber(['manuscript', 'compile'])->name('compile.download');
    Route::get('/manuscripts/{manuscript}', Workspace::class)->middleware('can:manuscripts.view')->whereNumber('manuscript')->name('manuscript');
    Route::get('/manuscripts/{manuscript}/drafts/{draft}/export', ExportDraftController::class)
        ->middleware('can:manuscripts.view')->whereNumber(['manuscript', 'draft'])->name('draft.export');
});

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
    ->where('path', '^(?!api(?:/|$)|studio(?:/|$)|livewire(?:[-/]|$)|sanctum(?:/|$)|up(?:/|$)|dashboard(?:/|$)|preview(?:/|$)|user(?:/|$)|passkeys(?:/|$)|login(?:/|$)|logout(?:/|$)|register(?:/|$)|forgot-password(?:/|$)|reset-password(?:/|$)|two-factor-challenge(?:/|$)|email(?:/|$)|verify-email(?:/|$)).*');
