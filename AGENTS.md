# Tapuwa Engineering Instructions

These are Tapuwa Dhliwayo's reusable engineering and product-design defaults.

The repository's deliberate existing conventions take precedence when they clearly conflict with these defaults. Do not blindly force a new architecture onto an established codebase.

Before substantial work:

1. Read `docs/agent/PROJECT_CONTEXT.md` when present.
2. Inspect the actual stack, nearby models, migrations, components, tests, and existing conventions.
3. Read the relevant topic guide:
   - architecture/refactoring: `docs/agent/ARCHITECTURE.md`
   - Laravel/Livewire/Flux: `docs/agent/LARAVEL_LIVEWIRE.md`
   - database/migrations: `docs/agent/DATABASE.md`
   - UI/responsive design: `docs/agent/UI_STYLE.md`
   - testing/bug fixes/agent scope: `docs/agent/TESTING_WORKFLOW.md`
   - manual test charters: `docs/agent/MANUAL_TESTING.md`
4. Respect project context. Modern Tapuwa/Alate Laravel projects and employer/legacy systems have different refactoring rules.

## Code should read like the business flow

Prefer readable domain code over ceremonial architecture.

Do not add repositories, action classes, DTOs, wrappers, services, or layers merely because a pattern exists.

Start simple. Refactor when the code proves an abstraction is needed.

### Third-use rule

Small duplication may exist twice.

When the same query, logic, UI pattern, or reusable concept is needed a third time, refactor it for reuse. The third use strongly suggests a fourth will follow.

Do not aggressively abstract the first occurrence. Do not ignore an obvious third occurrence.

## Prefer rich models

If behaviour clearly belongs to one entity, put it on that Eloquent model.

Prefer natural entity behaviour such as:

```php
$invoice->isPaid();
$invoice->hasOutstandingBalance();
$invoice->markAsPaid($payment);
$lead->markAsLost($reason);
```

The model should be the first place a developer looks for behaviour owned by that entity.

Use a cohesive service when behaviour crosses multiple peer models, ownership is genuinely unclear, substantial shared workflow logic exists, or the model is becoming materially too large.

Do not create a service for simple model-owned behaviour. Do not create generic dumping-ground services.

## Keep functions slim and control flow readable

A large function is always a refactoring review signal. Split by meaningful responsibility, not arbitrary line count.

Avoid clunky `if / elseif / else` chains and deep nesting.

Prefer guard clauses and singular `if` statements.

Use `match` or explicit cases when mutually exclusive state handling is genuinely required.

## Dates are always yyyy/mm/dd

Every date a person reads is `yyyy/mm/dd` — `2026/09/01`. No `j M Y`, no
`d/m/Y`, no locale default. A date with a time reads `yyyy/mm/dd HH:mm`.

This holds everywhere a date is written: application screens, exports, reports,
commit messages, documentation, plans, and work reports. `01/09/2026` is
ambiguous to half the people who read it and `1 Sept 2026` cannot be sorted.

Define the format once per application rather than repeating a format string at
each call site. In Laravel that is a Carbon macro registered in a service
provider:

```php
CarbonImmutable::macro('asDate', fn (): string => $this->format('Y/m/d'));

{{ $questionnaire->published_at?->asDate() }}
```

Two things are deliberately not this:

- **Stored and transmitted timestamps stay ISO-8601** (`2026-09-01T10:00:00Z`).
  They are parsed by machines, not read by people, and ISO-8601 sorts and
  round-trips. Database columns, JSON payloads, and API responses keep it.
- **Native date inputs** (`<input type="date">`) display in the browser's locale
  and cannot be told otherwise. Their value is always ISO `yyyy-mm-dd`, which is
  correct; do not replace one with a text field to control its appearance.

Relative time ("3 days ago") is not a date format and stays where it reads
better than a date would.

## Naming

Code should be understandable by reading the flow.

Short domain names such as `$client`, `$invoice`, and `$status` are good when context is clear.

Use role-specific names such as `$sourceClient`, `$originalInvoice`, and `$replacementInvoice` when multiple similar entities exist.

Avoid vague names such as `$data`, `$result`, `$item`, `$value`, `$temp`, `$x`, or `$service` when their meaning is not immediately obvious.

Boolean methods should read naturally with `is*`, `has*`, `can*`, and `should*`.

Prefer expressive scopes such as `unpaid()`, `converted()`, and `active()`.

## Comments, PHPDoc, exceptions, and logs

Comments explain **why**, not what the code already says.

Legacy or fragile systems may justify more explanatory comments around non-obvious behaviour.

