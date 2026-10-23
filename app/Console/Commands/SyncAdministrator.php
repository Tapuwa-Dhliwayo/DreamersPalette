<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class SyncAdministrator extends Command
{
    protected $signature = 'dreamers:sync-admin {--verify-email : Explicitly attest that the configured email identity has been verified} {--rotate-password : Explicitly apply ADMIN_PASSWORD to an existing shared family account}';

    protected $description = 'Synchronize the designated administrator and role permissions from configuration';

    public function handle(): int
    {
        $email = config('administrator.email');
        $password = config('administrator.password');
        $id = config('administrator.id');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || ($id && (! ctype_digit((string) $id) || (int) $id < 1))) {
            $this->error('Configure ADMIN_EMAIL and a positive numeric optional ADMIN_USER_ID. Credentials are never printed.');

            return self::INVALID;
        }
        try {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $changed = DB::transaction(function () use ($email, $password, $id): bool {
                foreach (config('authorization.roles') as $name => $permissions) {
                    foreach ($permissions as $permission) {
                        Permission::findOrCreate($permission, 'web');
                    }
                    Role::findOrCreate($name, 'web')->syncPermissions($permissions);
                }
                $user = $id ? User::find($id) : User::where('email', $email)->first();
                if ($user && $user->email !== $email) {
                    throw new \RuntimeException('ADMIN_USER_ID and ADMIN_EMAIL identify different accounts.');
                }
                if (! $user) {
                    $user = new User(['name' => config('administrator.display_name'), 'email' => $email]);
                    if ($id) {
                        $user->id = $id;
                    }
                }
                $changed = false;
                if (! $user->exists || $this->option('rotate-password')) {
                    if (! is_string($password) || strlen($password) < 12 || strlen($password) > 72) {
                        throw new \RuntimeException('A new account or explicit password rotation requires ADMIN_PASSWORD (12–72 bytes).');
                    }
                    $changed = $user->synchronizePassword($password);
                }
                if ($this->option('verify-email') && ! $user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                }
                $user->profile()->firstOrCreate([], ['display_name' => config('administrator.display_name')]);
                $user->syncRoles([config('administrator.role')]);

                return $changed;
            });
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $this->info($changed ? 'Administrator synchronized. Existing sessions expire on their next protected request; remembered credentials were revoked.' : 'Administrator synchronized. Shared password unchanged. Use --rotate-password only for an intentional family-wide password change.');
            $this->line('2FA and passkeys are optional. Email verification is preserved unless explicitly attested with --verify-email.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('Administrator synchronization failed.', [
                'exception_type' => $exception::class,
                'code' => (string) $exception->getCode(),
                'administrator_id' => $id,
            ]);
            $this->error('Administrator synchronization failed. Check migrations, account identifiers, uniqueness, and configuration. No credentials are included in this output.');

            return self::FAILURE;
        }
    }
}
