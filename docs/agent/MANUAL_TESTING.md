# Manual Testing Charters

Manual charters are Tapuwa's verification method. Automated tests are not written (`TESTING_WORKFLOW.md`); everything is verified by running the code and executing a charter.

A charter lets Tapuwa sit down and start testing immediately, on one workflow, without deciding what to test during the session.

## Rules

- One charter covers **one business workflow**.
- Do not combine unrelated workflows. Do not write one enormous system-wide plan.
- Produce **one charter per request**. Do not batch charters for the whole application.
- Charters must reflect the actual implementation. Generic steps that would suit any application are worthless.
- While producing a charter, do not also write automated tests.
- Do not claim behaviour is correct because the code looks correct. The charter exists to make Tapuwa verify it.

A charter is a living document. Findings, decisions taken mid-session, and wording that changed during the work all belong back in it before the session is closed.

## Session modes

Testing is not one activity, and the agent must not behave the same way throughout. Tapuwa names the mode; the agent stays in it until told otherwise.

| Mode | The agent | Must not | Records |
| ---- | --------- | -------- | ------- |
| **Shape review** | Reads stored structures — schema, JSON snapshots, status rows — and reports what the design has missed | Click anything, change anything | Design findings |
| **Notes** | Records what Tapuwa reports, locating each in the code | Fix, refactor, or investigate beyond locating | Findings, as they are spoken |
| **Fix loop** | Fixes each finding as it lands, then hands it straight back for retest | Batch fixes up, or wander beyond the finding | Findings *and* what was probed |
| **Verify** | Runs read-only checks against the resulting data | Write anything, reset anything | Pass/fail per check |
| **Code audit** | Explains what the code does and why, and argues what should change | Fix, refactor, or tidy anything it finds | The reasoning, and the case for each change |

Two things sit outside the modes:

- **Reset** is a request, not a mode. The charter names the exact reset the workflow needs and what it preserves.
- **Design questions** may be asked in any mode. Answer with one recommendation and the reasoning for it, not a survey of options.

## The four phases

Run in this order. Phases 0 and 2 are the ones most often skipped, and they are where the structural and permission defects live.

### Phase 0 — data shape review (mode: shape review)

**Before any clicking.** Read what the workflow stores and ask whether the shape is right, not whether this session's writes landed. Is anything missing that will be needed later? Does a stored snapshot carry everything needed to render it without the live records? Is anything keyed by something that can change?

Output is design findings. Defects found here are usually invisible from the screen — a snapshot that has no ordering, a row keyed by a label, a status that reads differently to two audiences.

### Phase 1 — intended workflow (mode: notes)

Tapuwa works the process through as a normal user, reporting as he goes. **Nothing is fixed during this phase.** Findings are recorded and the session continues; the agent fixes afterwards, in batches, once the whole workflow has been walked.

This phase is scripted. Numbered steps, each stating the action, the expected visible result, and what to observe.

### Phase 2 — misuse and probing (mode: fix loop)

Tapuwa picks what to attack while looking at the screen, choosing what looks like a legitimate fail state. This cannot be enumerated in advance, so **do not write it as numbered steps.** Give a risk map instead: where this workflow can actually break, ranked, so the choosing is informed rather than blind.

Fixing is interleaved here on purpose. A fix often exposes the next finding, and the loop — notice, fix, retest — is the method rather than a lapse in discipline.

Tapuwa does not write anything down in this phase. **The agent records both the findings and what was probed**, so the session leaves a record of coverage rather than only of failures.

### Phase 3 — state verification (mode: verify)

Did this session's actions land correctly. Record counts, foreign keys, status, timestamps, calculated totals, ownership, audit entries, soft-delete state, pivot rows, queue and job state, duplicates.

Read-only. Provide SQL or Tinker commands where they help. Never instruct Tapuwa to modify production data.

## Code audit

Agent-driven, and the one mode with no charter and no running application behind it. Tapuwa is reading the code, or taking a senior through it, and needs the reasoning made explicit: which principles were applied, where the code departs from them, and what should change.

This is not a bug hunt. Correctness belongs to `/code-review`; tidying belongs to `/simplify`. A code audit is about **habits and design decisions** — the choices a reviewer would ask about, and whether they hold up when asked.

Produce:

```text
the principle applied, and where it shows in this code
departures from AGENTS.md, split into deliberate and drift
design decisions and what they cost downstream
what should change, ranked, with the argument for each
what was traded away under time pressure
```

The discipline:

- **Explain, do not fix.** A code audit changes nothing. Something worth changing becomes a ranked recommendation, not an edit.
- **Name the trade-off, not just the pattern.** "A form object owns validation" says nothing. "Validation lives on the question rather than in code, which is why a rule nobody implements fails at submission rather than at authoring" is the sentence a superior will ask about.
- **A decision defended must be defensible.** Where the honest answer is that something was expedient, or was copied from a sibling project without being reconsidered, say so. An audit that justifies everything is worthless in the discussion it exists to support.
- **Point at file and line.** The reader is going to go and look.
- **Separate the code's opinions from mine.** Where the repository's existing convention conflicts with a default in `AGENTS.md`, say which is being followed and why — the repository usually wins, but the reader should know a choice was made.

Scope it before starting: one domain, one layer, or one recent change. An audit of everything reads as a list and settles nothing.

## Before writing a charter

Inspect the real code and establish:

```text
business purpose of the workflow
roles involved, and the permissions/policies that gate it
Livewire components, form objects, models, services, jobs, events, notifications
tables, relationships, and status values actually used
valid and invalid status transitions
calculations and their inputs
irreversible actions: approve, release, cancel, refund, delete, pay
duplicate-submission, multi-tab, and stale-data risks
audit logging and notifications that should fire
information that must stay private between roles
```

