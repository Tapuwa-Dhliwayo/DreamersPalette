# Author dashboard and manuscript writing activity

Local implementation: 2026/11/06. No production deployment.

The dashboard now combines recent manuscripts/poems/worlds, active manuscript word and chapter counts, open editorial suggestions, finished drafts available for release review, release/draft milestones, and published/unpublished library totals. Resume links open a manuscript's most recently modified chapter. Published poems remain available for revision.

## Writing measurements

`studio_chapters.word_count` is maintained when chapter Markdown changes. The count includes Unicode words and numbers; Markdown link destinations and HTML tags are excluded. Only active working chapters contribute to the current manuscript size. Private summaries, title changes and immutable draft copies do not contribute.

`studio_writing_activity` records successful prose saves in the same transaction as the chapter save. It stores additions, removals and resulting manuscript size, without copying prose. Counts compare word frequencies between successive saved versions; moving the same words within a chapter is excluded. Pasted and assisted prose count when saved. This measures saved word changes, not keystrokes, time spent, authorship or a full textual diff. Replacing equal numbers of different words records both additions and removals. Punctuation-only edits can count as an active editing day with zero added/removed words.

Existing request UUID/revision checks happen before recording activity, so retried saves do not count twice. Restoration, chapter archiving/restoration and accepted editorial replacements record size snapshots separately, without counting as new author writing. Finishing or undoing a finish does not clone activity or inflate manuscript size.

The additive migration backfills current chapter counts and establishes a size baseline. It does not infer past activity. Dates before tracking display **Not recorded**. New manuscripts establish a baseline with their first activity. Daily figures use the browser's validated IANA timezone; weeks begin on Monday. Today/week/month totals include only recorded activity, even when their calendar period began before tracking.

## Controls and access

The author can filter activity to one manuscript or all manuscripts and select 7, 30 or 90 days. The chart switches between daily additions/removals and manuscript size. Daily figures are available as text. A selected manuscript also shows chapter counts and an optional weekly words-added goal. Clearing the goal field removes it.

Dashboard data is scoped to the signed-in author's active manuscripts and existing resource permissions. Goal updates require the manuscript update policy and server validation. No user, blog, reader or permission schema is changed. The migration uses the existing publishing migration ledger.

## Verification

Production asset build, focused frontend lint, focused Pint, compiled Blade and whitespace checks passed. A rollback-only local execution verified word counts, Unicode/Markdown handling, two prose saves producing 5 added/1 removed/4 net words, retry deduplication, private-summary exclusion and unknown history before the baseline. Existing real writing was not changed by that check. Browser review covered the authenticated dashboard and populated manuscript overview; the historical chart will populate from future saved writing.
