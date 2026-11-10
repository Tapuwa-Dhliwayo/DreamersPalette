<?php

use App\Mcp\Servers\AccountServer;
use App\Mcp\Servers\ReviewServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('dreamers-review', ReviewServer::class);

if (config('studio.account_mcp_enabled')) {
    Route::middleware('throttle:10,1')->group(fn () => Mcp::oauthRoutes());
    Mcp::web('/mcp/account', AccountServer::class)->middleware(['auth:api', 'throttle:120,1']);
}