If the code does not answer one of these, say so in the charter rather than inventing behaviour.

This reading is what makes the session fast: a question asked mid-session — why was this input refused, why did that button do nothing — is answered from it in one step instead of an investigation.

## Start with a workflow inventory

The first time manual testing is requested for an application, produce an inventory before any charter:

| Workflow | Roles involved | Risk level | Main records | Recommended session |
| -------- | -------------- | ---------- | ------------ | ------------------- |

Then recommend the first five sessions by business risk, using this priority order:

```text
1. payments and financial calculations
2. permissions and confidential information
3. irreversible actions
4. duplicate submissions
5. status transitions
6. core customer and staff workflows
7. reports, exports, generated documents
8. validation and error recovery
9. general usability
10. cosmetic presentation
```

Wait for Tapuwa to approve the inventory, then write charters one workflow at a time.

## Charter format

### Required sections

**1. Title** — business-focused. `Evaluator submits and finalises a candidate score`.

**2. Risk level** — Critical / High / Medium / Low, with a one-line reason. Critical and High normally involve money, permissions, confidential data, irreversible actions, duplicates, file release, approvals, or audit history.

**3. Goal and stop condition** — exactly what is being verified, with a clear completion point.

Bad: `Test evaluations`.

Good: `Confirm an evaluator can submit one evaluation per candidate, that the score is calculated correctly, and that repeated submission does not create duplicates.`

The session ends when the normal workflow is complete, the main misuse cases have been attempted, the resulting state has been verified, and findings are recorded. A blocking defect may also end it.

**4. Accounts and roles** — every account needed and the permissions each must hold. Stay in one role for a full pass before switching.

**5. Test data** — the exact records that must exist first, with identifiers, statuses, amounts, dates, and ownership. Include the deliberately awkward ones (a record already actioned, a record owned by someone else).

**6. Fixture reset** — the exact command that returns the workflow to its starting state, and what it preserves. A workflow with a submit, an approval, or any one-way step will be run several times in one session; the reset is a step, not an inconvenience. State plainly what it destroys.

**7. Starting state** — login account, URL or navigation path, required record status, what should be visible, any browser or session preparation.

**8. Data shape review** — what to read before testing, and the questions to ask of it.

**9. Intended workflow** — numbered steps. Each step states the action, the expected visible result, and what to observe. Never "confirm it works" — state what working means.

**10. Risk map for probing** — not steps. Where this workflow can break, ranked, so phase 2 is informed. Draw from what the code actually exposes:

```text
double-click or repeated submit
refresh mid-submission; back button after submitting
same record open in two tabs; submitting stale data
required fields empty; min/max values; very long text; unexpected characters
repeating a completed action
wrong role; direct URL to another user's record
invalid status transition
interrupting halfway; losing the session and logging back in
the same action from two accounts
```

**11. Permission checks** — which roles may view, create, edit, submit/approve, reverse, download, and see private fields. Include direct-URL access attempts. A hidden button is not authorisation; confirm the server refuses.

**12. State verification** — see phase 3.

**13. Findings table** — maintained by the agent, not by Tapuwa.

| ID | Phase | Location | Expected result | Actual result | Severity | Status |
| -- | ----- | -------- | --------------- | ------------- | -------- | ------ |

Tapuwa reports an observation in his own words. The agent locates it in the code, decides whether it is one defect or two, assigns severity, and writes the row. `Location` is the file and line, which is what makes a finding actionable later.

Severity: **Critical** — financial loss, privacy exposure, data corruption, unauthorised access, or the workflow cannot continue. **High** — a business rule is broken or the result is wrong. **Medium** — usable but incorrect, confusing, or needs a workaround. **Low** — minor visual, wording, or usability issue.

Status: New / Fixed / Documented / Needs investigation / Ready for retest / Not a defect.

### Conditional sections

Include only when the workflow warrants it. A section that would be left blank should be omitted.

**Calculation checks** — when the workflow computes anything. Show the input values and the independently calculated expected result so Tapuwa does not simply accept the displayed number.

**Notifications and side effects** — when the workflow sends, queues, generates, or uploads anything. State whether each side effect should occur once, several times, or not at all.

**Decisions taken** — choices Tapuwa made during the session that shape later work. Record them in the charter as they are made; they are the reason a later change looks the way it does.

**Evidence to capture** — only where a defect would need proof beyond a file and line: a validation message whose exact wording is the point, a total, a payment reference, a failing request.

**Session summary** — for longer or repeated sessions: date, environment, commit, accounts used, phases completed, findings by severity, areas not tested, recommended next session.

## During the session

Fixing is separated in phase 1 and interleaved in phase 2. That is deliberate in both cases: phase 1 needs the state to stay still while the whole workflow is walked, and phase 2 needs the loop.

A schema change applied while a session is under way follows the fix-mode rule in `AGENTS.md`: edit the original migration, do not refresh, and bring the test database into line by hand.

Refreshing the database is Tapuwa's call, never the agent's.

## Retest charters

After fixes, produce a focused retest charter covering the original defect, the corrected behaviour, the surrounding workflow, likely regression areas, and related duplicate or edge-case behaviour.

A fix that hides a control, changes a condition, or moves an action is itself a regression risk: name what it could have broken, not only what it repaired.

## Output when manual testing is requested

```text
1. short explanation of the workflow as found in the code
2. business rules identified
3. risks identified
4. required test data and the fixture reset
5. the charter (or the inventory, if this is the first request)
6. verification queries or Tinker commands where useful
7. findings table
8. suggested follow-up sessions
```
