<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\SessionUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Fortify\Features;

class SessionController extends Controller
{
    public function show(Request $request): SessionUserResource
    {
        Gate::authorize('view', $request->user());

        return new SessionUserResource($request->user()->load('profile'));
    }

    public function features(): JsonResponse
    {
        return response()->json([
            'two_factor' => Features::enabled(Features::twoFactorAuthentication()),
            'passkeys' => Features::enabled(Features::passkeys()),
        ]);
    }
}
