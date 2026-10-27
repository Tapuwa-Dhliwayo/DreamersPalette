# Fixed review packets and assistant access

Updated 2026/10/26. Local dev only; no account configuration was changed.

## Author workflow

In a manuscript, open Compile, choose a finished draft and select chapters. Choose
Review packet, then explicitly include writing, summaries and/or the captured
world. Prepare export downloads a ZIP containing README.md, manuscript.md,
review.json and available captured map images. This is suitable for attaching to
your chosen assistant client. No model inference API call is made by this workflow.

The world is the draft’s captured world, including its exact magic-system versions,
not today’s live world. Chapter selection restricts passage references; excluding
writing also excludes passage anchors. Knowledge reveal links carry chapter metadata,
not hidden chapter prose. World data itself can contain authored story details;
including the world deliberately shares those details.

Missing map images or images exceeding the ZIP's total 50 MiB budget are listed as
omissions. Blank maps retain dimensions, scale, pins and journeys as structured data.
Exports expire after 24 hours. Download links additionally require an authenticated
owner, current export permission and a valid 30-minute signature.

## Optional local MCP connection

On a ready review packet, Enable assistant access generates a secret scoped to
that packet. The server only reads the fixed manifest and captured map images.
It cannot save writing, alter canon, discover other projects or read arbitrary files.
The connection expires with the packet; Revoke assistant access stops further reads,
including calls from a running client. Enabling again replaces the secret.

The screen supplies Codex TOML and Claude JSON. Treat them as secrets and keep them
in client-private settings. Do not commit them. The server command is the local PHP
binary with the absolute project artisan path and arguments `mcp:start dreamers-review`.
Its two environment variables are DREAMERS_REVIEW_ID and DREAMERS_REVIEW_TOKEN.
The database stores the token hash only. No client settings are written by the app.

Codex: add a STDIO server in MCP settings using the displayed command, arguments
and environment, or merge the displayed TOML into its private configuration.
Claude Code: the JSON describes a local STDIO server; use the client’s MCP import
or private configuration support. Follow the current official client documentation:
[Codex MCP](https://learn.chatgpt.com/docs/extend/mcp?surface=cli) and
[Claude Code MCP](https://code.claude.com/docs/en/mcp).

This increment connects clients running on the computer that can execute this
Laravel checkout. It does not expose an HTTP endpoint, authenticate a remote
ChatGPT/Claude web connector, or provide account OAuth. For other devices or a
server-only deployment, use the downloaded packet. A future remote connector needs
its own HTTPS authentication and author consent; it is not silently enabled here.
Your assistant account’s ordinary usage limits still apply.

## Retrieval and verification

The single `read-review-packet` tool is annotated read-only. Begin with
`section=manifest`; follow next_offset until null to enumerate the catalogue
(60 records per page). Then read chapter or world records by the advertised section
and ID. Record chunks use Unicode character offsets, default 12,000, maximum
16,000 characters. Manifest offsets count catalogue records; its response names
that unit explicitly. `map_image` reads a captured map ID, maximum 12 MiB.

Every read checks token, expiry, revocation, active manuscript, verified author and
current export/world capabilities. The long-lived STDIO process reloads its local
permission map. Downloaded packets and text already read by a client cannot be
recalled by revocation.

Engineering checks passed: real STDIO initialise/discovery/read; unknown-record
rejection; live revocation; equivalent parsed Codex TOML/Claude JSON; explicit
summary exclusion; pinned magic definitions after live edits; world metadata did
not leak excluded prose. Browser preparation reached Export ready. No model call
or external account connection was made during these checks.