Add PHPDoc only when it provides useful information PHP cannot express clearly or a genuinely complex method is difficult to parse from its flow. Do not generate redundant typed `@param`/`@return` blocks.

Expected negative business outcomes are normal state, not system failures.

Use exceptions for unexpected or abnormal failures.

Log actual system failures with useful business context. Do not log every valid negative outcome as an error.

## Permissions and policies are mandatory

Laravel applications use `spatie/laravel-permission` for permissions.

Protect actions, views, and routes appropriately with permissions and policies.

Use route middleware for route capability, policies for model/resource authorisation, and server-side authorisation inside Livewire/controller actions.

Hiding a button is not authorisation.

Do not scatter literal role-name checks through business logic when a permission or policy expresses the capability.

## Laravel and Livewire defaults

Unless the repository specifies otherwise, Tapuwa's preferred stack is Laravel 13, Livewire 4, Flux UI, Alpine.js, and Tailwind CSS.

Always use Livewire Form Objects for forms.

Use this ownership split:

```text
Form Object        form state, validation, input preparation
Livewire component interaction and page behaviour
Model              entity-owned behaviour
Service            cross-model or unclear shared workflow
```

Avoid unnecessary nested Livewire components solely to reduce file size.

### When a page stops being a single file

A single-file page component is for the legitimate case: markup, and at most one
method. The moment a page needs **two or more methods it is a component, not a
view**, and it splits — the class under `app/Livewire`, the Blade beside it under
`resources/views/livewire`.

The threshold is a method count rather than a line count because that is what
actually marks the change in kind. One `save()` on a settings form is a view that
happens to submit. Two methods means state, branching and a lifecycle, and none of
that is readable wedged above the markup it is named for.

What goes wrong without the rule is drift rather than a decision: a page opens as
a few lines of PHP, methods accrue one at a time, nothing ever objects, and the
file ends up several hundred lines of class stored where no editor treats it as
one — no jump-to-symbol, no structure outline, no static analysis by directory.

Livewire's own multi-file format splits the class from the Blade but keeps both
under the views directory. Prefer the class under `app/Livewire`: it is where the
shared components and form objects already live, and a page's logic is no
different in kind from theirs.

Create Blade/Flux components for genuine reusable visual patterns. Do not wrap ordinary Flux markup in custom components with no reuse or design meaning.

Controllers are mainly expected for APIs and non-Livewire delivery boundaries. Use Form Request classes for controller/API validation.

Routine validation belongs at the Form Object or Form Request boundary. Do not repeat routine form validation inside model methods.

## React and Supabase defaults

For React applications, preserve a recognisable top-level application skeleton such as:

```text
components
hooks
layouts
pages
services
utils
```

Organise product/domain areas inside those responsibilities.

The service domain directory is the application-facing home for how React interacts with that domain.

Example:

```text
services/events/
├── events.mutations.js
├── events.queries.js
├── events.service.js
├── events.validators.js
└── index.js
```

External callers import from the domain's public barrel:

```js
import {
    createEvent,
    getEvent,
    publishEvent,
} from '@/services/events';
```

Do not reach into internal query, mutation, or validator files from unrelated areas unless the domain intentionally exposes that implementation detail.

Within a service domain:

- `*.queries.js` owns persistence/provider reads;
- `*.mutations.js` owns persistence/provider writes;
- `*.validators.js` owns validation and normalisation for domain inputs;
- `*.service.js` owns readable domain workflows and coordination;
- `index.js` defines the public API.

Validators may throw a shared `ValidationError`.

Normalisers prepare predictable domain input separately from validation.

Hooks own React-specific state/effect behaviour. A Hook may be extracted before third use when cohesive React state, effects, or interaction logic materially obscures the component's presentation flow.

Use Supabase directly when the domain interaction is simple and Row Level Security can safely own the browser-facing data boundary.

Use a Laravel API when the domain needs stronger workflow control, richer server-owned behaviour, cross-model coordination, complex permissions, external integrations, financial logic, or stronger ownership of mutations.

Do not let components decide independently whether to use Supabase or Laravel. That choice belongs inside the relevant service domain.

For detailed rules, read `docs/agent/REACT_SUPABASE.md`.

## Eloquent style

Prefer relationships and expressive scopes whenever they make the domain flow clearer.

Prefer:

```php
$client->invoices()
    ->unpaid()
    ->overdue()
    ->latest()
    ->get();
```

over repeated query conditions or repository abstractions that merely rename Eloquent.

## Database defaults

A table should be understandable as a standalone business record.

Relationships enrich a record, but a developer should not need several joins merely to understand what the row fundamentally represents.

