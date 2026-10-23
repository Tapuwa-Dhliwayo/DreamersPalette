# React and Supabase Architecture Guide

Use this guide for React applications, Supabase applications, and React applications that may call Laravel APIs.

The goal is to preserve Tapuwa's engineering philosophy without pretending JavaScript has an Eloquent-style rich Active Record model.

## Core React application skeleton

Prefer a recognisable top-level structure:

```text
src/
├── components/
├── config/
├── context/
├── hooks/
├── layouts/
├── pages/
├── services/
├── test/
└── utils/
```

Not every project requires every directory.

The principle is that top-level directories communicate technical responsibility.

Examples:

```text
components
→ reusable and domain-grouped UI

hooks
→ React-specific state, effects, and interaction behaviour

pages
→ route/screen composition

services
→ application/domain interaction

utils
→ genuinely application-wide non-domain utilities
```

Organise product areas inside those responsibilities.

Prefer:

```text
components/events/
services/events/
pages/events/
```

over forcing the entire application into feature folders by default.

The repository's established structure still wins when deliberate.

## Service domains are the React equivalent of rich model ownership

In Laravel, Tapuwa expects one entity's behaviour to be easy to find on the rich model.

In React/Supabase, returned records are plain data.

The equivalent ownership boundary is the service domain directory.

Example:

```text
services/events/
├── events.mutations.js
├── events.queries.js
├── events.service.js
├── events.validators.js
└── index.js
```

A developer asking:

> How does this application interact with events?

should begin at:

```text
src/services/events/
```

Do not scatter event persistence and workflow logic through:

```text
EventForm.jsx
CreateEvent.jsx
EventDetail.jsx
useEventDetail.js
random utils
```

## Public service API

Each service domain exposes a deliberate public API through `index.js`.

External callers prefer:

```js
import {
    createEvent,
    getEvent,
    publishEvent,
} from '@/services/events';
```

Avoid:

```js
import {getEvent} from '@/services/events/events.queries';
import {publishEvent} from '@/services/events/events.service';
```

outside the Events service domain.

Internal file organisation is an implementation detail.

The barrel export makes ownership and dependency direction obvious.

Do not export every internal helper automatically.

Export what the application is meant to use.

## Queries

`*.queries.js` owns reads from the persistence/provider boundary.

Example:

```js
export const getEvent = async (eventId) => {
    const {data: event, error} = await supabase
        .from('events')
        .select(`
            *,
            status:statuses(*)
        `)
        .eq('id', eventId)
        .single();

    if (error) throw error;

    return event;
};
```

Queries should remain focused on:

- selecting;
- filtering;
- ordering;
- provider response handling;
- mapping provider data when the mapping clearly belongs to persistence.

Do not hide domain workflows in query files.

## Mutations

`*.mutations.js` owns persistence/provider writes.

Example:

```js
export const insertEvent = async (event) => {
    const {data: createdEvent, error} = await supabase
        .from('events')
        .insert(event)
        .select()
        .single();

    if (error) throw error;

    return createdEvent;
};
```

Mutations should remain focused on:

- insert;
- update;
- delete when the domain permits deletion;
- RPC/API write calls;
- provider write response handling.

A mutation is not automatically the public business operation.

Example:

```text
updateEventRecord()
```

may be an internal persistence primitive.

```text
publishEvent()
```

may belong in `events.service.js`.

## Service workflow

`*.service.js` owns readable domain workflow and coordination.

Example:

```js
export const createEvent = async (event) => {
    validateCreateEvent(event);

    const normalizedEvent = normalizeCreateEvent(event);

    return insertEvent(normalizedEvent);
};
```

A more involved workflow may:

```text
validate
normalise
load related domain state
check supported domain conditions
call one or more queries/mutations
return the domain result
```

Keep service workflows readable from top to bottom.

Use guard clauses and singular `if` statements.

Do not turn the service into a class merely to simulate Laravel models.

Do not create a generic service manager abstraction.

The domain directory is already the ownership boundary.

## Validators

Validation belongs to the relevant service domain.

Pattern:

