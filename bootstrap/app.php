<?php

use App\Http\Middleware\ProtectPausedFeature;
use App\Services\Operations\IncidentMonitor;
use App\Services\Operations\IncidentResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use League\OAuth2\Server\Exception\OAuthServerException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', ProtectPausedFeature::class);
        $middleware->appendToGroup('api', ProtectPausedFeature::class);
        $middleware->statefulApi();
        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A revoked or expired client token is an expected refusal, not an application incident.
        $exceptions->dontReport(OAuthServerException::class);
        $exceptions->context(function (): array {
            $incident = request()->attributes->get('application_incident');

            return $incident ? ['incident_id' => $incident['id'], 'feature' => $incident['feature']] : [];
        });
        $exceptions->report(function (Throwable $exception): void {
            app(IncidentMonitor::class)->reportRequest($exception, request());
        });
        $exceptions->respond(fn ($response, $exception, $request) => app(IncidentResponse::class)->render($response, $exception, $request));
        $exceptions->dontFlash(['password', 'password_confirmation', 'code', 'recovery_code', 'credential']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
