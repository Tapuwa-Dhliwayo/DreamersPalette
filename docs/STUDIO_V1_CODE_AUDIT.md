# Writing Studio v1 code audit

Reviewed 2026/10/27 on dev. This is a local release candidate, not a deployed release. Existing publishing is already live; v1 here means the first Writing Studio release.

## Findings and corrections

| Finding | Correction |
| --- | --- |
| Dashboard mixed private authoring and publishing, with a permanently highlighted studio link | Dashboard first; Writing contains Manuscripts, Worlds and Editorial reviews; Publishing contains Poems, Collections, Novels and Chapters; account and Trash below. Only the current route is highlighted. |
| Mobile navigation appeared behind the page | Raise the open navigation above the content; make underlying content inert; focus the close control, contain Tab navigation and restore focus on close. |
| Editor-only dashboard attempted publishing requests without publishing capabilities | Gate each request and its links by the relevant permission. Reuse authenticated profile state instead of fetching it again in the sidebar. |
| Adopted public books still invited editing through the old prose editor | Expose narrowly scoped management metadata and link authors into their manuscript. Preserve public cover editing; block legacy prose mutation. Recovered browser writing remains available to copy. |
| Eager manuscript relations risked being returned in legacy save responses | Load only selected relationship fields for read listings; remove private relations before serialisation. Management access to a public book does not reveal another author's manuscript ID. |
| An old successful save retried after finishing a draft could acknowledge the next generation | Validate the active draft before accepting an idempotent retry. Old-generation requests are rejected rather than silently advancing the browser. |
| A live site could offer a local STDIO assistant command that cannot run on the user's PC | Limit local MCP enablement to the local environment. Production retains downloadable review packets. Remote account connectors are outside this release. |

## Architecture review

The release keeps private manuscripts separate from public chapter rows. Finished drafts preserve prose; author-approved releases copy content into publishing. Stable public chapter numbers are separate from reading order. Editing permissions and model policies apply on the server, including review grants and private exports; an administrator's publishing access does not imply access to private manuscripts.

Entity behaviour remains on models. Draft coordination, publishing and compilation justify cohesive cross-model services. Forms own routine input validation. Archiving and explicit application deletion rules preserve recoverable records without database cascades. There is no new repository or per-action abstraction layer.

The development workspace is substantial and its forms cover several tools. Splitting it solely by size would add coordination costs; future changes should extract cohesive behaviour when repeated, rather than introduce a generic framework. Reasoning records use bounded structured fields for v1. They are not a general graph query engine.

## Verification

- PHP syntax checks; focused Pint and ESLint; production frontend build; Blade compilation; Composer validation; whitespace checks.
- Rolled-back application probe: book/chapter handoff metadata, private-relation exclusion, public manager exclusion from private manuscript IDs, cover/theme save response privacy, rejection of an old-generation save retry, and disabled MCP action rejection.
- Authenticated dashboard browser review at desktop and narrow viewport, including navigation ordering and menu behaviour. No real writing was edited.
- Earlier feature verification is recorded in STUDIO_REMAINING_WORK.md and the individual workflow charters. This audit does not claim every historical browser check was repeated.

## Deviations and attention

No automated tests were added or extended, following the project instruction. Runtime probes are transactional, not a permanent regression suite. This is not a penetration test or accessibility certification. A sustained physical tablet/phone writing session remains useful feedback.

Tracked changes and new studio files remain uncommitted. No production changes were made. Anubis requires backup, upload-limit adjustment and validation of the exact sandboxed Chrome binary before deployment. Follow STUDIO_V1_RELEASE.md.

Deferred capabilities include rich word-level revision tracking, remote assistant connectors, specialised language/ecology tools, spherical geography and native Wonderdraft editing. The dashboard's Continue writing list still represents publishing drafts; it is not yet a combined private manuscript activity feed.

### Subsequent server preparation

On 2026/10/27, Tapuwa authorised Anubis preparation. Upload limits and the scheduler
are configured, and sandboxed Chrome rendering passed through a dedicated site
PHP-FPM worker. The existing site remains healthy. See STUDIO_V1_RELEASE.md for
applied settings and remaining deployment checks; the earlier statement that no
production changes were made describes the audit phase, not this subsequent task.
