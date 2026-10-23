# Tapuwa Agent Guide

This directory expands the reusable engineering instructions in the repository root `AGENTS.md`.

## Instruction source

`AGENTS.md` is the shared primary source of truth.

`CLAUDE.md` imports `AGENTS.md` so Claude Code and Codex receive the same core engineering philosophy.

## Topic guides

```text
PROJECT_CONTEXT.md      Per-repository context and risk/testing policy
ARCHITECTURE.md         Rich models, services, third-use rule, legacy boundaries
LARAVEL_LIVEWIRE.md     Livewire Form Objects, Flux, controllers, policies
REACT_SUPABASE.md       React service domains, Supabase, Hooks, Laravel API boundaries
DATABASE.md             Table design, lookups, migrations, indexes, no cascades
UI_STYLE.md             Tapuwa's visual and responsive design rules
TESTING_WORKFLOW.md     No automated tests, verification without them, agent autonomy/refactoring rules
MANUAL_TESTING.md       Manual testing charters Tapuwa executes by hand
```

## Reusing in a new repository

Copy:

```text
AGENTS.md
CLAUDE.md
docs/agent/
```

Then edit:

```text
docs/agent/PROJECT_CONTEXT.md
```

Do not rewrite the shared style files for ordinary project-specific architecture.

Put project-specific decisions in `PROJECT_CONTEXT.md` or the repository's architecture documentation.

## Codex

Codex reads `AGENTS.md` automatically.

The root file instructs the agent to read the relevant topic guide before substantial work.

For a persistent personal default across repositories, a curated version of the shared core may also live at:

```text
~/.codex/AGENTS.md
```

Repository guidance should still describe project-specific context.

## Claude Code

Claude Code reads `CLAUDE.md`.

The provided `CLAUDE.md` imports the shared `AGENTS.md`, avoiding two copies of Tapuwa's engineering philosophy that can drift apart.

Project-specific Claude-only instructions may be added to `CLAUDE.md` only when they genuinely apply to Claude Code behaviour rather than engineering style.

## Keeping the guide useful

Update these instructions when Tapuwa changes a stable working preference.

Do not record one-off task decisions as global style.

Do not let the guide become a dump of every architecture decision from every project.
