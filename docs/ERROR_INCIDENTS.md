# Error incidents and feature pauses

Implemented locally on 2026/11/08. No deployment or database migration.

Unexpected server exceptions retain Laravel's normal exception log and additionally
create a private incident reference, feature scope, occurrence count, first/last seen,
code location, argument-free trace, and bounded route/action/actor or assistant-turn
context. Request bodies, URLs with query values, cookies, credentials, exception
messages and manuscript content are not copied into the incident store or email.
Laravel's existing exception logs may still contain exception messages and SQL.

## Behaviour

An incident is either watching or paused. The first unexpected error opens an
incident in the watching state and sends an alert email, but the feature stays
available and the failed request keeps its original status (500) with a notice that
the request failed and was reported. The feature pauses when, within the last
10 minutes of its open incident, there are 3 or more occurrences, or 2 or more
distinct signed-in users hit the same error signature (exception class plus
file:line). Configure with `INCIDENT_PAUSE_AFTER_OCCURRENCES` (3),
`INCIDENT_PAUSE_AFTER_USERS` (2) and `INCIDENT_PAUSE_WINDOW_MINUTES` (10)
(`config/incidents.php`). The incident retains up to the greater of 50 or the configured pause thresholds in recent occurrences
(time, user id or null, signature) pruned to the window, and `paused_at` once the
threshold is met. An operator can also pause a watched incident immediately.

A paused feature stays paused for all users until an operator resolves the
incident (503 responses, blocking notice). Only paused incidents appear in the
status endpoint and block input in the browser; a watching notice shows the banner
without blocking. HTTP reads stay available; HTTP mutations and Livewire round trips to the
paused feature are rejected on the server. Livewire checks run on verified hydrated
components before property updates/actions, and again before action calls. Page
renders identify their component scope for failures. Invalid/stale Livewire payloads
receive a reload instruction rather than causing a shared feature pause.

The studio and React API client display the safe notice and reference, advise the
user to stop work, and retain the current page/unsaved text. On a paused page,
further input/button/submit interactions are prevented; selecting/copying text and
navigation links remain available. Status polling runs every 30 seconds on private
surfaces and is authenticated, permission-checked and non-cacheable. Other features
remain usable. Once the pause is cleared, an affected page advises a deliberate
reload rather than automatically navigating away from unsaved text.

Scope mapping lives in `app/Services/Operations/FeatureScope.php`: world sections,
sandbox, maps/calendars/connections/reasoning, manuscripts/writing/compile/review/
publishing, poetry analysis, publishing collections/poems/books/chapters, account,
uploads, generation and writing tools. Extend the registry when adding a feature.
Unclassified failures are still logged/alerted under `application`, but do not
implicitly disable the whole application. Validation, authentication/authorization,
normal 404s and other expected HTTP client errors do not open incidents.

Caught unexpected internal world/poetry assistant failures also record their own
feature and turn reference. Existing expected provider rejection, validation,
missing-record and access-loss paths do not trip a feature pause. Ordinary browser
JavaScript exceptions and external connectivity failures are not sent as trusted
server incidents; the browser notice integration handles server incident responses.
In-flight work is not cancelled by a pause, and pre-existing requests are not rolled
back by monitoring. Existing application transactions still own atomicity.

## Email and operation

Set `INCIDENT_ALERT_TO` (falls back to `ADMIN_EMAIL`) and `INCIDENT_ALERT_MAILER` to
a configured delivering Laravel mailer. SMTP credentials belong in the environment,
never source control. The default local log mailer does not deliver and never marks
an alert as sent. `log`, `array`, and failover/round-robin transports are deliberately
not accepted for delivery acknowledgement; logging fallback is not delivered email.

The existing Laravel scheduler runs `dreamers:incidents notify` every minute, without
overlap. The first successful delivery is remembered for the open incident. Failed
attempts remain pending and retry after five minutes. Mail delivery uses a separate
process and lock, rather than holding the incident file lock or user request open.
As with SMTP generally, a lost acknowledgement after acceptance can cause a retry.

