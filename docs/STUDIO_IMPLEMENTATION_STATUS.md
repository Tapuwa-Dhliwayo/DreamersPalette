# Manuscript studio: implementation and release review

Current delivery and remaining review: [completion ledger](STUDIO_REMAINING_WORK.md), updated 2026/10/26. Historical scope/status statements below do not supersede that ledger.

Updated: 2026/10/24. Branch: dev. Status: implemented locally, awaiting Tapuwa’s visual/workflow review and final code audit. Nothing deployed to Anubis or pushed by this work.

## What is available

Open `/studio` after signing in as an author. The separate Laravel/Livewire/Flux interface provides:

- A private manuscript library and new manuscript creation.
- Chapters with titles, Markdown prose, optional private summaries, and Move Up/Down.
- Write, Preview, Split, focus mode, Markdown controls and selected-text Assist using the existing providers.
- Explicit Save, an idle autosave queue, periodic server checkpoints while typing, and device-local recovery.
- Revision checks, retry-safe saves, stale-draft guards and a local-copy download when saving cannot be confirmed.
- Finish Draft N: capture all server-saved chapters and summaries in one transaction, lock that version, and continue Draft N+1 with identical working content.
- Undo the latest finish while the next draft is untouched, retaining the cancelled snapshot internally.
- Read a finished draft beside current server-saved writing, with changed/unchanged labels and added chapters.
- Download a finished draft as Markdown, excluding private summaries.

The browser’s text buffer is Alpine-owned inside a Livewire-ignored region. Laravel owns validation, permissions, saving and draft transitions. No React studio, duplicate account system, repository layer, or AI analysis subscription has been introduced.

## Database changes

Six new forward migrations:

1. `2026_10_24_000001_create_manuscript_studio_tables.php`: `studio_manuscripts`, `studio_chapters`, `studio_drafts`, `studio_draft_chapters`.
2. `2026_10_24_000002_add_manuscript_permissions.php`: `manuscripts.view`, `.create`, `.update`, `.finish`, `.export`, granted to existing author/admin/superadmin roles. Policies still require manuscript ownership; administrator status does not grant another author’s private prose.

3. `2026_10_25_000001_add_manuscript_draft_undo.php`: cancellation timestamp/actor on finished drafts; replace unique manuscript/number with an indexed lookup so a cancelled number can be completed again. Parent-row locking serialises active completions; request UUIDs remain unique.

4. `2026_10_25_000002_create_world_magic_workspace.php`: worlds, magic systems/rules/cases/variations/versions and lookup records, world permissions, optional manuscript world link and retained draft world context. See [WORLD_MAGIC_WORKSPACE.md](WORLD_MAGIC_WORKSPACE.md).

5. `2026_10_25_000003_create_world_development_tools.php`: people/roles/aliases, fictional events and participants, arcs/beats, alignment charts/placements. See [WORLD_DEVELOPMENT_TOOLS.md](WORLD_DEVELOPMENT_TOOLS.md).

6. `2026_10_26_000001_add_encyclopedia_and_studio_archives.php`: typed encyclopedia, chapter references, recoverable studio archives and actor metadata.

No changes to existing user, blog, poem, book or public chapter tables. No database foreign-key constraints or cascading deletes. Production migration history is preserved.

Initial migrations were rehearsed in isolation. Normal local development now uses the repaired persistent device MySQL database `dreamerspalette_laravel`; all studio migrations have been applied there. Anubis has not been migrated for studio work. Review data is synthetic and is not deployment seed data.

## Evidence from this implementation

- Composer configuration validates; dependency installation and existing global Flux licence authentication were checked earlier.
- PHP style check, focused JavaScript lint, whitespace check and production asset build passed.
- Actual browser: sign in, open manuscript, change prose, save, reload, preview/split, reorder, finish Draft 1, see Draft 2 and compare the locked version.
- Actual browser: select a word, obtain thesaurus suggestions, apply one to the correct range. Assistance drawer traps focus and returns to the editor.
- Desktop layout checked at 1366 × 900; narrow layout checked in the normal browser pane. This is not a physical-tablet keyboard verification.
- Transactional scratch execution: owner permitted/other existing user denied; identical save retry acknowledged once; stale chapter write rejected; repeated finish reuses the same snapshot; Draft 2 edits leave Draft 1 unchanged; locked-model mutation and old-generation saves rejected; incomplete save payload rejected; intentionally empty prose accepted. Probe records rolled back.
- Actual authenticated HTTP: Livewire preview responds successfully, strips unsafe HTML/links, and returns `private, no-store`; finished Markdown download omits private chapter summaries; anonymous studio request redirects to sign in.

No automated tests were added, as required. The focused charter is [manual-testing/manuscript-drafting.md](manual-testing/manuscript-drafting.md).

## Attention and current limits

