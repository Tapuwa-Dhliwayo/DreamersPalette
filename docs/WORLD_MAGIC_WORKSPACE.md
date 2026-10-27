# World and magic-system workspace

Updated: 2026/10/25. Implemented locally on dev for Tapuwa to explore. No Anubis deployment.

## Open and use

Open `/studio/worlds`, then Dawn of the Moon. The initial Focus system is empty on purpose: no rules, aliases, cases or invented lore were seeded.

- **Edit world system** saves the system name, Markdown notes and aliases (one per line).
- **Rules** stores statements, conditions, consequences, exceptions and reasoning. A decision can be Exploring, Settled or Discarded. Only title and statement are mandatory; details are progressive.
- **Cases** stores a situation, optional outcome/reasoning and explicit rule links. Cases without links are visibly identified. Linked rules open the relevant rule, scroll to it and receive focus.
- **Link manuscript** attaches one of the author’s unlinked private manuscripts to this world. Several manuscripts can share a world. Existing links cannot be silently reassigned to another world.
- **Scope** switches between shared world rules and a linked manuscript. Adjust a rule as a qualification, alternate rule or not applicable; include the reason. Use world rule unchanged removes only the current adjustment.
- World rule changes flag existing adjustments. Review source compares the original source and current rule; marking reviewed updates the basis without changing the manuscript’s adjustment.
- **History** opens immutable saved world-system versions. Version links are scoped to their world/system. Closing the dialog clears the version query.

World forms use explicit Save. The manuscript editor retains its autosave. Edit dialogs prevent background/Escape dismissal, and world editing warns on leaving with unsaved input. Cancel deliberately discards the current form.

## Persistence and access

Migration: `2026_10_25_000002_create_world_magic_workspace.php`.

Nine new tables: `studio_worlds`, `studio_magic_decisions`, `studio_magic_variation_modes`, `studio_magic_systems`, `studio_magic_rules`, `studio_magic_cases`, `studio_magic_case_rules`, `studio_magic_variations`, `studio_magic_versions`.

Adds optional indexed `world_id` to manuscripts and `world_context` JSON to finished drafts. Lookup rows represent changeable decisions and adjustment modes; no database enums, foreign-key constraints or cascade deletes.

`worlds.view/create/update` are granted to existing author/admin/superadmin roles. WorldPolicy requires author ownership; role alone does not expose another author’s worlds. Every mutation authorises its world, and manuscript operations also authorise the manuscript. Form Objects own validation; linked rule IDs are validated inside the selected system.

World writes lock the system, compare its revision and save an immutable full version of notes/aliases/rules/cases. Manuscript adjustments lock manuscript then system, compare draft/manuscript/system/variation revisions, and preserve the original source rule until explicit review. No new repository, generic entry framework or action layer.

Finishing a manuscript draft pins system version IDs and copies its current adjustments into the draft’s world context. Draft comparison exposes those saved world versions and adjustment copies. Later world changes do not rewrite finished context. Older finished drafts have no retroactively fabricated world context.

## Verification performed

- Actual browser creation of a disposable world/system, Markdown notes and alias, rule/conditions/consequences, linked case, manuscript link, qualification, source-change comparison/confirmation and historical version reading.
- Transactional local execution: ownership policy, stale world write rejection, cross-system rule rejection, preservation of manuscript variations, explicit source review, immutable versions, finished-draft context surviving later world edits, and resetting a working adjustment to inheritance.
- Probes rolled back; browser verification records removed by exact IDs/names. Only the empty Dawn of the Moon/Focus starting records remain; existing author writing was not changed.
- PHP style, focused JavaScript lint, whitespace and production build checks. No automated tests added.
- Desktop/narrow browser layout reviewed; actual tablet keyboard/IME remains for Tapuwa’s play session.

## Current boundaries

This is the first magic-system slice, not the complete encyclopedia. General characters/places/species, selected-text creation, automatic alias highlighting, graphs/alignment/comparison tools, formal historical/regional/belief structures and assistant access remain future work. Costs/capabilities can currently be authored as rules and Markdown rather than separate specialised records.

Cases are shared world material; manuscript-specific scope currently adjusts rules. World/system archive/delete, world renaming, restore-to-old-world-version and per-keystroke world recovery are not provided yet. History is read-only. Rule discard is explicit decision state, not deletion. Search covers system names and aliases; it is not a full encyclopedia search engine.

No simulation or literary consistency proof is claimed. Connections and unlinked cases expose the reasoning the author has recorded.

Use [the focused charter](manual-testing/world-magic-development.md) while exploring, then collect feedback before expanding or restructuring the interface. Production release remains deferred until the broader studio is ready.
