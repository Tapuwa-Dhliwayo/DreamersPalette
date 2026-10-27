# Studio completion ledger

Updated 2026/10/27. Committed locally on branch dev. No push or application deployment.
See STUDIO_CODE_READTHROUGH.md for the commit series; Anubis runtime preparation
is recorded in STUDIO_V1_RELEASE.md.
The first-version audit and dashboard cleanup are recorded in
[STUDIO_V1_CODE_AUDIT.md](STUDIO_V1_CODE_AUDIT.md); deployment gates and sequence
are in [STUDIO_V1_RELEASE.md](STUDIO_V1_RELEASE.md). Tapuwa requested live use
after this review so real writing need not be entered twice.
This ledger supersedes older unchecked status lists. Implemented means the local
feature exists and has engineering verification; it does not replace Tapuwa’s
final code audit or hands-on review.

## Delivered scope

| Area | Available now | Evidence |
| --- | --- | --- |
| Manuscript | Quiet Markdown editor/Assist, chapters/summaries/order/archive, autosave/recovery/conflicts, locked named drafts, compare and undo untouched finish | Earlier browser review plus current recovery probes |
| Details/restoration | Rename/description, revision checks, restore an older draft after first preserving current writing in a locked recovery checkpoint | Browser details/restore and transactional retry/stale-generation checks |
| World foundation | Encyclopedia/types/aliases, selected-text links/creation, magic rules/cases/variations, immutable snapshots, characters/custom roles, events, arcs/Flux charts, alignment tools | Earlier local/browser workflow checks retained |
| Thinking | Relationships/comparisons; explicit dependencies, transitive change impact and cycle warnings; investigations with sourced evidence/counter-evidence/decisions | Scratch chain/cycle/scope/archive checks; browser investigation save |
| Knowledge | Author truth vs individual beliefs, learned-at events, manuscript reveal chapters and reveal order, archive/restore, finished-world capture | Scoped save/rejection probes and browser perspective save |
| Geography | Blank/image maps, location pins, keyboard/drag coordinates, calibrated flat distances, ordered journeys, immutable versions, private images, archive/restore | Browser image/history/keyboard/archive/restore/narrow-layout checks; geometry/privacy probes |
| Calendars | Custom months/weekdays/simple leap cycle, world-date arithmetic, event assignment/removal and calendar chronology | Round-trip/leap/invalid-date probes; browser leap-day assignment and removal |
| Publishing | Explicit existing-novel adoption, retained baseline/releases, finished-draft publishing, public synopsis, stable chapter URLs with separate reading order, legacy prose guards | Retry/adoption/controller probes; browser release and public reader verification |
| Editorial | Editor capabilities, scoped expiring grants/revocation, notes/chapter proposals, side-by-side review, guarded author acceptance/dismissal | Ownership/revocation/divergence probes; browser author acceptance and revoke |
| Compile | Selected working/finished writing or summaries to Markdown/PDF; fixed finished-draft review ZIP with optional pinned world/maps and inclusion coverage | Sandboxed Chrome PDF rendered; browser PDF/ZIP preparation; packet privacy/pinning checks |
| Assistant handoff | Optional author-enabled local read-only MCP access to one expiring review packet; Codex/Claude configuration; live revocation and chunked catalogue/records | Actual STDIO initialise/discovery/read, revocation rejection, parsed client configuration checks |

See [new workflow details](STUDIO_NEW_WORKFLOWS.md),
[review-packet/client setup](STUDIO_REVIEW_HANDOFF.md) and the updated
[compile review charter](manual-testing/manuscript-compile.md).

## What still needs Tapuwa

1. Play through the studio with real Dawn of the Moon material and critique the
   tools, density and navigation. Engineering verification used synthetic fixtures;
   no invented material was added to Dawn of the Moon.
2. Review real PC/tablet typing, IME, long sessions, interruption/session expiry and
   device-local recovery. Browser viewport checks do not prove hardware behaviour.
3. If wanted, explicitly enable a real review packet and add its connection to
   your chosen local Codex/Claude client. Neither account/client has been changed.
   A remote web-account connector is not implemented; downloads work across devices.
4. Review the completed code audit and candidate. Then approve commit/merge/release.
   Anubis still needs runtime/upload/private-storage/backup/migration rehearsal;
   the studio has not been deployed there.

## Broader roadmap refinements, not claimed complete

- Recognition already works in reference preview; inline highlighting inside the
  raw Markdown textarea and a richer word-level diff still need editor work.
- Specialized language/ecology/belief tools should be shaped against real material;
  typed entries, investigations, comparisons and alignment are the current tools.
- Geography is a flat map/image workspace. Globe/planet geometry, terrain-aware
  travel, native Wonderdraft editing and GIS exchange are future increments.
- Calendars use a simple periodic leap rule and positive years. Complex leap
  exceptions, multiple eras and calendar conversion are not implemented.
- Knowledge records capture stated perspectives, not a full evolving belief history.
  Editorial proposals operate on chapters, not simultaneous collaboration or
  word-level tracked changes. Public release rollback has no dedicated UI.
- Remote account OAuth/HTTP MCP access needs a separate authenticated connector.
  No automatic inference, canon changes or paid model API workflow was added.

## Database additions in this completion increment

All applied to the existing local MySQL database; no fresh/reset or production
mutation. The earlier studio migrations remain in place.

| Migration suffix (2026_10_26) | Change |
| --- | --- |
| 000004_add_draft_restoration_provenance | Draft restored-from provenance |
| 000005_create_world_maps | Private maps and immutable map versions |
| 000006_add_studio_publishing | Manuscript/public-book and chapter links, reader order, retained releases |
| 000007_create_editorial_review | Draft review grants, comments/proposals and review permissions |
| 000008_create_world_calendars | Calendar definitions and event calendar/ordinal links |
| 000009_add_review_packet_access | Scoped assistant secret hash and revocation timestamp on private compiles |
| 000010_create_world_reasoning_tools | Dependency flag, investigations and knowledge/reveal records |

During local fix mode, the new reasoning migration’s overlong index name was
shortened and missing archive-actor fields added. The running database was brought
into line without resetting data; that undeployed original migration now describes
the resulting schema. Existing production migration history was not rewritten.

## Verification and attention

No automated tests were added or changed, as required. Actual code execution used
rolled-back scratch probes and exact synthetic fixtures; syntax, Composer metadata,
focused React lint, production asset build and compiled Blade checks also passed.
Design scan: the new undocumented muted colour was replaced by the existing
studio token. No new suppressions; no findings left in the scanned touched screens.
The design sidecar is newer than DESIGN.md and was preserved.

The local server now runs as the transient user service dreamers-studio.service,
with the same local database/session and Node 22 for Browsershot. It is local-only
at 127.0.0.1:8000 and is not enabled at boot. Stop it with
`systemctl --user stop dreamers-studio.service` before starting a replacement server.
`bin/serve-studio` remains the direct development launcher. Chrome’s sandbox stays on.

Cleanup complete: exact synthetic world 35, manuscript 26, local public book 6,
reviewer 53/grant 3, calendar/event, map versions/images and derived compile files
were removed with ownership/title/count guards. Real world 1 and manuscript 1
writing hashes remained unchanged. The browser is back on Dawn of the Moon.
The saved visual proof uses synthetic review material and is not canon.
