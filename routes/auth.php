<?php

use App\Http\Controllers\Auth\SecurityController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Middleware\AuthorizeAccountSecurity;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;
use Laravel\Fortify\Http\Controllers\RecoveryCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\TwoFactorQrCodeController;
use Laravel\Fortify\Http\Controllers\VerifyEmailController;
use Laravel\Passkeys\Http\Controllers\PasskeyConfirmationController;
use Laravel\Passkeys\Http\Controllers\PasskeyLoginController;
use Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController;

Route::middleware('guest:web')->group(function (): void {
    Route::view('/login', 'app')->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login')->name('login.store');
    if (Features::enabled(Features::twoFactorAuthentication())) {
        Route::post('/two-factor-challenge', [TwoFactorAuthenticatedSessionController::class, 'store'])->middleware('throttle:two-factor')->name('two-factor.login.store');
    }
    if (Features::enabled(Features::passkeys())) {
        Route::get('/passkeys/login/options', [PasskeyLoginController::class, 'index'])->middleware('throttle:passkeys')->name('passkey.login-options');
        Route::post('/passkeys/login', [PasskeyLoginController::class, 'store'])->middleware('throttle:passkeys')->name('passkey.login');
    }
});

// Leaving a session stays possible even after its permissions have been removed.
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth:web')->name('logout');

Route::middleware(['auth:web', 'auth.session', 'can:account.view'])->group(function (): void {
    Route::view('/verify-email', 'app')->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, '__invoke'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware('throttle:6,1')->name('verification.send');
});

Route::middleware(['auth:web', 'auth.session', 'verified', 'can:account.security.manage', AuthorizeAccountSecurity::class, 'throttle:security'])->group(function (): void {
    Route::post('/user/confirm-password', [SecurityController::class, 'confirmPassword'])->name('password.confirm.store');
    Route::view('/user/confirm-password', 'app')->name('password.confirm');

    if (Features::enabled(Features::passkeys())) {
        Route::get('/passkeys/confirm/options', [PasskeyConfirmationController::class, 'index'])->name('passkey.confirm-options');
        Route::post('/passkeys/confirm', [PasskeyConfirmationController::class, 'store'])->name('passkey.confirm');
    }
    Route::middleware('password.confirm')->group(function (): void {
        if (Features::enabled(Features::twoFactorAuthentication())) {
            Route::post('/user/two-factor-authentication', [TwoFactorAuthenticationController::class, 'store'])->name('two-factor.enable');
            Route::post('/user/confirmed-two-factor-authentication', [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
            Route::delete('/user/two-factor-authentication', [TwoFactorAuthenticationController::class, 'destroy'])->name('two-factor.disable');
            Route::get('/user/two-factor-qr-code', [TwoFactorQrCodeController::class, 'show'])->name('two-factor.qr-code');
            Route::get('/user/two-factor-recovery-codes', [RecoveryCodeController::class, 'index'])->name('two-factor.recovery-codes');
            Route::post('/user/two-factor-recovery-codes', [RecoveryCodeController::class, 'store'])->name('two-factor.regenerate-recovery-codes');
        }
        if (Features::enabled(Features::passkeys())) {
            Route::get('/user/passkeys/options', [PasskeyRegistrationController::class, 'index'])->name('passkey.registration-options');
            Route::post('/user/passkeys', [PasskeyRegistrationController::class, 'store'])->name('passkey.store');
            Route::delete('/user/passkeys/{passkey}', [PasskeyRegistrationController::class, 'destroy'])->middleware('can:delete,passkey')->name('passkey.destroy');
        }
    });
});
