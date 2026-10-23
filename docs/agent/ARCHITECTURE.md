# Architecture Guide

Use this guide when adding domain behaviour, choosing between a model and service, or refactoring application structure.

## Default ownership order

Ask these questions in order.

### 1. Is the code still a simple first pass?

Keep it readable and direct.

Do not create architecture merely to avoid writing ten understandable lines once.

### 2. Does the behaviour clearly belong to one entity?

Put it on the rich model.

Examples:

```php
$invoice->markAsPaid($payment);
$lead->markAsLost($reason);
$client->activate();
$complaint->isApproved();
```

The model is the primary home for the entity's behaviour.

### 3. Does the behaviour cross several peer models?

Use a cohesive service.

Examples:

```text
Payment allocation across Payment and several Invoices
Compensation evaluation across Payment, Lead attribution, Contract, and Earning
Cross-system import involving several domain models
```

The service name must describe a real business responsibility.

Prefer:

```text
CompensationService
PaymentAllocationService
ComplaintTransferService
```

over:

```text
DataService
HelperService
CommonService
ProcessService
```

### 4. Is the model becoming too large?

Review why.

Do not split a model purely because of line count.

Look for:

- a separate cross-model workflow;
- a cohesive concern with a different owner;
- substantial duplicated calculation logic;
- provider integration logic;
- reporting/query code that is not entity behaviour.

Keep relationships, scopes, casts, entity predicates, and entity-owned state changes close to the model.

## Avoid architecture scattering

A developer should not have to search these files to understand one entity:

```text
InvoiceAction
InvoiceHelper
InvoiceManager
InvoiceProcessor
InvoiceStateService
InvoiceQueryRepository
```

when the behaviour can live coherently on `Invoice`.

Prefer one clear place.

## Third-use refactoring

The third-use rule applies to:

- queries;
- calculations;
- view patterns;
- formatting;
- integration mapping;
- repeated workflow fragments.

Two occurrences can remain duplicated when abstraction would reduce clarity.

On the third occurrence, inspect all three callers and extract the shared concept.

Name the abstraction after what the code means, not how it is implemented.

## No action-class ceremony by default

Do not automatically create one action class per verb.

These are not automatically better:

```text
CreateClient
UpdateClient
ActivateClient
DeactivateClient
MarkClientInactive
```

For entity-owned behaviour, prefer the model.

Use a dedicated use-case/action class only when the repository already uses that convention or the use case has enough orchestration to justify it.

## Policies are the protection layer

Use policies and permissions to protect actions.

Routine input validation belongs in Form Objects or Form Requests.

Do not fill model methods with duplicate validation simply because callers might misuse them.

This project style assumes protected application flows are correctly authorised and validated at their boundary.

## Legacy architecture

In legacy or non-Laravel systems:

- preserve the local architecture;
- make surgical changes;
- do not modernise a whole module during a bug fix;
- comment non-obvious fragile behaviour when necessary;
- prefer the smallest change that fixes the real source of the bug.

## Architecture disagreement

When Tapuwa requests a design that causes a material domain problem, explain it before implementing.

State:

```text
Problem:
Concrete consequence:
Safer recommendation:
```

Do not silently choose a different major architecture.

Do not stop for harmless local design decisions.
