<?php

use App\Http\Controllers\Auth\SecurityController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\Content\AssetController;
use App\Http\Controllers\Content\ContentController;
use App\Http\Controllers\Content\ReaderController;
use App\Http\Controllers\Content\WritingAssistantController;
use App\Http\Middleware\PreventAuthenticationCaching;
use Illuminate\Support\Facades\Route;

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
    Route::post('/uploads', [AssetController::class, 'upload'])->middleware(['can:assets.upload', 'throttle:30,1']);
    Route::post('/generated-assets', [AssetController::class, 'generate'])->middleware(['can:generated-assets.create', 'throttle:5,1']);
    Route::post('/writing-assist', WritingAssistantController::class)->middleware('throttle:30,1');
    Route::get('/collections/preview/{slug}/poems', [ReaderController::class, 'previewPoems'])->middleware('can:collections.preview');
    Route::get('/{resource}/preview/{slug}', [ReaderController::class, 'preview'])->whereIn('resource', ['collections', 'books']);
    Route::group(['where' => ['resource' => 'collections|poems|books|chapters']], function (): void {
        Route::get('/{resource}', [ContentController::class, 'index']);
        Route::post('/{resource}', [ContentController::class, 'store']);
        Route::get('/{resource}/{id}', [ContentController::class, 'show'])->whereUuid('id');
        Route::patch('/{resource}/{id}', [ContentController::class, 'update'])->whereUuid('id');
        Route::delete('/{resource}/{id}', [ContentController::class, 'archive'])->whereUuid('id');
        Route::patch('/{resource}/{id}/publish', [ContentController::class, 'publish'])->whereUuid('id');
        Route::post('/{resource}/{id}/restore', [ContentController::class, 'restore'])->whereUuid('id');
    });
});
