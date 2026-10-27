# Develop a world magic system and vary it for a manuscript

Date: 2026/10/25. Timebox: 30–45 minutes. Risk: **High** — private lore, shared rules, concurrent changes and retained draft context.

## Goal and stop condition

Develop one system, explain a case using its rules, make an intentional manuscript adjustment, and confirm a later world edit is reviewable without losing that adjustment or changing finished draft context. Stop after the normal journey, chosen probes, read-only state review and recorded findings, or on a blocking defect.

## Accounts, data and reset

Use a verified author with dashboard access, worlds.view/create/update and manuscript capabilities. Use another author in a separate session for boundary probes. Administrator status must not expose a world owned by someone else.

Dawn of the Moon/Focus is available empty. Choose your own disposable world/system if repeating the whole session. Use a private unlinked manuscript you own. No public Book is copied or changed.

No destructive reset. There is no world deletion interface yet. Repeat a pass with a newly named world or another system; exact disposable fixture cleanup is a separately scoped action. Preserve actual authored lore and manuscripts.

## Phase 0 — shape review

Read `World`, `MagicSystem`, the rule/case/variation/version models and the new migration. Check:

- A system belongs to one world, manuscripts share the world, and scoped adjustments have their own source/revision/reason.
- Cases link to stable rule identities in the same system.
- World versions capture complete readable content; finished manuscript context pins those versions and copies adjustments.
- A changed source is distinguishable from a changed manuscript adjustment.
- The first slice intentionally does not model historical periods, locations or beliefs as formal scopes.

## Phase 1 — intended workflow, notes mode

1. Open `/studio/worlds`, then your world. Open Focus or create another system. Save a name, alias and Markdown notes. Reload: exact authored data should return and Markdown render safely.
2. Open Rules and add a statement, condition, consequence, exception and reasoning as useful. Choose a decision state. Save and open its details: optional fields should appear only when authored.
3. Create a world case, record the situation and expected outcome, then select its supporting rules. Save. Clicking a rule link should open Rules and focus that rule, not leave you wondering what happened.
4. Open History and inspect an earlier version. Expect its captured notes/rules/cases, not current content. Close and return to the same workspace.
5. Link the disposable private manuscript. Expect Scope to select it and all world rules to remain inherited.
6. Adjust one rule with a qualification and a reason. Save. Switch to world scope: the world statement must remain unchanged. Switch back: qualification and reason must remain visible.
7. Edit that world rule. Return to manuscript scope. Expect a changed-source warning. Review source must show old and current information. Confirm review and check that the manuscript qualification remains untouched.
8. Finish a draft in the manuscript studio. Read & compare, then expand World context. Open the captured system version. It must show what was used at finish.
9. Change the world system again. Reopen the finished draft context: it must still point to the earlier version and retained adjustments.
10. Return to the current manuscript’s rule adjustment and select Use world rule unchanged. Only the working adjustment should disappear; finished context retains its previous copy.

Record findings during the pass; apply fixes afterwards.

## Phase 2 — risk map, fix loop

| Priority | Boundary | Probe concern |
| --- | --- | --- |
| 1 | Private ownership | Other author/reader/anonymous direct world URL, system/version IDs, case-rule IDs and manuscript query; server must refuse, not only hide controls. |
| 1 | Two editing sessions | A stale notes/rule/case form must refuse overwrite and retain input. A stale variation or source-review form must also refuse. |
| 1 | Finished context | Later notes, rules, cases and adjustments must not rewrite a finished draft’s retained context. |
| 2 | World/manuscript scope | Qualification, alternate and not-applicable must affect only the selected manuscript. Switching back to inheritance must preserve snapshots. |
| 2 | Relationships | Rule from another system cannot be injected into a case. A manuscript already in another world cannot be silently reassigned. |
| 2 | Session/network interruption | A failed save must not be labelled saved or close its form; leaving dirty input warns. Explicit Cancel is deliberate discard. |
| 3 | Readability/navigation | Narrow systems drawer opens/closes, dialogs scroll, source/history results appear immediately, keyboard focus returns, aliases can find their system. |

## Phase 3 — read-only state verification

Use your actual IDs. No write queries as verification.

```sql
SELECT id, author_id, name FROM studio_worlds WHERE id = <world_id>;
SELECT id, world_id, name, revision FROM studio_magic_systems WHERE world_id = <world_id>;
SELECT id, system_id, title, decision_id, revision FROM studio_magic_rules WHERE system_id = <system_id>;
SELECT c.id, c.title, r.rule_id FROM studio_magic_cases c
LEFT JOIN studio_magic_case_rules r ON r.case_id = c.id WHERE c.system_id = <system_id>;
SELECT id, manuscript_id, rule_id, revision, source_revision, mode_id
FROM studio_magic_variations WHERE system_id = <system_id>;
SELECT id, system_id, revision, created_by, created_at FROM studio_magic_versions
WHERE system_id = <system_id> ORDER BY revision;
SELECT id, world_id, working_draft_number, revision FROM studio_manuscripts WHERE id = <manuscript_id>;
SELECT id, number, world_context FROM studio_drafts WHERE manuscript_id = <manuscript_id> AND undone_at IS NULL;
```

Expected: one current adjustment per manuscript/rule; increasing system versions for world saves; no new world version for a manuscript-only adjustment; source revision changes only when first captured or explicitly reviewed. Saved draft context must retain the version ID/content that existed at finish.

No publication, email, external AI analysis, world copying, account changes or production deployment should occur.

## Findings and coverage

| ID | Phase | Location | Expected result | Actual result | Severity | Status |
| --- | --- | --- | --- | --- | --- | --- |
| — | — | — | — | Awaiting author feedback | — | — |

Agent records findings, decisions and each probe while Tapuwa plays. Capture IDs, exact error wording, selected scope and screenshot when a result is ambiguous. Avoid credentials and unrelated private lore in evidence.

Session summary: tester/environment/commit/device; normal pass completed; probes and outcomes; verified state; findings; remaining checks.
