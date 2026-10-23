# Testing, Refactoring, and Agent Workflow Guide

## Determine project context first

Read `PROJECT_CONTEXT.md`.

If it is missing, inspect:

- repository owner/context;
- README;
- current test suite;
- CI;
- existing architecture;
- framework age;
- migration history;
- deployment clues.

Do not assume every Laravel repository is an Alate project.

## Do not write automated tests

The correct number of new tests for a task is **zero**. There is no bar to clear
and no ceiling to stay under.

This holds even when the behaviour looks exactly like something worth pinning: a
money or business calculation, a permission, policy, or ownership boundary, a
state-transition or invalid-transition guard, an idempotency or
duplicate-prevention rule, a historical or immutable-snapshot invariant, a bug you
just reproduced. Note it in the report under attention required and move on.

None of the following is permission to write one:

```text
a tests directory, phpunit.xml, a composer test script, or an existing suite
a framework starter kit's own tests
having just changed something risky
being unable to think of another way to check the change
the change touching money, permissions, or history
```

Do not report "added N tests" as evidence of quality.

### If Tapuwa explicitly asks for a test

Then write it — an explicit request overrides this guide. Write the smallest one
that proves the rule and nothing more. An ambiguous mention of testing is not a
request; ask.

### Existing tests

Leave them alone. Do not add to them, do not extend them to cover a change, and do
not delete or cull them without an explicit request.


## Verifying without tests

The deliverable is a manual testing charter, not a test file. Follow `MANUAL_TESTING.md`.

Before the charter, verify the work yourself by running the code: a scratch script outside the repository, the real application, the actual request path. Report what you ran and what it showed.

If a change genuinely cannot be verified without a test, say so plainly and let Tapuwa decide. Do not write one to settle your own doubt.

## Employer projects

The same rule applies: do not introduce tests, and do not add Pest, PHPUnit configuration, factories, or CI changes.

Do not cull existing employer tests without an explicit request.

## Behaviour versus implementation

Prefer business language in ordinary behaviour tests:

```php
expect($lead->fresh()->isConverted())->toBeTrue();
expect($lead->client)->toBeInstanceOf(Client::class);
```

Assert raw fields when the exact implementation detail is the thing that matters.

Examples:

- migration regression;
- `status_id` bug;
- timestamp invariant;
- stored historical snapshot;
- idempotency key;
- rounding storage.

Choose the assertion based on the reason for the test.

## Bug-fix workflow

Before editing:

1. reproduce or trace the real failure;
2. identify the source of the bad behaviour;
3. inspect directly related code;
4. assess project context and refactoring risk.

Do not patch the symptom when the source is visible.

### Modern Tapuwa/Alate Laravel project

A focused bug fix may include directly related low-risk cleanup.

Allowed examples:

```text
rename poor local variables
remove directly related dead code
refactor the method the fix would otherwise make worse
extract the third duplicate
consolidate directly related PDF logic
clean a directly related service/component
```

The cleanup boundary must remain connected to the task.

Do not rewrite unrelated modules.

### Legacy, employer, or non-Laravel project

Make the smallest safe fix.

Do not modernise the surrounding module.

Preserve strange conventions unless changing them is necessary for the fix.

Add comments for fragile reasoning when useful.

## Automatic local decisions

An agent may fix these without asking:

```text
obvious poor variable name
safe missing type hint
directly related dead/commented code
third-use duplication in the touched concern
function structure that becomes materially worse due to the requested change
```

Assess risk before substantial refactoring.

## Decisions that require architectural pushback

Stop before implementing a requested design when it creates a major correctness problem.

Examples:

```text
payment design cannot support required partial payments
money stored as float
historical record will be recalculated from current configuration
permissions become client-controlled
deletion destroys required financial history
```

Respond with:

```text
Problem:
Consequence:
Recommended design:
```

Do not silently deviate.

## Reporting after work

Do not give a detailed diary.

Report:

### Changed

Summarise the functional areas/files changed.

### Important decisions

Mention meaningful implementation or architecture decisions.

### Checks

List the formatter, static analysis, build, migrations, scratch scripts, or other commands run, and what each showed.

Be honest about checks that could not run.

### Attention required

Prominently mention:

- deviations;
- every deleted test, with the reason;
- any behaviour that would normally have been pinned with a test, and how it was verified instead;
- unresolved risks;
- migrations requiring care;
- manual setup;
- provider configuration;
- anything Tapuwa needs to decide.

Do not list every trivial cleanup.

## Working with existing code

The existing project is evidence.

Before inventing a pattern, search for how the repository already handles the same concept.

Use Tapuwa's global style to choose between equally reasonable options, but preserve deliberate local conventions.

A strong local project convention may override the reusable default.

A bad or dangerous convention should be surfaced rather than blindly copied.
