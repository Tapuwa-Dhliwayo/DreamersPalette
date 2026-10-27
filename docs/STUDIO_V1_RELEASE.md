# Writing Studio v1 release checklist

Prepared 2026/10/27. Do not execute this document as an unattended script. It records the release sequence and the checks that require the actual candidate revision.

## Observed Anubis baseline

Read-only inspection found production master at c20cb94, with publishing migrations through 2026_10_17 and no studio tables deployed. Site root is `/home/forge/dreamerspalette.co.za/current`, using shared storage and the existing family MySQL database. Run application/dependency work as forge, not root.

PHP CLI 8.5.12, Node v22.23.1 and required PHP extensions are present. PHP-FPM upload_max_filesize is 2M and post_max_size is 8M. The existing cached Puppeteer Chrome is version 151; its path is outside the observed `/opt/google/chrome/chrome` AppArmor profile. These observations are not proof that PDF export works under FPM.

## Release sequence

1. Review the candidate and commit all intended new files on dev. Record the exact revision, then promote that reviewed revision to master using the normal release process. No commit, push or promotion has been done by this audit.
2. Before migration, verify a restorable backup of the shared MySQL database and shared public/private storage. Record the current release path and revision. Preserve blog tables, users, publishing data, encryption keys and shared storage. Do not import the local development database.
3. Prepare the new Forge release with locked Composer dependencies, including Flux Pro credentials already configured for forge, and a clean locked frontend install/build. Keep APP_ENV=production and APP_DEBUG=false. Preserve the existing production environment and shared storage links.
4. Install the Chrome version expected by the candidate's Puppeteer dependency as forge. Configure BROWSERSHOT_NODE_BINARY if Node is outside the web worker PATH and BROWSERSHOT_CHROME_PATH to the verified executable. Check shared-library dependencies and AppArmor/user-namespace support for that exact executable. Retain Chrome's sandbox: do not add noSandbox. Local machine setup does not configure Anubis.
5. Raise the site's PHP-FPM upload_max_filesize to at least 16M and post_max_size to at least 20M for the application's 12 MiB map uploads. Inspect effective Nginx includes and set client_max_body_size to at least 20M. Validate configurations and reload the affected services through Forge. Do not assume CLI PHP settings affect FPM.
6. Run `php artisan migrate --force` in the candidate release. The application uses its publishing migration ledger alongside the blog ledger. Inspect the reported migration status. Never use fresh, reset, wipe or rollback against the shared database. Do not bootstrap or rotate administrator credentials.
7. Rebuild Laravel production caches using the site's normal Forge deployment procedure. Verify writable private export/map storage and the existing public storage link. Ensure Laravel's scheduler runs once per minute; `dreamers:prune-compiles` is scheduled daily and must remove expired exports without deleting manuscripts or finished drafts.
8. Activate the release and perform smoke checks through the real HTTPS site: sign in; dashboard/sidebar; create an author's real manuscript/world; save and reload; finish a draft; prepare Markdown, sandboxed PDF and review ZIP; confirm the owner can download them. Check an unauthorised account cannot open private resources. Verify existing poems, novels, chapter URLs and images still read correctly. Use the existing workflow charters for deeper sessions.
9. Existing public novels remain unchanged until their author explicitly adopts them into the studio and chooses a finished draft to release. Do not adopt or publish automatically during deployment. Local MCP access remains disabled in production; use the downloadable review ZIP for account-based AI analysis.

## Recovery

If smoke checks fail, stop new studio writing while assessing the failure. A code rollback to the recorded prior release does not remove new schema or restore new writing. Prefer a forward correction and preserve all new records/files. Restore a database backup only after assessing writes since that backup and agreeing the data-recovery consequence; never reverse migrations or replace the shared database casually.

## Release gate

The code candidate is ready for final review when local checks pass. Live readiness additionally requires a verified backup, matching sandboxed Chrome under the web worker, corrected upload limits and the HTTPS smoke checks. These server actions remain pending; this audit made no production changes.

## Anubis preparation completed — 2026/10/27

Tapuwa authorised server preparation, then said not to spend further effort on backups. The already completed protected database/environment/storage snapshot remains at `/root/backups/dreamers-studio-pre-v1-20261027-080521`. Dump completion, compression/archive integrity and checksums were checked; a restore rehearsal was not performed. No further backup work was undertaken.

The following preparation is now applied:

- Nginx site upload limit 20M in `/etc/nginx/forge-conf/3457308/server/studio-uploads.conf`.
- Dedicated PHP worker `/etc/systemd/system/dreamerspalette-fpm.service`, with pool/global configuration `/etc/php/8.5/fpm/dreamerspalette.conf`; socket `/run/php/dreamerspalette-fpm.sock`. Only this site's Nginx upstream was changed. Upload limit 16M and POST limit 20M are pool-owned. The pool has up to five on-demand workers and the same 60-second request termination limit as the previous shared pool.
- The shared PHP service has `RestrictNamespaces=yes`, which prevented Chrome's sandbox under FPM even though Forge-user CLI rendering worked. The dedicated service allows Chrome's required user/mount/PID/network/IPC/UTS namespaces while retaining the existing service's other hardening settings and denying cgroup namespaces. Other sites remain on the original shared service with its restrictions unchanged.
- Exact executable AppArmor profile `/etc/apparmor.d/dreamers-studio-chrome`, following Ubuntu's normal Chrome user-namespace profile convention. Chrome remains sandboxed; no noSandbox option or global user-namespace restriction change was made.
- Candidate-locked Puppeteer dependencies installed separately at `/home/forge/.local/share/dreamers-studio-runtime`; Chrome 156.0.8078.47 installed in forge's cache, with Ubuntu runtime libraries and Liberation fonts. This directory is a readiness probe environment; the deployed release still needs its own locked dependency installation.
- Shared environment prepared with absolute Node and Chrome paths and local MCP disabled. Existing credentials retained. The candidate deployment must rebuild its own configuration cache.
- `/etc/cron.d/dreamerspalette-scheduler` runs the publishing site's scheduler every minute as forge, logging to `/home/forge/.forge/dreamerspalette-scheduler.log`. The old release currently has no scheduled tasks; compile cleanup starts when the new code is deployed.

Verification: the actual dedicated FPM worker generated a valid 15,143-byte PDF with Chrome 156, sandbox enabled and no renderer errors. Worker settings returned 16M/20M. Nginx configuration validation passed; home and login returned HTTPS 200 after switching the site upstream. Nginx, both PHP services and cron are active. No public diagnostic endpoint was created; the private PHP probe was removed.

Production code remains master c20cb94. No studio migrations, code promotion or application deployment were performed. Actual Browsershot integration, authenticated studio use and permissions smoke checks remain deployment checks. Forge PHP-version changes or generated site configuration updates must retain/reapply the dedicated upstream; a future Chrome version needs its exact AppArmor profile reviewed before the configured executable path is changed.
