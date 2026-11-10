<?php

namespace App\Mcp\Tools\Account;

use App\Models\User;
use App\Services\Exchange\ExternalMaterialUnavailable;
use Closure;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

abstract class AccountTool extends Tool
{
    /**
     * The user is resolved from the bearer token on every call, so revoking a client or an author's
     * grant is observed by a session that is already running.
     */
    protected function user(Request $request): User
    {
        $user = $request->user('api');
        if (! $user instanceof User || ! $user->tokenCan('mcp:use')) {
            throw new ExternalMaterialUnavailable;
        }

        return $user;
    }

    /** Expected refusals are tool errors the model can read; anything else stays a server error. */
    protected function answer(Closure $read): Response|ResponseFactory
    {
        try {
            return Response::structured($read());
        } catch (ExternalMaterialUnavailable|InvalidArgumentException $refusal) {
            return Response::error($refusal->getMessage());
        }
    }
}