- This is a first private increment, not the full worldbuilding product.
- Public Book onboarding, publishing from this studio, editor grants/comments/suggestions, inline raw-editor highlighting, map tools, draft restoration, manuscript renaming and rich compile formats are still future work.
- Comparison is side by side with changed labels, not a word-level tracked-change engine. Order changes count as changes.
- A conflict preserves local writing and offers comparison/download. Combining copies currently uses download, reload and explicit recovery; there is no in-place merge engine.
- Recovery is tied to the browser/device/account/chapter/draft generation. It is not full offline synchronisation. Private recovery text remains on that device until acknowledged or deliberately discarded.
- The selected chapter is a replaceable URL query. Browser history does not step through every chapter selection.
- Real tablet keyboard/IME, long chapters near size limits, session-expiry interruptions and several competing browser tabs still need the manual session. PHP and proxy request limits must accommodate JSON-encoded chapter content; the prose limit is 1 MiB in UTF-8 bytes, summary 50,000 bytes.
- The pre-existing React login page attempted a Vercel analytics script locally and logged a script error. The new studio did not produce that error. Removal of the old analytics integration is separate cleanup.
- The user-approved Impeccable update could not reach its remote hosts; installed skill instructions were used.

## Release sequence after approval

1. Finish the visual/workflow review and final code audit on dev. Review the additive migrations and the actual changed files, not only screenshots.
2. Commit the intended changes on dev and merge the approved commit into master. Forge must continue tracking master. Do not copy the local review database or synthetic user to production.
3. Verify Anubis’s shared-database backup and restore path; take a fresh backup before applying migrations. Confirm the new `studio_` table names do not already belong to another application.
4. Check the Forge deployment script installs locked Composer dependencies as the forge user using its existing global Flux credentials, installs frontend dependencies from the lockfile and runs the asset build. Do not put Flux credentials in the repository or site `.env`.
5. Deploy master through Forge’s normal release process. Apply `php artisan migrate --force` against the production site environment and publishing migration ledger. Run its normal cache refresh/optimisation sequence. No `fresh`, reset, rollback or database import.
6. Sign in as the real author, open `/studio`, create a disposable private review manuscript, save/reload, finish a draft and download its Markdown. Confirm the existing public reader, poems, books, login and blog still work.
7. Only then begin real manuscript work. Existing novels have not been imported; use the later explicit onboarding workflow when implemented.

Code rollback should retain the additive studio tables and permission records. Hiding/removing the studio entry is preferable to dropping private writing or reverting shared migrations. Preserve any writing created after release.

Update, 2026/10/25: normal device MySQL is now the development environment; Undo finish was migrated and verified there. Production is unchanged. Decorative header subtitles were removed and the useful-copy rule is recorded in AGENTS.md and UI_STYLE.md.

Release decision, 2026/10/25: Tapuwa will release when most desired writing-studio features are present. Continue local increments and feedback; do not treat manuscript or magic workspace completion as production approval.


## World development expansion · 2026/10/25

Local dev now includes Characters & gods (combined custom roles/aliases), fictional
chronology with participants, arcs with linked events and Flux Pro line charts, and
reusable alignment charts with custom axes and reasoned placements. New forward
migration: `2026_10_25_000003_create_world_development_tools.php` (seven tables).
New finished drafts copy these world records into their retained world context.
See `WORLD_DEVELOPMENT_TOOLS.md` for delivered behavior and limits. No production
deployment; Tapuwa will play with the local tools before further design feedback.


Encyclopedia/reference/archive increment, 2026/10/26: typed entries, selected-text
creation/linking/aliases, advisory preview highlights, retained passage anchors, and
archive/restore across private studio records. See ENCYCLOPEDIA_REFERENCES_AND_ARCHIVING.md
for exact coverage, boundaries and checks. All work remains local on dev.

Relationships/comparisons increment, 2026/10/26: directed, named subject links with
period and reasoning; searchable connection browser; author-defined comparison
criteria and Markdown cells; archive/restore; retained archived subjects; revision
conflicts and finished draft context copies. Migration
`2026_10_26_000002_create_world_relationships_and_comparisons.php` adds two tables.
See WORLD_RELATIONSHIPS_AND_COMPARISONS.md and manual-testing/world-comparison.md.
Local dev only. Whole-world network visualization remains a later refinement.

Compile increment, 2026/10/26: working/finished source selection, chapter subsets,
independent prose/summaries, stable manifests, Markdown and Browsershot PDF, private
expiring downloads, durable format preferences and scheduled temporary-output
cleanup. Migration `2026_10_26_000003_create_manuscript_compiles.php` adds one table
and a manuscript preference column. See MANUSCRIPT_COMPILE.md and
manual-testing/manuscript-compile.md. Chrome sandbox stays enabled; the local
AppArmor mismatch was fixed by using regular Chrome headless mode. No deployment.
