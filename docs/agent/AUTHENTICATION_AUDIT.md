# Authentication code review

Originally prepared on 2026/10/17. Activated locally on 2026/10/20 after Tapuwa
authorized migrations and runtime verification. Migrations 000008–000010 are
applied and the administrator has been synchronized. See
`docs/LARAVEL_SYSTEM_REVIEW.md` for current evidence and the final audit path.

## Read in this order

1. `config/fortify.php`: native Fortify password authentication, optional confirmed
   authenticator enrollment and native Laravel passkeys. Public registration and
   browser password changes/resets are disabled.
2. `routes/auth.php`, `routes/api.php`, `routes/web.php`: explicit authentication,
   verification, permission, recent-confirmation, throttling and policy boundaries.
3. `config/authorization.php` and `app/Policies`: capability definitions and model
   ownership/publication rules. There is no blanket superadmin authorization bypass.
4. `app/Console/Commands/SyncAdministrator.php` and `app/Models/User.php`: configured
   account synchronization, password hashing and remembered-session revocation.
5. `app/Providers/FortifyServiceProvider.php`: account permission checks, login
   throttling, passkey authorization and session password-hash tracking.
6. `resources/js/services/authService.js`, `services/_shared/api.js`,
   `app/ProtectedRoute.jsx` and `pages/dashboard/SecurityPage.jsx`: session state,
   same-origin CSRF requests, permission visibility and optional enrollment UI.

## Account and permission rules

The private `.env` supplies `ADMIN_USER_ID`, `ADMIN_EMAIL`, `ADMIN_NAME` and
`ADMIN_PASSWORD`. `dreamers:sync-admin` applies that configuration explicitly;
editing `.env` alone does not mutate the database. Run synchronization after
configuration changes and in deployment commands. Passwords must be 12–72 bytes.
Existing imported UUIDs and profiles are retained. An unchanged password is not
rewritten; a changed password rotates the remember token and makes other sessions
expire at their next protected request. Existing passkeys remain independent
credentials until removed by their owner.

| Role | Capabilities |
| --- | --- |
| reader | Own account and security settings |
| author | Reader capabilities, dashboard and own content operations |
| admin | Author capabilities and content management across authors |
| superadmin | Admin capabilities plus user and role management permissions |

User/role administration screens are not added by this package. Historical
`profiles.role` is retained source content, not an authorization input.
React checks `can(user, permission)` for visibility, the equivalent responsibility
of Blade `@can`. Resource policies and server route/action checks enforce access;
hidden controls do not provide the security boundary. Content policies are ready
for the next API package; existing content service calls still use Supabase.

## Optional authentication features

`AUTH_TWO_FACTOR_ENABLED` and `AUTH_PASSKEYS_ENABLED` independently control feature
availability. Both default to true, allowing enrollment without requiring it.
The account security page lets the owner enroll or remove either method, following
recent password or passkey confirmation. Disabling a feature removes its routes
and challenge while retaining enrolled credentials for possible re-enablement.
Password login remains available. No mandatory MFA or forced password rotation
is imposed, as explicitly requested by Tapuwa.

Passkeys use the official Laravel backend and JavaScript packages. Keep `APP_URL`
canonical; relying-party hostname and exact origins must match. Production needs
HTTPS. Changing the hostname can make previously enrolled passkeys unusable.
Authenticator secrets/recovery codes use Fortify encryption; keep `APP_KEY` safe.

## Activation commands (completed locally)

Check that `DB_DATABASE` is `dreamerspalette_laravel`, never the separate blog
database `dreamerspalette`. Then run migrations and synchronize the administrator:

```bash
php artisan config:clear
php artisan migrate
php artisan dreamers:sync-admin
```

For production, build the configuration cache from the intended environment before
synchronization. Configure mail delivery for email verification. Imported verified
identity remains verified. Only use `--verify-email` when explicitly attesting the
configured address; synchronization does not silently mark new accounts verified.

## Verification and remaining work

Real HTTP checks now cover password login/logout, CSRF, account capabilities,
recent password confirmation, author ownership, TOTP enrollment/confirmation,
recovery-code login and password-change session invalidation. Disposable accounts
were removed. No real administrator factors were changed. No automated tests
were added. Browser/device passkeys and real mail delivery remain unverified.

Content, upload and external-service calls have also moved to Laravel. Read
`docs/LARAVEL_SYSTEM_REVIEW.md` for the completed implementation and remaining
provider configuration, frontend checks and Forge staging/cutover work.
