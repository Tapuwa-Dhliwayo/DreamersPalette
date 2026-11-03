# Manuscript selection actions

Updated 2026/11/03. Local only; use the copied Anubis baseline.
Allow 30 minutes. No reset is needed. Any deliberate local writing or link changes
remain local; do not repeat these probes against production.

## Stored shape

The menu uses existing chapter-reference records, selected wording and surrounding
context, source chapter revision and active draft number. No schema changes.
Opening the menu, Assist or encyclopedia must not create a link or alter prose.
Link, alias and creation actions retain their existing authorisation boundaries.

## Intended workflow

1. Open Dawn of the Moon, highlight a name in a chapter. Expect a nearby menu
   offering Link to world, Assist, bold and italic; the passage stays selected.
2. Choose Link to world. Expect the exact selected wording in the encyclopedia.
   Search for an existing character, system or entry and link it. Expect the
   chapter-link list to show the chosen target and the passage as Linked.
3. Select an alternative name, choose the same target and Add as alias. Expect
   that name in the target's aliases. Check Preview recognises the new alias.
4. Select a new name and choose Create from selection. Complete the existing
   character/entry form. Expect one entry and a link to this chapter passage.
5. Select prose and choose Assist, or press Alt + Enter. Expect the existing
   tool choices with precisely that passage. Deliberately request a suggestion;
   applying it should affect only that passage and support normal undo.
6. Select a word and choose bold or italic. Expect Markdown markers around the
   selected text; undo, save and reload should behave normally.
7. Repeat selection using Shift + Arrow, in Split and Focus modes, and at phone
   width. Expect accessible controls within the viewport. Escape, clicking
   elsewhere, scrolling and switching chapters should dismiss the menu.

## Probe risks

- Highest: change chapters or finish a draft while another tab has pending work;
  existing recovery/conflict protections must still retain writing.
- Repeated wording, punctuation, multiline selections and Unicode: confirm the
  link points to the intended occurrence and keeps exact selected text.
- Long selections: Assist is unavailable beyond 1,000 UTF-16 units; linking is
  unavailable beyond 2,000 Unicode characters. Whitespace-only selection shows
  no menu. Read-only access must not expose editing actions.
- Phone selection handles and the on-screen keyboard require a real-device check;
  desktop mobile emulation does not reproduce those native controls.

## Verification recorded

Agent browser probes opened the real local manuscript: selected wording reached
its encyclopedia dialog; Assist opened; desktop and 375px mobile menu bounds
stayed on screen; no browser script errors were observed. Formatting/undo and
keyboard probes are recorded in the chat work report. No automated tests added.
The agent did not submit a new link, alias, world entry or external Assist request;
those deliberate workflow steps remain for author retest.