```js
import {ValidationError} from '../_shared/index.js';

export const validateCreateEvent = (event) => {
    const {
        title,
        event_date,
        status_id,
        host_id,
    } = event;

    if (!title) throw new ValidationError('Event title is required');
    if (!event_date) throw new ValidationError('Event date is required');
    if (!status_id) throw new ValidationError('Status is required');
    if (!host_id) throw new ValidationError('Host ID is required');
};
```

This matches Tapuwa's preferred guard-style flow.

Do not return a large nested validation result object by default when the service workflow expects validation to stop immediately.

Use a shared domain/application `ValidationError` for expected invalid input.

A validation failure is not a system error and should not be logged as one merely because it throws.

The caller may catch `ValidationError` and present the message appropriately.

## Normalisers

Keep input normalisation separate from validation.

Example:

```js
export const normalizeCreateEvent = (event) => {
    return {
        ...event,
        max_guests_per_invite: event.max_guests_per_invite ?? 1,
        settings: event.settings ?? {},
    };
};
```

Validation answers:

> Is this input acceptable?

Normalisation answers:

> What predictable shape should this accepted input have?

Do not mix large amounts of persistence code into normalisers.

Do not silently invent required business data in a normaliser.

Defaults are acceptable when the domain defines them.

## Hooks

Hooks own React-specific stateful behaviour.

Create a Hook when:

1. the logic genuinely requires React Hooks, context, state, or effects; or
2. cohesive React state/effect/interaction behaviour has grown enough to obscure the component's presentation and interaction flow.

Reuse is encouraged but is not required.

Examples:

```text
useEventDetail
useDesignBuilder
usePosterUpload
useRsvpForm
useSessionTimeout
useUndoRedo
```

A Hook may be used only once and still be correct when it gives a complex React behaviour one clear owner.

Do not create a Hook for a pure function merely because the function is used by a component.

Bad:

```js
export const useInvoiceTotal = (items) => {
    return items.reduce(
        (total, item) => total + item.total_minor,
        0,
    );
};
```

Use a plain function when no React behaviour is involved.

Do not create:

```text
useEventService
useEventManager
useEventUtils
```

as meaningless wrappers around service imports.

## Components

Components own:

- rendering;
- local presentation;
- local interaction;
- composition;
- calling Hooks and service-domain operations.

A component should remain readable as a UI flow.

If state and effects overwhelm the JSX/presentation flow, extract a cohesive Hook.

If a pure presentation interpretation belongs only to one UI area, it may live next to that component area.

Examples from an Invito-style structure:

```text
components/events/eventReadiness.js
components/public/guestScene.js
pages/public/publicRsvpState.js
```

Do not move logic into `services/` merely because it is not JSX.

Ask:

> Who clearly owns this logic?

If it only interprets a specific page or presentation experience, keep it close to that owner.

If it defines how the application interacts with a business domain, move it to the service domain.

## Pages

Pages own route-level screen composition.

A page may:

- select the correct layout;
- load or coordinate page-level Hooks;
- compose domain components;
- choose page-level state experiences;
- pass identifiers and service results to components.

Pages should not become a second service layer.

Do not put raw Supabase query chains directly into pages.

Do not duplicate domain validation in pages.

## `_shared` service infrastructure

A service `_shared` directory is for genuine cross-service application infrastructure.

Examples:

```text
ValidationError
provider guards
Supabase error handling
shared service result helpers
service-only utilities
```

Do not put domain-specific logic in `_shared` to avoid deciding its owner.

The third-use rule still applies.

## `utils`

Application-wide `utils` are for genuinely non-domain utility behaviour.

Examples:

```text
calendar formatting
date/time helpers
generic cleanup
template variable interpolation
```

Avoid generic dumping grounds such as:

```text
helpers.js
utils.js
common.js
```

when functions have a clear domain owner.

Existing files with these names should be reviewed rather than expanded automatically.

## Supabase versus Laravel API

Tapuwa deliberately uses both approaches for different kinds of domains.

This is not primarily about making provider replacement easy.

It is about choosing the correct ownership and control boundary.

### Use Supabase directly when

The interaction is simple enough for browser-to-Supabase access and RLS can safely define the security boundary.

Good candidates:

```text
simple user-owned CRUD
poems
collections
media metadata
simple event data
preferences
realtime-friendly data
```

The exact domain still depends on project requirements.

