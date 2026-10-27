# Charter: compile a private manuscript

2026/10/26 · 30–45 minutes · local review.

Mission: export selected writing for a discussion without exposing summaries
unintentionally or changing a finished draft. Use a disposable owned manuscript
with two chapters, summaries and a finished draft. Preserve real writing. No reset.

## Phase 0 — shape review

Read the compile manifest before clicking. It must contain complete chapter text,
source label, capture timestamp, ordered chapter IDs/positions and the selected
inclusion flags. A download must not look up fresh prose. Finished-draft chapter
IDs belong to the snapshot, not the working chapter table. Check that output paths
are private and chapter IDs are scoped to the chosen source. Review the 24-hour
lifetime and synchronous size bounds in MANUSCRIPT_COMPILE.md.

## Phase 1 — intended workflow

1. Change a chapter in Write and choose Compile immediately. Expect saved writing
   in the chapter preview, or a save conflict that keeps you in the editor.
2. Select only chapter two, keep Writing on and summaries off, prepare Markdown
   and download. Expect that chapter's original Markdown and no summary text.
3. Include summaries, prepare again, and inspect the new download. Expect the
   selected summary, separate from prose. The previous copy must remain unchanged.
4. Choose the finished draft. Expect its chapter list and preview. Prepare PDF;
   download it and inspect the title, chapter breaks, Unicode, emphasis and page
   numbers. Expect the locked draft's prose even if current writing differs.
5. Choose summaries only. Expect an outline with titles and summaries. Then turn
   both inclusion options off and prepare: expect a useful validation message.
6. Choose None and prepare: expect a chapter-selection error. Select All and
   prepare again: expect chapters in manuscript order, without duplicate rows.
7. Reopen Compile. Expect the saved format/inclusion preferences and current
   working source. Recent outputs should be available without compiling again.

## Phase 2 — risk map / fix loop

Highest: another author's manuscript or a forged chapter/draft ID must not be
exportable. Removing export permission must stop a previously issued download.
Next: change saved working prose in a second tab after opening Compile. A fresh
preparation must reject the stale source; Refresh source should show new writing.
An already captured copy must retain its earlier contents. Probe expired/tampered
signed links, a cancelled finish, archived chapters/manuscripts, output retries,
large chapter groups, unusually long words, Markdown links/images and narrower
screens. PDF generation must keep the Chrome sandbox enabled. Record findings
and probes as the session proceeds; do not add automated tests.

## Phase 3 — verify

Read manifest chapter IDs, copied text, flags, source revision, creator, status,
private path and expiry. Compare the downloaded output with that manifest. Confirm
source manuscript and finished draft records did not change. An expired output
must return 410 even before cleanup; a tampered signature must be rejected.
The prune command should remove only expired derived files/manifests. Preferences
must remain on the manuscript after those exports expire.

## Deviations and attention

No automated tests by project instruction. This charter is the author's review;
engineering browser and scratch checks are recorded in MANUSCRIPT_COMPILE.md.
PDF preview is content-only, and embedded images are outside this increment.

## Review-packet extension

Risk: High, because author-selected private material is shared with a client.
Stop condition: the packet contains exactly the selected source material and a
revoked local connection cannot read again. Budget another 15 minutes if reviewing
this extension together with PDF; otherwise make it a separate session.

Starting state: use a disposable owned manuscript linked to a disposable world,
with two chapters, a private summary, one location/map, a magic definition and a
finished draft. Make later working/world edits distinguishable from that snapshot.
No reset is needed. Keep real writing, permissions and accounts intact.

Phase 0: inspect world_context, exact magic version IDs, chapter selection and
prose/summary flags. Reveal links must contain chapter metadata only. Local map
paths stay private; only the ZIP's map-relative filenames leave the application.
Review packet access stores only a secret hash, with the same expiry as the export.

Phase 1:

1. Choose the finished draft and only chapter two. Prepare Review packet with
   writing on, summaries off and captured world on. Expect Export ready and a ZIP.
2. Inspect README.md and review.json. Expect chapter two only, no private summary,
   earlier world/magic definitions, explicit coverage and map files or omissions.
3. Prepare world-only. Expect chapter metadata and captured world, without chapter
   prose, summaries, passage anchors or prose nested under reveal links.
4. Enable assistant access deliberately. Expect separate Codex/Claude configurations
   for the same packet, a secret and expiry. Use a private client setting; do not
   commit the configuration. No model API key is requested by Dreamer’s Palette.
5. Ask the chosen client for the manifest, then one advertised chapter/world record.
   Expect selected captured data only. Follow next_offset until null for all pages.
6. Revoke access and ask again in the running client. Expect denial. The downloaded
   ZIP still exists independently; revocation cannot recall a copy already shared.

Phase 2 risk map: forged packet/chapter/map IDs; another author's manuscript;
expiry while a client is running; removed export/world permission; very large
catalogues/chunks; missing images; retries; enabling twice; source cancellation;
working source selected for a review packet; excluding writing while world contains
reveal links. A local STDIO connection must not imply remote web connector support.

Phase 3 read-only verification (replace 123 with this session's compile ID):

```sql
SELECT id, manuscript_id, created_by, format, status, expires_at,
       assistant_revoked_at, assistant_token_hash IS NOT NULL AS access_enabled,
       JSON_LENGTH(manifest, '$.chapters') AS selected_chapters
FROM studio_compiles WHERE id = 123;
```

Expect one ready review row per preparation UUID, no plain secret, and null token
hash after revocation. Compare manifest flags and selected IDs with the ZIP, and
check the manuscript/draft prose remains unchanged. No invitation, email,
background inference job or external account configuration should be generated.

| ID | Phase | Location | Expected result | Actual result | Severity | Status |
| -- | ----- | -------- | --------------- | ------------- | -------- | ------ |
| COMPILE-01 | 0 | World::captureDraftContext | Reveal links exclude hidden prose | Narrowed relation to chapter metadata; exclusion probe passed | High | Fixed |
| COMPILE-02 | 0 | ManuscriptCompile::capture | Captured magic definitions available | Exact retained version hydrated; later live edit exclusion passed | High | Fixed |
| COMPILE-03 | 2 | ReviewPacketAccess | Running client observes revocation | Real tool and STDIO checks passed | High | Ready for retest |

Engineering checks ran on 2026/10/26; Tapuwa’s client/device session remains pending.
The agent records observations and findings during that session. Existing automated
tests were left untouched.
