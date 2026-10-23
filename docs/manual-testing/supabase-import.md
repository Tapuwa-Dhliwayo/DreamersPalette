# Publisher imports the source snapshot without changing the blog

Updated: 2026/10/24. Timebox: 40 minutes. This replaces the UUID-target charter.

## Risk level

High: publishing shares users/database with the blog. An incorrect import could
replace family credentials, reassign authors or disturb blog content.

## Goal and stop condition

Import the private source snapshot into a native publishing schema alongside the
blog; preserve blog users/content/ledger and verify every mapped publishing record
and media checksum. Stop on any unexpected blog/account change, source conflict,
missing writing or media mismatch.

## Accounts and test data

Use an isolated local MySQL database loaded with the actual Anubis schema, without
production rows. The agent used an existing synthetic numeric family user 37,
matching the source email, and a marker in the blog migrations ledger. Preserve
its name, password hash, legacy role, verification, remember token and timestamps.
The private snapshot contains one author/profile, 16 collections, 77 poems, one
book, one chapter, 44 generated assets and 45 storage objects.

## Fixture reset and starting state

No database reset. For repeated import checks, rerun the importer; it is idempotent.
Use new isolated databases for empty-install/corruption scenarios. Never refresh
Anubis or the original local database. Private rehearsal continuation instructions
are in storage/app/private/native-rebuild/README.md. Use --env=rebuild with its
socket-only MySQL instance; normal .env still points to the old preserved UUID DB.

## Data shape review

Read the shared-user bootstrap migration, config/database.php, prefixed permission
migrations, SupabaseSnapshot and SupabaseImportService. Native IDs must be assigned
by MySQL; source_uuid exists only on publishing records. Family users are matched
by email, and source author/parent UUIDs become native IDs. Publishing must use its
own migration ledger and must not add factor columns to shared users.

## Intended workflow

1. Record shared user fields, blog table counts/content checksums and the existing
   blog migration ledger. Do not print hashes or private content into the report.
2. Apply publishing migrations on the isolated copy. Expect publishing_migrations
   and native publishing tables, with the original users schema/ledger intact.
3. Run dreamers:import-supabase --dry-run. Expect source counts and prospective
   mappings, with no data/media writes.
4. Import, then --verify. Expect all source writing, themes, timestamps, publication
   state and 45 files to match through native-ID mappings. Source user email must
   map to the existing family user, without replacing its account fields.
5. Repeat import. Expect zero new records and unchanged native IDs, author/parent
   relationships and every shared account field. Extra unrelated native rows remain.
6. Run dreamers:sync-admin normally. Expect publishing permissions only and unchanged
   shared account fields. Do not use --rotate-password or --verify-email in this pass.
7. Compare blog records/ledger with step 1. Expect no changes. Repeat on a new empty
   standalone database to verify creation of a native user when no family account exists.

## Risk map for probing

- Critical: wrong database or reset command. Destructive Artisan commands are prohibited.
- High: existing family account has a different password/name/verification from the
  snapshot. All existing values must survive import and normal admin sync.
- High: source UUID already links to a different user/email, source owner mismatch,
  duplicate UUID/slug/chapter number, missing parent or checksum failure. Refuse
  before destructive writes; retain conflicting unrelated records.
- High: same source slug owned by an unrelated native record, including archived
  content. Refuse instead of merging identities.
- Medium: empty import destination or interrupted copy/SQL transaction. Only
  verified complete media may remain unreferenced; no partial data transaction.
- Medium: source-owned local edits get replaced on repeat import. Reconcile them
  deliberately before final cutover; do not treat the importer as bidirectional sync.

## Permission and state verification

Source snapshot remains private; copied public images are publicly served. Import
never grants publishing roles by itself. Users.role is the blog's legacy field,
not a publishing permission check. Public reader requests must still exclude draft
or archived parents/children after import. Actual login/logout is a separate state
change: Laravel logout rotates the shared remember token, unlike importing.

## Findings

| ID | Phase | Location | Expected result | Actual result | Severity | Status |
| -- | ----- | -------- | --------------- | ------------- | -------- | ------ |
| R1 | State | Copied blog schema + native migrations | Coexist without changing blog users/ledger | Nine publishing migrations applied; users schema and blog ledger preserved | High | Ready for retest |
| R2 | State | Native importer | Translate UUID provenance to native IDs | 140 publishing rows, linked family user and all 45 media files verify | High | Ready for retest |
| R3 | State | Repeat import/admin sync | Preserve shared account fields | Every shared user column unchanged; no duplicate publishing rows | High | Ready for retest |
| R4 | Runtime | Empty standalone database | Native account bootstrap | Entire empty-install import and verification pass | High | Ready for retest |
| R5 | Runtime | Authentication | Isolate factor state | TOTP/recovery flows pass; standard logout rotates shared remember token | Medium | Documented |

No automated tests were added. No live Anubis writes or original-local resets
occurred. Fresh production capture and deployment remain separate work.