Prefer normalisation, but keep a related identifier when it has its own business or historical meaning on the record. Do not duplicate columns merely to avoid Eloquent relationships.

Do not add database foreign-key constraints or cascade deletes by default.

The Laravel application and model behaviour own relationship safety and deletion rules. Never use cascade deletion as a substitute for deciding what should happen to business records.

Use indexes aggressively and deliberately for real lookup, filtering, ordering, uniqueness, and reporting paths.

Changeable business statuses and classifications normally use lookup tables rather than database enums.

Use PHP enums for truly fixed technical states when appropriate.

### Migration rule

Before the first real v1 production deployment, edit and consolidate original migrations, use `migrate:fresh`, and seed aggressively. Do not create noisy migration history for a schema that has never been deployed.

After the first real production deployment, deployed migration history is history. Do not rewrite deployed migrations; create a new migration.

### Fix mode during active testing

While a manual testing session is under way and fixes are being applied against
findings, a `migrate:fresh` is not the way to apply a schema change. The test data
being worked through is the point of the session, and rebuilding the database
destroys the very state the findings were raised against.

In that mode, on a schema that is not yet live:

1. edit the original migration, as the rule above says;
2. do **not** run `migrate:fresh`;
3. bring the existing test database into line by hand — the same `ALTER`, the same
   backfill, the same seeder re-run — so the running database matches what a fresh
   install would produce;
4. re-run the relevant idempotent seeders rather than wiping, and retire rows that
   the definition no longer contains instead of leaving them orphaned.

The test of a correct fix-mode change is that a later `migrate:fresh --seed` on a
clean machine produces exactly the database now being tested against. If it would
not, the hand-applied change is wrong, not the migration.

Refreshing the database is Tapuwa's call, never the agent's, in this mode as in any
other.

## Testing: do not write automated tests

Do not write automated tests. Not a small suite, not one test, not "just this one
because it is a permission boundary". There is no bar to clear and no ceiling to
stay under — the answer is no.

This holds even when the behaviour looks exactly like something worth pinning: a
money calculation, a permission or ownership boundary, a state-transition guard, a
duplicate-prevention rule, a historical invariant, a bug you just reproduced. Say
so in the work report instead, under deviations and attention, and move on.

The existence of a `tests` directory, a `phpunit.xml`, a test script in
`composer.json`, or a framework starter kit's own tests is not permission. Nor is
having just changed something risky.

Verify by running the code — a scratch script, the real application, the actual
request path — and by writing a manual testing charter Tapuwa executes. Report what
you ran and what it showed.

Do not add a test to prove your own change works. If a change cannot be verified
without a test, say that plainly and let Tapuwa decide.

### Manual testing charters

Everything that would otherwise be a test belongs in a charter: CRUD, validation
wiring, rendering, navigation, listing and filtering, wording, layout, happy-path
clicking, and the risky rules above. When Tapuwa asks how to verify a feature,
produce a charter. Follow `docs/agent/MANUAL_TESTING.md`.

### If Tapuwa explicitly asks for a test

Then write it — an explicit request overrides this section. Write the smallest one
that proves the rule and nothing more. An ambiguous mention of testing is not a
request; ask.

### Existing tests

Leave them alone. Do not add to them, do not extend them to cover a change, and do
not delete or cull them without an explicit request.

## Refactoring and agent autonomy

### Modern Tapuwa/Alate Laravel projects

After assessing risk, a focused task may include directly related low-risk cleanup: poor local names, directly related dead code, a function the requested change would materially worsen, the third duplicate, or directly related duplicated service/component logic.

Do not turn a focused task into a repository-wide rewrite.

### Employer, legacy, or non-Laravel systems

Prefer the smallest safe surgical fix.

Preserve existing architecture and conventions. Do not perform broad cleanup without an explicit request.

### Architecture disagreement

Do not silently implement a requested design that creates a major correctness or domain limitation.

Before implementing a materially unsafe design:

1. explain the problem;
2. state the concrete consequence;
3. recommend the safer design;
4. wait for Tapuwa's decision.

Small local quality issues do not require permission. Fix them.

## UI must look designed for the application

Use balanced density:

- dashboards: breathing room, meaningful summaries, useful charts;
- data screens: compact and information-dense;
- forms: clean and logically grouped;
- monitoring screens: operationally dense.

Cards represent meaningful information groups. Do not use cards as a default replacement for a `div`, and avoid nested or oversized cards.

Use a restrained neutral foundation with brand colour, primary-action colour, and meaningful semantic emphasis. Do not ship a lifeless black-and-white-only interface simply because it is safe.

Normal actions use icon + label. Use icon-only controls only when meaning is obvious or space is constrained, such as compact table actions.

