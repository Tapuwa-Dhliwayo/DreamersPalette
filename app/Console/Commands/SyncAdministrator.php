<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class SyncAdministrator extends Command
{
    protected $signature = 'dreamers:sync-admin {--verify-email : Explicitly attest that the configured email identity has been verified}';

    protected $description = 'Synchronize the designated administrator and role permissions from configuration';

    public function handle(): int
    {
        $email = config('administrator.email');
        $password = config('administrator.password');
        $id = config('administrator.id');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || ! is_string($password) || strlen($password) < 12 || strlen($password) > 72 || ($id && ! Str::isUuid($id))) {
            $this->error('Configure ADMIN_EMAIL, ADMIN_PASSWORD (12–72 bytes), and a valid optional ADMIN_USER_ID. Credentials are never printed.');

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
                    $user = new User(['email' => $email]);
                    if ($id) {
                        $user->id = $id;
                    }
                }
                $changed = $user->synchronizePassword($password);
                if ($this->option('verify-email') && ! $user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                }
                $user->profile()->firstOrCreate(['id' => $user->id], ['display_name' => config('administrator.display_name')]);
                $user->syncRoles([config('administrator.role')]);

                return $changed;
            });
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $this->info($changed ? 'Administrator synchronized. Existing sessions expire on their next protected request; remembered credentials were revoked.' : 'Administrator synchronized. Password unchanged.');
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
