# Manuscript compile

Local increment, 2026/10/26. No production deployment.

The manuscript toolbar's **Compile** button checkpoints local writing through the
existing save guard before navigating. Compile can use current server-saved work
or a finished draft. Select chapters, choose Markdown or PDF, and independently
include prose and chapter summaries. At least one chapter and one content kind
are required. Chapters always export in manuscript order. Source refresh selects
all chapters again; choosing a finished draft uses its retained chapter IDs and
text, including chapters subsequently archived from the working manuscript.

The preview reads one chapter from the chosen source. It is a content preview,
not a representation of PDF pagination. PDF includes a title page, source label,
capture timestamp, page numbers and a new page per chapter. Images are omitted;
text and safe links are retained. Markdown preserves the original source and
includes summaries only on request. No publishing metadata is required.

## Source and storage

`2026_10_26_000003_create_manuscript_compiles.php` adds `studio_compiles` and a
nullable `studio_manuscripts.compile_preferences` JSON column. The preferences
retain format and prose/summary choices independently of temporary exports.

The compile model locks the manuscript briefly, checks the source revision and
chapter membership, and copies a complete ordered manifest. Generation happens
after that transaction. Later edits do not alter this copy. Repeated preparation
of the same request UUID reuses its manifest; changing options creates a new
request. An expired request must refresh its source. Source titles, chapter
positions, prose, summaries and revisions are copied into the manifest. No
finished draft is modified. This is a temporary export snapshot, not a new draft.

Files live on the **local private disk** under `studio-compiles/`. Downloads
require a valid signature, authenticated author ownership, current export
permission and an unexpired ready record. Signed links last 30 minutes and can be
renewed by reopening Compile; copies remain available for 24 hours. Recent exports
show the five latest available copies. Expiration is checked on every download.
`dreamers:prune-compiles` removes expired files and manifests; it is scheduled
daily and requires Laravel's normal scheduler in deployment. It never removes
manuscripts, finished drafts or their chapter records. Expiration does not remove
copies already downloaded by an author.

## Browsershot runtime

PDF uses **spatie/browsershot 5** and the project's **Puppeteer** dependency. Dompdf
was removed at Tapuwa's request. Browsershot uses regular Chrome's new headless
mode (`newHeadless()`), not Chrome Headless Shell. The latter was the cause of
the first sandbox/profile mismatch. The sandbox remains enabled.

Production needs Composer dependencies, production npm dependencies (Puppeteer
is not a dev dependency), a Puppeteer-managed Chrome installation accessible to
the PHP runtime account, its Linux runtime libraries, and a working Chrome
sandbox. The local runtime was verified with Node 22. Optional absolute paths are
`BROWSERSHOT_NODE_BINARY` (falls back to existing `NODE_BINARY`) and
`BROWSERSHOT_CHROME_PATH`. No production runtime was installed or changed.
See the [Browsershot project](https://github.com/spatie/browsershot) for platform
requirements, and verify the actual Forge runtime account/cache during deployment.

On this Ubuntu device, Tapuwa installed a narrow AppArmor profile for the exact
Puppeteer Chrome binary. The prepared file is
`storage/app/private/dreamers-browsershot.apparmor` (ignored, local only). A Chrome
upgrade that changes the executable path requires updating that profile. Do not
copy this device path onto Anubis. No global AppArmor toggle or sandbox-free mode
was added. Markdown is sanitised before PDF rendering; page scripts, remote HTTP
resources and images are disabled, and the document has a restrictive CSP.

Rendering is synchronous and bounded: PDF manifests up to 750,000 encoded bytes,
Markdown up to 12,000,000, with at most 1,000 chapters. Larger selections receive
an instruction to choose fewer chapters or Markdown. Queueing very large PDFs,
DOCX/EPUB, covers, embedded illustrations, worldbook appendices and publishing
remain later increments.

## Verification and attention

- Real browser: compile one finished-draft chapter with its opted-in summary,
  download Markdown and inspect the downloaded file; PDF preparation succeeds
  after the Chrome/profile fix, with the sandbox enabled.
- Rolled-back scratch execution: manuscript order, retry identity, stable capture,
  stale working-source rejection, immutable finished-draft prose/summaries,
  download content type, expired download rejection, foreign author and foreign
  chapter rejection. No writing or finished snapshots were changed.
- A 181-page, 588,073-byte manifest rendered in approximately 2.4 seconds locally.
  Unicode punctuation, accents, Greek text, italics and bold were inspected in
  extracted PDF text and a rendered page. This does not establish support for
  every script, font or full publishing layout.
- Build, PHP/compiled Blade syntax, formatting and design checks run locally.
  No automated tests were added, per repository instructions.
