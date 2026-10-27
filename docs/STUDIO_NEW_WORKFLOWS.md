# Studio completion increment

Updated 2026/10/26. Implemented locally on dev. No production mutation or deployment.

## Draft recovery

Details edits title/description with a manuscript revision check. A locked draft’s
Restore opens a confirmation showing the source and current generation. Restoration
first locks current saved writing into a recovery checkpoint, then starts another
working generation from the selected draft. Existing snapshots stay immutable.
Later working-only chapters are archived; archived source chapters are recovered.
Retries reuse the request UUID. Open stale editor buffers cannot overwrite the new
generation. Restoration does not roll the shared live world backwards.

## Geography

Maps start blank or from private JPG/PNG/WebP uploads, including image exports from
Wonderdraft. Link pins to existing location entries or name them independently.
Drag, keyboard arrows or numeric coordinates position pins. Set the map’s physical
width to calibrate straight-line journey segments; absent scale means unknown
distance. Ordered stops support return visits. Each save and archive transition
retains an immutable version; old images are kept for versions and finished drafts.
This is flat geometry, without terrain, globe curvature or native Wonderdraft editing.

The local `bin/serve-studio` runs the usual PHP development router from public/
with 16M upload and 20M request limits. Application image validation remains 12 MiB.
Production PHP/FPM and reverse-proxy upload limits need corresponding release setup.
Back up private studio-maps storage with the database; public storage links do not
make these images public.

## Deliberate publishing

Bring in existing novel copies owned public chapters into a private manuscript,
including archived chapters. It retains a baseline release and changes no reader
writing. Further prose editing then belongs to the studio, including while its
manuscript is archived. The legacy form remains available for themes.

Publishing selects a finished draft, previews its chapters, asks for an explicit
public synopsis and requires confirmation. It publishes locked prose only.
Summaries, private manuscript descriptions and world records are excluded. Existing
chapter_number values remain stable URL identifiers; reading_position expresses
new chapter order. Omitted public chapters become unpublished. A release retains
its book/chapter copy; repeated requests do not duplicate it. Theme image URLs are
captured, but remote image bytes are not archived. Release rollback UI is not part
of this increment; republish a chosen finished draft deliberately.

## Editorial review

The editor role grants review capabilities, not ownership of another author’s
studio. Authors grant an existing verified review-enabled account one locked draft
for 1–90 days, optionally allowing proposals, and may revoke it. No invitation
message is sent. Reviewers see the locked prose, not the live manuscript/world.
They add notes or propose whole-chapter Markdown separately from writing.

Authors compare reviewed/proposed text and apply or dismiss a proposal. Apply
requires the current chapter still to match the reviewed text and the manuscript
revision to match. A divergent chapter needs manual reconciliation; it is never
replaced automatically. Accepted edits affect working writing; locked prose stays
unchanged. This is chapter proposals with side-by-side reading, not word-level
tracked changes or simultaneous co-editing.

## Calendars and reasoning

Calendars define ordered named months, optional weekdays and a simple every-N-years
leap cycle. Years begin at 1; no year zero/BCE or leap exceptions. Events store an
ordinal day and calendar ID. Calendar assignments can be removed; freeform timeline labels are
editable again afterwards. Calendar chronology sorts actual world dates;
the general timeline’s manual position remains separate. Changing calendar rules
changes live labels, with a visible warning; finished drafts retain definitions
and rendered date labels. Archiving a calendar preserves linked event labels.

Investigations link world subjects to a question, sourced supporting evidence,
counter-evidence, open questions and a conclusion. Resolving requires a conclusion.
Knowledge separates author truth from each subject’s belief, optional learned-at
event, and optional reader reveal chapter in a manuscript linked to the world.
Reveal order follows that manuscript’s current chapter positions. Missing
perspectives mean unrecorded, not ignorance. A perspective records one stated
understanding; a full history of evolving beliefs is future work. Both tools have
revision checks and archive/restore and are captured into finished world context.

A relationship can explicitly mark From depends on To. Changing To shows direct
and transitive dependents and a circular-dependency warning. This traverses all
unarchived dependency links, including different authored periods; it is a review
aid, not a chronological causality solver. Results cap at 2,000 affected subjects
with a visible truncation notice. It never edits downstream records automatically.

## Engineering evidence and remaining review

Transactional scratch execution passed restoration/retry/recovery checks, map
geometry/versioning, adoption/release idempotency and privacy, actual legacy
controller guards, editorial scope/revocation/divergence, calendar round trips and
invalid dates, dependency chains/cycles, investigation evidence, knowledge scope,
and fixed assistant packet boundaries. These are disposable execution probes;
no automated tests were added or changed.

Browser checks passed restoration and details, private image import and retained
map selection, keyboard movement, investigation/knowledge saves, leap-day event
chronology, author suggestion acceptance/revocation, finished-draft release,
public reader prose and review-packet preparation. Physical tablet/IME/session
recovery and real-account client setup remain the author’s final review. See
STUDIO_REMAINING_WORK.md for the authoritative release gate and broader refinements.
