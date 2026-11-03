# Manuscript autosave and device recovery

Updated 2026/11/03. Allow 30–45 minutes on the local Anubis copy. No database
reset. Keep real writing and finished drafts; deliberate local edits are disposable.

## Shape and findings

- Server-rendered initial chapter data changed on each save. Replacing the root's
  Alpine attributes reinitialised its buffer and sequence, causing duplicate saves
  and possible selection of older text. The editor root now uses wire:ignore.self;
  server-rendered children still update and explicit chapter/draft loads still run.
- Old autosave used a 1.5-second debounce plus a 15-second interval. Background
  saves now run every five minutes and send one snapshot per tick. Save, Ctrl/Cmd+S
  and workflow transitions still explicitly flush pending writing.
- Device recovery stores current writing immediately, its base server revision,
  the pending request identifier and a per-editor writer identifier. Matching-base
  local writing is restored automatically. A recognised accepted request also
  allows recovery after a lost acknowledgement. Unrelated server changes retain
  the explicit recovery choice. Unresolved recovery/conflict text is read-only.
- A server acknowledgement only clears device storage owned by that editor;
  typing after a request remains dirty and checkpointed at the accepted revision.
  No schema or production changes. Server revision/draft guards remain enforced.

## Intended workflow

1. Paste several paragraphs from Word, then quickly edit the beginning and end.
   Expect immediate device recovery storage and no server save after a brief pause.
2. Keep typing across a five-minute boundary. Expect one background save; typing
   after that request stays in the editor and awaits the next timed/manual save.
3. Press Save or Ctrl/Cmd+S. Expect the latest title, prose and summary saved. Reload
   and confirm exact content, including intentional empty text and Unicode.
4. Edit without saving, then reload. When the server revision is unchanged, expect
   the newer local writing to return automatically, marked as awaiting server save.
5. Switch chapters, open the world linker and finish a draft with pending edits.
   Expect those deliberate actions to save before proceeding. Check finished text
   includes server-saved writing; do not finish a real draft just for this check.

## Probe risks

Highest: paste/edit while a slow save is in flight, or lose the save response.
Confirm local typing never reverts, manual retry is idempotent, and a reload can
recognise its own accepted request. Network failure must retain the current buffer.

Two tabs: save distinct text in the other tab, then reload the first with unsaved
local work. Expect an explicit comparison/choice, never a silent overwrite. A save
acknowledgement in one tab must not delete the other tab's device checkpoint.

Navigation and errors: expired login, browser storage failure, oversize paste,
intentional empty chapter, archive/restore and finish in another tab. Inspect save
status and recovery/download choices; no queued save may enter the wrong draft.

## Read-only verification

After saving, inspect studio_chapters content_md, summary_md, revision and
last_save_uuid for the selected chapter, plus its manuscript revision. After an
ordinary single timed save, expect one revision increment, not duplicates. Ensure
finished drafts and published chapter rows have not changed from ordinary saves.

## Agent verification

Actual local browser/Livewire/MySQL probes used an existing empty chapter with a
private before-state snapshot. Pasted text plus rapid typing made no automatic
request after two seconds. The registered 300000ms timer callback was invoked to
exercise a timed save without waiting five minutes. A delayed request preserved
newer typing, dirty state and the device copy; reload restored it without a chooser.
Manual save and simulated lost-acknowledgement recovery saved the exact latest text.
After the root fix, exactly the three intended writes occurred; earlier duplicate
writes were reproduced and eliminated. No browser script errors were observed.
The probe prose and both chapter/manuscript revision changes were restored exactly.
No automated tests were added. Real Word clipboard formatting and the complete
five-minute elapsed-time workflow remain for author retest.
