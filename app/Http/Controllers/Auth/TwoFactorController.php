<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmTwoFactorRequest;
use Illuminate\Support\Facades\Gate;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Contracts\TwoFactorConfirmedResponse;

class TwoFactorController extends Controller
{
    public function confirm(ConfirmTwoFactorRequest $request, ConfirmTwoFactorAuthentication $confirm): TwoFactorConfirmedResponse
    {
        Gate::authorize('manageSecurity', $request->user());
        $confirm($request->user(), $request->validated('code'));

        return app(TwoFactorConfirmedResponse::class);
    }
}
