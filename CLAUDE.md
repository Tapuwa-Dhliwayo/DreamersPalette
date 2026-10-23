# Claude Code Project Instructions

@AGENTS.md

## Claude Code-specific behaviour

`AGENTS.md` is the primary engineering instruction source for this repository. Apply it as project guidance.

Before substantial work, inspect `docs/agent/PROJECT_CONTEXT.md` when present and read the topic guide relevant to the task.

Do not rewrite `AGENTS.md` or the shared agent guides merely to record temporary task observations.

Use Claude auto-memory for repository-specific debugging discoveries and temporary operational knowledge where appropriate. Stable engineering preferences belong in the checked-in instruction files.

When a task materially changes architecture, database design, financial behaviour, permissions, or historical data semantics, surface the concern before implementing a conflicting design.

For normal implementation work, make reasonable local decisions and proceed without requesting approval for trivial cleanup.
