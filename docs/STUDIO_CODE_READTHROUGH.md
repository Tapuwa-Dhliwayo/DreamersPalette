# Writing Studio v1 code read-through

Prepared 2026/10/27 on dev. These commits split one integrated candidate into reviewable slices; intermediate commits are not standalone deployment targets. Review and deploy the completed series together. No push, merge or application deployment is included.

## Recommended order

| Read | Commit | Focus |
| --- | --- | --- |
| 1 | da075a0 | Schema, model ownership, policies and permission definitions |
| 2 | 12dd9b8 | Manuscript editing, draft finish/undo/restoration and local recovery |
| 3 | 81d33a0 | World tools, references, maps, calendars and reasoning |
| 4 | 9f6233f | Explicit public-book adoption, release copying and editor grants |
| 5 | b3bd04d | Export capture, sandboxed PDF, expiring downloads and local assistant access |
| 6 | ea001bc | Protected routes, Livewire middleware, shared visual shell and dashboard |
| 7 | d11adfe | Locked dependencies and licensed Flux repository configuration |

The final documentation commit contains this guide, the design history, manual review charters, code audit and Anubis preparation record.

## Start with the data and permissions

Read the studio migrations under `database/migrations/2026_10_24_*` through `2026_10_26_*`, then `Manuscript`, `ManuscriptChapter`, `ManuscriptDraft` and `DraftChapter`. Follow `World` and its tool models next. Policies are in `app/Policies`, capability definitions in `config/authorization.php`.

Questions to challenge: does each row have a clear owner and business meaning? Are immutable copied records distinct from working records? Are lookups indexed? Does an archive retain what historical drafts need? Do private manuscript/world policies deny unrelated users even when they manage public publishing?

## Follow one writing save through to a finished draft

Start at `app/Livewire/Studio/Manuscripts/Workspace.php` and its Blade view, then `app/Livewire/Forms/Studio/ChapterForm.php`, `ManuscriptChapter::saveWriting` and `app/Services/ManuscriptDraftService.php`. Browser state, debounced saves and recovery are in `resources/js/studio.js`.

Inspect generation/revision guards, idempotent retries and local-buffer retention when saving fails. Finishing should lock the old draft and copy it to the next working generation. Undo is constrained; restoration must preserve current writing in a recovery checkpoint before replacing it.

## Follow a world change

Start with `app/Livewire/Studio/Worlds/Workspace.php` for magic, `Development.php` for encyclopedia/people/events/arcs/alignment, then `Thinking.php`, `Maps.php`, `Calendars.php` and `Reasoning.php`. Inputs live in `app/Livewire/Forms/Worlds`; entity rules live in the matching models.

Check that linked IDs belong to the same author's world, manuscript variations remain explicit, private images are authorised, and finished drafts retain copied context. Geography is calibrated flat distance, not a spherical geography engine. Calendar rules cover the documented simple leap cycle. Investigation evidence, knowledge and dependencies are scoped tools rather than inferred canon.

## Cross the publishing and editorial boundaries

Read `app/Services/ManuscriptPublishing.php`, `Book.php`, the manuscript `Publishing` and `ReviewAccess` components, `ReviewGrant` and `ReviewComment`, then `app/Livewire/Studio/Reviews/Workspace.php`.

Check public chapter identity versus reading order, deliberate adoption, locked-draft release provenance and legacy prose guards. Review grants must expire/revoke, editors propose separately from finished prose, and acceptance must reject divergence. In `ContentController`, inspect the limited management metadata and private relation removal from public-management responses.

## Inspect exports and delivery

Read `ManuscriptCompile`, `ManuscriptCompiler`, `DownloadCompileController` and the Compile component. Then inspect `ReviewPacketAccess`, the MCP server/tool and `config/studio.php`.

Check capture sources, pinned world/map versions, signed owner-only downloads, expiration and cleanup. Local MCP must remain unavailable in production; a ZIP packet is the live account-based handoff. The compiler must retain Chrome's sandbox.

Finish with `routes/web.php`, persistent Livewire middleware in `AppServiceProvider`, `resources/views/layouts/studio.blade.php`, studio CSS/JS and the React dashboard changes. Route access, component actions and model policies each have separate responsibilities; hidden controls do not authorise mutations.

## Evidence and remaining release work

Read [STUDIO_V1_CODE_AUDIT.md](STUDIO_V1_CODE_AUDIT.md), then [STUDIO_V1_RELEASE.md](STUDIO_V1_RELEASE.md). The latter records server settings already prepared and the still-pending code/migration deployment. Earlier design/status documents are history; [STUDIO_REMAINING_WORK.md](STUDIO_REMAINING_WORK.md) is the current scope ledger.

No automated tests were added, per project instruction. Existing runtime probes, browser evidence and focused manual charters are the verification record. Use individual workflow charters for hands-on checks; do not reset the shared database to review the code.
