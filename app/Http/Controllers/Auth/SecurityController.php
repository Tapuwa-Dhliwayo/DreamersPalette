<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Fortify\Features;

class SecurityController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        Gate::authorize('manageSecurity', $user);

        return response()->json([
            'password_confirmed' => $request->session()->has('auth.password_confirmed_at')
                && now()->getTimestamp() - $request->session()->get('auth.password_confirmed_at') < config('auth.password_timeout'),
            'two_factor' => [
                'available' => Features::enabled(Features::twoFactorAuthentication()),
                'enrolled' => $user->two_factor_secret !== null,
                'confirmed' => $user->two_factor_confirmed_at !== null,
            ],
            'passkeys_available' => Features::enabled(Features::passkeys()),
            'passkeys' => $user->passkeys()->latest()->get(['id', 'name', 'created_at', 'last_used_at']),
        ]);
    }

    public function confirmPassword(ConfirmPasswordRequest $request): JsonResponse
    {
        Gate::authorize('manageSecurity', $request->user());
        $request->session()->put('auth.password_confirmed_at', now()->getTimestamp());

        return response()->json(['confirmed' => true]);
    }
}
