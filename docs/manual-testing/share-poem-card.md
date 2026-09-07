# Visitor shares a poem card

## Risk level

Medium — the workflow does not change poem content, but browser and app sharing support varies and an incorrect publication date would become part of a public image.

## Goal and stop condition

Confirm that a visitor can create and share or download a legible 1080 × 1920 poem card containing the correct title and first-publication date, with the public poem link accompanying the image where supported.

The session ends when the intended mobile and desktop paths have completed, the fallback paths have been exercised, the stored publication date has been inspected, and findings have been recorded.

## Accounts and roles

No account is required. Run the workflow as a signed-out visitor. A signed-in author may be used for the publication-date preservation check.

## Test data

- One published poem with a short title and a collection background.
- One published poem with a title long enough to wrap across at least three lines.
- One published poem whose collection has no custom background.
- One draft poem that can be published, unpublished, and republished.

Record each poem slug and its expected `published_at` value before starting.

## Fixture reset

Return the draft poem to its starting publication state through the dashboard. Do not change `published_at`; preserving it is part of the behaviour under test.

## Starting state

1. Apply the shareable poem-card section in `docs/DatabaseUpdatesHistory.sql` to the target Supabase database before deploying the application code.
2. Open a published poem URL in a mobile browser as a signed-out visitor.
3. Confirm the poem and its **Share poem** control are visible.

## Data shape review

Run this read-only query before clicking:

```sql
select id, title, slug, is_published, created_at, published_at
from public.poems
where slug in ('replace-with-test-slug');
```

Confirm every published test poem has a non-null `published_at`, and note whether an older poem was backfilled from `created_at`.

## Intended workflow

1. Select **Share poem** on a mobile device. The control becomes disabled and reads **Preparing image…** while work is in progress.
2. Confirm the device share sheet opens with a PNG file. Cancelling the sheet should return to the poem without an error.
3. Repeat and choose WhatsApp. Confirm the portrait image is attached and the poem title and URL are available in the accompanying message.
4. Repeat and choose Instagram. Confirm the image can be handed to the Story flow; note that Instagram may omit accompanying link text.
5. Save or inspect the image. It must be 1080 × 1920, use the collection background or atmospheric fallback, and show only the poem title, `Published · yyyy/mm/dd`, and Dreamer’s Palette attribution.
6. Open the same poem on desktop and select **Share poem**. Where file sharing is unavailable, confirm the PNG downloads and the status explains whether the link was copied.
7. Repeat with the long title. Confirm no word or line leaves the canvas and the attribution remains unobstructed.
8. Repeat with the collection that has no custom background. Confirm the atmospheric fallback remains clean and legible.
9. Publish the draft poem and record `published_at`. Unpublish and republish it, then confirm the original timestamp is unchanged.

## Risk map for probing

- Collection images whose storage response does not permit cross-origin use.
- Very long titles or a single unusually long word.
- Browser cancellation being mistaken for a failure.
- Mobile browsers that expose `navigator.share` but reject file payloads.
- Clipboard access being unavailable on a desktop fallback.
- A poem route deployed before the `published_at` database update.
- Repeated taps while image generation is in progress.

## State verification

After republishing the draft poem, run:

```sql
select title, is_published, published_at
from public.poems
where slug = 'replace-with-draft-slug';
```

The poem must be published and `published_at` must match the timestamp captured after its first publication.

## Findings

| ID | Phase | Location | Expected result | Actual result | Severity | Status |
| -- | ----- | -------- | --------------- | ------------- | -------- | ------ |
| — | — | — | — | — | — | — |
