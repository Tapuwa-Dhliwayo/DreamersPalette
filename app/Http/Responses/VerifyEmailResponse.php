<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;

class VerifyEmailResponse implements VerifyEmailResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['verified' => true]);
        }

        return redirect($request->user()->can('dashboard.access') ? '/dashboard' : '/dashboard/security');
    }
}
