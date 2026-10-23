# Author generates an image and recovers from failure

Risk: Medium — provider, authentication, storage, and database failures currently share a generic error message.

Goal: Verify a failed generation displays the server's explanation, clears the loading state, and preserves the editor's content. Stop after retrying the original prompt, checking both editors, and recording remaining failures. Allow 30 minutes.

## Accounts and test data

Use an authenticated author with an existing collection and book they own. Record their IDs. Use the original prompt:

> A surreal, melancholic painting of Black men and women caught in love, longing, jealousy, and heartbreak. Some embrace, others chase unrequited love, while neglected friends and families watch from the shadows.

Reset: No database reset. Reload the editor without saving to discard preview changes. Successful generations create stored assets even when the editor is not saved; leave those records intact during this session.

Starting state: Open `/dashboard/collections/{id}/edit`, with browser Network tools open. Use the frontend containing this fix. Repeat the editor checks at `/dashboard/books/{id}/edit`.

## Data shape review

Read the `generated_assets` definition and policies in `docs/IntialDatabaseCreationQuery.sql`. Each successful generation stores author, type, original prompt, and image URL. The server uploads before inserting the database record, so an insert failure may leave an uploaded file. Failure messages do not establish whether an upload occurred.

## Intended workflow

1. Enter the original prompt and generate. Observe the loading state and the `generate-asset` response.
2. If the response fails with JSON `{ error: "..." }`, expect that exact explanation in the editor instead of only `Edge Function returned a non-2xx status code`. A gateway `{ message: "..." }` response should also display its explanation. Record the response status and body without tokens.
3. Confirm loading clears, the prompt and unsaved fields remain, and the previous image is retained after failure.
4. Retry after resolving the specific failure. On success, expect an image preview. Save, reopen, and confirm the image persists.
5. Repeat the failure/recovery checks in the book editor, where the same image service is used.

## Risk map for probing

- Highest: A generic gateway response can still hide the provider cause; compare the UI with the response body and server logs.
- Expired session: Gateway or server authentication messages must remain visible and generation must stop.
- Empty or non-JSON error body: Preserve the original SDK error and clear loading.
- Network interruption: Keep the original network error; do not attempt to parse it as an HTTP response.
- Success regression: Returned image URL must still populate the correct preview.
- Background composition: The server appends `no people`, conflicting with this prompt. This issue remains separate from HTTP failure handling.

## Permission checks

Generation requires a verified Supabase user. A logged-out request must fail server-side. Editor ownership remains governed by existing collection/book access rules; this fix changes only displayed error handling.

## State verification

Read the author's `generated_assets` rows and storage objects around the request time. A successful generation should have an asset record with the correct type and URL. Saving the editor should persist that URL on the collection or book. For failures, use the response and function logs to locate the stage reached; do not assume no side effects occurred.

## Findings

| ID | Phase | Location | Expected result | Actual result | Severity | Status |
| --- | --- | --- | --- | --- | --- | --- |
| AI-1 | Fix loop | `src/services/aiAssetService.js:33` | Display server explanation | Original frontend displayed generic non-2xx message | Medium | Ready for retest |
| AI-2 | Fix loop | `supabase/functions/generate-asset/index.ts:88` | Prompt instructions agree | Background prompt appends `no people` | Medium | Documented |
| AI-3 | Fix loop | Deployed `generate-asset` response/logs | Generation succeeds | Underlying server failure not yet identified | Medium | Needs investigation |


## Laravel live verification — 2026/10/23

The preceding Supabase instructions are historical. Current requests use
`POST /api/studio/generated-assets`, Laravel session/CSRF authentication,
`generated-assets.create` permission and the GeneratedAsset policy. The
Hugging Face key is server-only. The official SDK runs in the fixed Node helper;
its standard output must contain only image bytes.

A real configured-provider request exposed SDK provider-selection logging mixed
into the image output. Laravel correctly rejected those contaminated bytes.
The helper now sends SDK diagnostics to stderr. After the fix, actual generation
returned a valid 1024×1024 PNG. A second real request through authenticated HTTP
returned 201, stored the cover record, and served all 1,197,626 image bytes.
The temporary record and public file were removed; original writing was untouched.
Node syntax and Git whitespace checks passed. No automated tests were added.

For the browser follow-through, use an owned collection or novel, generate a new
image, confirm the preview changes, save and reopen. On failure, confirm the
loading state clears and writing/previous image remain. Provider errors are
sanitized by Laravel; raw upstream responses and credentials are not UI content.
Browser editor interaction and save/reopen still require that manual pass.