Two emails per incident at most: one when it opens (state Watching, or Paused if it
was already paused by then) and one when it becomes paused, tracked by
`pause_notified_at` with the same five-minute retry. Bodies start with a `State:` line.

```bash
php artisan dreamers:incidents list      # State: Watching / Paused / Resolved
php artisan dreamers:incidents notify
# Investigate, apply the patch and verify it before resuming the feature:
php artisan dreamers:incidents resolve INCIDENT_REFERENCE
# Resolve every open incident (paused or watching):
php artisan dreamers:incidents release
```

Add `php artisan dreamers:incidents release` to the Forge deploy script after the
migrations. A deploy carrying the fix then resumes paused features; if it did not
fix the error, the threshold pauses the feature again quickly.

Incidents stored before the threshold existed have no `paused_at` key. They were
paused from their first error, so they are still treated as paused (and are not
re-alerted); the next occurrence writes the missing keys.

Operators can manage incidents in the studio at Studio > Incidents
(`/studio/incidents`, route `studio.incidents`), guarded by the `incidents.manage`
permission. It is granted to `superadmin` in `config/authorization.php` and, for the
deployed studio schema, by the forward migration
`2026_11_08_000001_add_incident_permission`. The page lists open incidents first
(state, reference, exception, location, occurrences, first/last seen, email
delivery) and recent resolved ones, with Resolve and Pause now actions authorised
server-side. It is never paused itself. The console commands remain available.
Status responses contain only public notices, never diagnostics. Email includes
environment, feature, incident reference and code diagnostics, together with the
command for resolution.

Incident state is stored atomically under `storage/app/private/operations`, with
file locks and restricted directory permissions. It does not need MySQL, cache or a
queue worker. Keep this directory persistent across releases; do not upload local
incident state to production. The latest record per feature plus 200 prior resolved
incidents are retained. Ordinary Laravel logs retain the correlated references.
This design targets the existing single-host deployment. Multiple application hosts
would require shared durable incident storage and coordinated notification locking.
If storage fails, normal error logging remains and the user gets an honest generic
notice; the original error is not recursively replaced with monitoring failures.

Production activation needs the normal scheduler, persistent writable incident
storage, a real alert mailer/recipient, and rebuilt frontend assets. No live mail,
credentials, scheduler or production configuration was changed by this implementation.

## Verification

`composer verify` covers class references, PHP syntax, Pint, ESLint, route boot,
Blade compilation, a disposable production build and diff whitespace. Scratch PHP
rehearsals outside the repository use disposable SQLite and actual signed Livewire
round trips, real Spatie permission checks/world policy, HTTP middleware and the
exception handler. They exercise error/pause/clear/resume, unrelated features,
validation/404 exclusion, private status permissions, public response redaction,
notification failure/retry/deduplication with captured mail, incident recurrence,
concurrent file writes, storage-failure isolation, standalone HTML error rendering
and background classification. Scratch JavaScript runs the actual notice module with
DOM/network substitutes to check notices, input prevention, copying/navigation,
feature isolation, status polling and cleared-pause signalling.

No automated tests or testing charters were added. MySQL concurrency, real SMTP
acceptance, production scheduling and the new banner in a browser with newly built
assets remain release checks. The existing local sandbox was opened successfully
in the browser without editing or submitting author content.

Attempting `npm run dev -- --host 127.0.0.1` was blocked by the execution sandbox:
`listen EPERM 127.0.0.1:5173`. Served build output was preserved. Start development
assets outside that restriction or rebuild served assets before checking the new
banner in the local browser.

Alert configuration status validates the recipient and supported mail transport; it
does not claim successful delivery. Pause emails and the open-incident list use
the diagnostics captured when the pause was triggered. Initial diagnostics remain
on the incident. Thresholds and window configuration have a minimum of 1. Legacy
incidents retain their previous notification timestamp when upgraded.
