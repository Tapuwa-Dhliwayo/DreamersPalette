<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'email_verified' => $this->hasVerifiedEmail(),
            'roles' => $this->getRoleNames()->values(),
            'permissions' => $this->getAllPermissions()->pluck('name')->sort()->values(),
            'profile' => $this->profile?->only(['id', 'display_name', 'bio', 'avatar_url']),
            'continue_to' => $this->pendingClientAuthorisation($request),
        ];
    }

    /**
     * A client that sent the author to sign in is waiting on its consent screen. The login page
     * navigates in the browser, so Laravel's intended redirect never runs; hand the address over once.
     */
    private function pendingClientAuthorisation(Request $request): ?string
    {
        if (! $request->hasSession()) {
            return null;
        }
        $intended = (string) $request->session()->get('url.intended');
        $path = parse_url($intended, PHP_URL_PATH);
        if ($path !== '/oauth/authorize' || parse_url($intended, PHP_URL_HOST) !== $request->getHost()) {
            return null;
        }
        $request->session()->forget('url.intended');

        return $path.'?'.parse_url($intended, PHP_URL_QUERY);
    }
}