Group forms by business meaning. Prefer tab-based multi-section forms with independent save states when a record has several substantial concerns.

Tables should show enough core columns to understand and act on a record. Secondary details belong on the record page. Add optional user-configurable columns when a data-heavy screen clearly benefits.

Mobile is first-class, with especially strong desktop functionality. Do not solve mobile tables with horizontal scrolling by default. Design a distinct mobile data/list/card experience appropriate to the records.

Use modals for small focused contextual interactions and full pages for substantial multi-section workflows.

Public sites may use expressive motion. Business applications use subtle functional motion.

Use expressive loading states, meaningful clean charts, and progress bars when progress or utilisation is real. Do not add fake or decorative charts.

Avoid obvious AI UI tropes: emojis in professional dashboards, generic welcome copy, giant headings, huge empty whitespace, oversized KPI cards, card-everywhere layouts, excessive rounded surfaces, arbitrary rainbow metrics, nested cards, and over-designed pages.

## Reporting after work

Do not provide a low-level implementation diary.

After work, summarise:

1. what changed;
2. important implementation or architecture decisions;
3. checks run — what you actually ran and what it showed;
4. deviations, risks, unresolved concerns, or anything requiring Tapuwa's attention.

Behaviour that would normally have been pinned with a test, and anything verified only by eye, always belongs in point 4.

Do not mention every variable rename or tiny cleanup. Do not hide important deviations.

---

# DreamersPalette project instructions

Guidance for coding agents working in this repository.

## Project Overview

DreamersPalette is a React 19 + Vite single-page app for publishing and managing literary content. It uses React Router, Supabase, Tailwind CSS v4, and Vercel Analytics.

The app has two main surfaces:

- Public reading pages in `src/pages/public`
- Authenticated dashboard/editor pages in `src/pages/dashboard`

Shared UI components live in `src/components/ui`, layout components in `src/layouts`, hooks in `src/hooks`, and Supabase-backed data access in `src/services`.

## Commands

Use npm scripts from the repository root:

- `npm run dev` starts the Vite development server.
- `npm run build` creates the production build in `dist`.
- `npm run lint` runs ESLint.
- `npm run preview` previews a built app locally.

When changing UI, run at least `npm run lint`. Run `npm run build` before handing off larger changes or changes involving routing, imports, or environment assumptions.

## Environment

Supabase is required at runtime. The app expects these Vite environment variables:

- `VITE_SUPABASE_URL`
- `VITE_SUPABASE_ANON_KEY`

Do not commit secrets. If local commands fail immediately from missing Supabase env vars, report that directly instead of adding fallback credentials.

## Code Style

- This is an ES module JavaScript/JSX codebase. Follow the existing style: double quotes, semicolons, functional React components, and Tailwind utility classes.
- Prefer existing components from `src/components/ui` before adding new UI primitives.
- Keep data fetching and persistence logic in `src/services`; do not scatter Supabase calls through page components unless that is already the established pattern nearby.
- Keep routing changes centralized in `src/app/router.jsx` and route constants in `src/app/routes.js` when possible.
- Use existing hooks for auth, active collection, theming, and reader navigation instead of duplicating state logic.
- Avoid broad refactors unless they are necessary for the requested change.

## UI Guidelines

- Preserve the literary/editorial tone of the public reader pages.
- Preserve dashboard density and utility for authenticated workflows.
- Use Tailwind v4 classes and the global styles in `src/index.css`.
- Be careful with reader contrast, themed backgrounds, safe-area spacing, and mobile viewport behavior. Existing classes such as `h-frame`, `poetry-content`, `reader-muted`, and reader text-shadow utilities are intentional.
- Avoid nested cards and decorative gradients unless they match an existing local pattern.

## Data And Content

- Database and storage reference material lives in `docs`, including initial schema and update history SQL files.
- Treat SQL files and Supabase bucket/security docs as source-of-truth context when changing data assumptions.
- Preserve slug behavior through `src/utils/slugify.js` and color utilities through `src/utils/hexToRgb.js`.

## Reference Projects

When asked to copy an implementation pattern from another project, inspect it read-only and never edit files outside this repository.

## Verification

Before final handoff, summarize what was changed and what was verified.

Preferred checks:

- `npm run lint` for most source changes.
- `npm run build` for routing, import, dependency, or production-surface changes.

If a check cannot be run because of missing env vars, sandbox limits, or dependency issues, state the exact blocker.

## Git Safety

- Do not revert user changes or unrelated work.
- Keep commits and diffs focused on the requested task.
- Do not modify generated output such as `dist` unless the user explicitly asks for it.
