<?php

use App\Mcp\Servers\ReviewServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('dreamers-review', ReviewServer::class);