### Use a Laravel API when

Tapuwa wants more control and ownership over the data or workflow.

Strong signals:

```text
complex domain interaction
multi-model workflow
financial logic
payments
invoicing
compensation
complex permissions
audited state transitions
external integrations
server-owned notifications
PDF/business document generation
business rules that must not be client-bypassable
```

Then the React service domain calls Laravel.

Example:

```js
// invoices.queries.js
export const getInvoice = async (invoiceId) => {
    return api.get(`/invoices/${invoiceId}`);
};
```

```js
// invoices.mutations.js
export const issueInvoiceRecord = async (invoiceId) => {
    return api.post(`/invoices/${invoiceId}/issue`);
};
```

The component still imports from:

```js
@/services/invoices
```

The service domain owns the backend boundary.

## Do not bypass the chosen backend boundary

When a domain is Laravel-owned, do not add a direct Supabase mutation from a component because it is quicker.

Bad:

```text
Invoice component
→ supabase.from('invoices').update(...)
```

when invoice workflows are owned by Laravel.

That bypasses:

```text
Laravel policies
permissions
rich model behaviour
cross-model services
audit logic
server-controlled workflow
```

Likewise, do not route a simple Supabase-owned domain through Laravel merely because a Laravel API exists elsewhere in the product.

Choose the appropriate domain boundary deliberately.

## RLS is mandatory security for direct Supabase domains

Tapuwa's dislike of database-enforced Laravel workflow rules does not remove Supabase RLS.

When browser code accesses Supabase directly, RLS is part of the security boundary.

Frontend UI visibility is not authorisation.

Bad:

```text
Edit button hidden
therefore record is secure
```

Use RLS policies for direct Supabase access.

Never expose service-role or secret credentials in browser code.

## Lookup tables

Tapuwa's lookup-table preference applies to Supabase/Postgres.

Prefer database-owned changeable business classifications.

Examples:

```text
statuses
event_statuses
rsvp_statuses
user_statuses
```

The frontend may define display interpretation:

```js
export const getStatusTone = (status) => {
    return {
        published: 'success',
        draft: 'neutral',
        cancelled: 'danger',
    }[status.slug];
};
```

But JavaScript string arrays should not become the source of truth for changeable business statuses.

## Third-use rule

The third-use rule remains active.

Examples:

```text
Third repeated Supabase read
→ query function or reusable query pattern

Third repeated write pattern
→ mutation function

Third repeated domain workflow
→ service extraction/refactor

Third repeated validation
→ domain validator

Third repeated visual pattern
→ reusable component
```

A Hook is the major exception to requiring reuse.

React-specific complexity may justify a Hook before third use.

A clear domain service directory may also exist from first use because it establishes the application-facing domain boundary.

## Errors and logging

Expected validation failures:

```text
ValidationError
```

are normal application outcomes.

Do not log them as system errors by default.

Provider/database failures are system failures.

Add explicit context when logging gives technical value.

Example:

```js
console.error('Failed to publish event', {
    eventId,
    error,
});
```

Use the project's logging/monitoring tool when available instead of leaving broad `console.error` usage in production architecture.

Do not log sensitive tokens, Supabase secrets, access tokens, or private user data.

## Testing

`TESTING_WORKFLOW.md` applies here: do not write automated tests. Verify by running the code, then with a manual charter from `MANUAL_TESTING.md`.

This includes the parts of a service domain that look most worth pinning — validators and normalisers, domain workflows coordinating money, access, or state transitions, pure state-machine logic, and bug regressions. Raise those in the report instead.

Leave existing React tests alone; do not add to them or cull them without an explicit request.

## Summary

The expected flow is:

```text
PAGE
    ↓
Screen composition

COMPONENT
    ↓
Presentation and local interaction

HOOK
    ↓
Cohesive React state/effect behaviour

DOMAIN SERVICE PUBLIC API
    ↓
index.js

SERVICE WORKFLOW
    ↓
Readable domain coordination

VALIDATOR / NORMALISER
    ↓
Domain input correctness and predictable shape

QUERY / MUTATION
    ↓
Supabase or Laravel API boundary
```

The service domain is the application's one-stop place for how React interacts with that business domain.

Keep ownership obvious.
