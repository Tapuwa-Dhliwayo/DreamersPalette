# Publishing authentication review

Updated: 2026/10/24. Supersedes the former UUID-user activation package.

Publishing now uses the blog's existing numeric family users. Read
`docs/LARAVEL_SYSTEM_REVIEW.md` for the current final-review path.

1. Read `config/fortify.php`, `routes/auth.php`, `routes/api.php`, `config/sanctum.php`
   and `FortifyServiceProvider`: native session authentication, CSRF, verification,
   throttling and recent confirmation. Bearer tokens are explicitly unsupported;
   unused token tables are not introduced into the family database.
2. Read `config/authorization.php`, `config/permission.php` and policies: publishing
   capabilities live in prefixed Spatie tables, independent of users.role. Resource
   ownership compares native numeric author IDs. There is no blanket admin bypass.
3. Read `User` and `Profile`: shared identity/credentials remain in users; optional
   TOTP secrets/recovery codes and confirmation live in publishing_profiles. The
   Eloquent attribute/save adapter lets native Fortify actions write those fields
   transactionally without adding sensitive fields to the blog's serialized user.
   Passkeys use publishing_passkeys and the canonical site's APP_KEY/origin.
4. Read `SyncAdministrator`: existing accounts are found by numeric ID or email.
   Normal synchronization applies publishing roles without changing shared account
   fields. A new account needs name/email/password; an existing password changes
   only with --rotate-password. This affects blog login too. Email verification
   changes only through explicit --verify-email attestation or normal verification.
5. Read frontend auth/security services and components: capability checks control
   presentation; server policies/middleware are the actual boundary.

The apps do not automatically share browser sessions or factor enrollment.
Keep SESSION_DOMAIN host-only, use a publishing-specific SESSION_COOKIE and a
separate stable APP_KEY. Standard Laravel logout rotates the shared remember token;
that can revoke remembered sign-ins in either app. Explicit password changes affect
both apps; active-session behavior also depends on each app's own middleware.

Two-factor/passkeys remain optional and password resets/public registration remain
disabled, preserving Tapuwa's explicit decisions. These deliberately override the
secure administrator provisioning skill's mandatory MFA/one-time-password defaults.
Do not rotate a shared production password merely because a source snapshot differs.

Actual HTTP rehearsal covered shared account login, numeric ownership, recent
password confirmation, TOTP enrollment/confirmation, recovery-code login, removal,
and logout. The synthetic family user's name, role, password, verification and
timestamps remained intact. Repeat import/admin sync preserved every column;
normal logout rotated only its remember token. No tests were added.

Browser/device passkeys, production mail, staging origin and final deployment still
need verification. No production authentication settings were changed.
