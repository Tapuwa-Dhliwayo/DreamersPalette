<?php

use App\Http\Controllers\Auth\SecurityController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\Content\AssetController;
use App\Http\Controllers\Content\AssistantActivityController;
use App\Http\Controllers\Content\ContentController;
use App\Http\Controllers\Content\DashboardController;
use App\Http\Controllers\Content\IncidentStatusController;
use App\Http\Controllers\Content\PoemAnalysisController;
use App\Http\Controllers\Content\ReaderController;
use App\Http\Controllers\Content\WritingAssistantController;
use App\Http\Middleware\PreventAuthenticationCaching;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

Route::get('/auth/features', [SessionController::class, 'features'])->middleware('throttle:60,1');

// Sanctum's stateful API middleware validates the web session password hash.
Route::middleware([PreventAuthenticationCaching::class, 'auth:sanctum', 'can:account.view'])->group(function (): void {
    Route::get('/auth/user', [SessionController::class, 'show']);
    Route::get('/auth/security', [SecurityController::class, 'show'])->middleware(['verified', 'can:account.security.manage']);
});

Route::prefix('reader')->group(function (): void {
    Route::get('/{resource}', [ReaderController::class, 'index'])->whereIn('resource', ['collections', 'poems', 'books', 'chapters']);
    Route::get('/{resource}/{slug}', [ReaderController::class, 'show'])->whereIn('resource', ['collections', 'poems', 'books', 'chapters']);
});

Route::prefix('studio')->middleware([PreventAuthenticationCaching::class, 'auth:sanctum', 'verified', 'can:dashboard.access'])->group(function (): void {
    Route::get('/incidents', IncidentStatusController::class)->middleware('throttle:60,1');
    Route::get('/assistant-activity', [AssistantActivityController::class, 'index'])->middleware(PermissionMiddleware::class.':worlds.update|poems.update');
    Route::post('/assistant-activity/{turn}/acknowledge', [AssistantActivityController::class, 'acknowledge'])->whereNumber('turn')->middleware(PermissionMiddleware::class.':worlds.update|poems.update');
    Route::prefix('poem-analysis')->middleware('can:poems.update')->controller(PoemAnalysisController::class)->group(function (): void {
        Route::get('/connections/{connection}/models', 'models')->whereNumber('connection');
        Route::get('/{poem}', 'index')->whereNumber('poem');
        Route::post('/{poem}/conversations', 'create')->whereNumber('poem');
        Route::get('/{poem}/conversations/{conversation}', 'show')->whereNumber(['poem', 'conversation']);
        Route::post('/{poem}/conversations/{conversation}/restore', 'restore')->whereNumber(['poem', 'conversation']);
        Route::delete('/{poem}/conversations/{conversation}', 'archive')->whereNumber(['poem', 'conversation']);
        Route::post('/{poem}/conversations/{conversation}/messages', 'send')->middleware('throttle:30,1')->whereNumber(['poem', 'conversation']);
        Route::post('/{poem}/conversations/{conversation}/turns/{turn}/stop', 'stop')->whereNumber(['poem', 'conversation', 'turn']);
        Route::post('/{poem}/conversations/{conversation}/turns/{turn}/retry', 'retry')->middleware('throttle:30,1')->whereNumber(['poem', 'conversation', 'turn']);
        Route::post('/{poem}/notes', 'note')->whereNumber('poem');
        Route::post('/{poem}/notes/{note}/restore', 'restoreNote')->whereNumber(['poem', 'note']);
        Route::delete('/{poem}/notes/{note}', 'retireNote')->whereNumber(['poem', 'note']);
    });
    Route::get('/dashboard', [DashboardController::class, 'show']);
    Route::patch('/dashboard/manuscripts/{manuscript}/goal', [DashboardController::class, 'goal'])->middleware('can:manuscripts.update')->whereNumber('manuscript');
    Route::post('/uploads', [AssetController::class, 'upload'])->middleware(['can:assets.upload', 'throttle:30,1']);
    Route::post('/generated-assets', [AssetController::class, 'generate'])->middleware(['can:generated-assets.create', 'throttle:5,1']);
    Route::post('/writing-assist', WritingAssistantController::class)->middleware('throttle:30,1');
    Route::get('/collections/preview/{slug}/poems', [ReaderController::class, 'previewPoems'])->middleware('can:collections.preview');
    Route::get('/{resource}/preview/{slug}', [ReaderController::class, 'preview'])->whereIn('resource', ['collections', 'books']);
    Route::group(['where' => ['resource' => 'collections|poems|books|chapters']], function (): void {
        Route::get('/{resource}', [ContentController::class, 'index']);
        Route::post('/{resource}', [ContentController::class, 'store']);
        Route::get('/{resource}/{id}', [ContentController::class, 'show'])->whereNumber('id');
        Route::patch('/{resource}/{id}', [ContentController::class, 'update'])->whereNumber('id');
        Route::delete('/{resource}/{id}', [ContentController::class, 'archive'])->whereNumber('id');
        Route::patch('/{resource}/{id}/publish', [ContentController::class, 'publish'])->whereNumber('id');
        Route::post('/{resource}/{id}/restore', [ContentController::class, 'restore'])->whereNumber('id');
    });
});
