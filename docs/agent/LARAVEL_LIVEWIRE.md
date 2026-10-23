# Laravel, Livewire, and Flux Guide

## Preferred stack

When the repository does not say otherwise, Tapuwa's current Laravel preference is:

```text
Laravel 13
Livewire 4
Flux UI
Alpine.js
Tailwind CSS
```

Inspect `composer.json`, `package.json`, and existing code before using version-specific APIs.

## Rich models

Models should expose natural business language.

Good:

```php
if ($invoice->isPaid()) {
    return;
}

$invoice->markAsPaid($payment);
```

Good relationship flow:

```php
$client->invoices()
    ->unpaid()
    ->overdue()
    ->latest()
    ->get();
```

Prefer scopes and relationships over repeated raw conditions and repositories.

## Livewire Form Objects are mandatory for forms

Every Livewire form uses a Form Object.

Even a small form benefits from the explicit separation between form state and interactive component state.

Example:

```php
use Livewire\Form;

class ClientForm extends Form
{
    public string $name = '';

    public string $email = '';

    public ?string $phone = null;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string'],
        ];
    }
}
```

The page component coordinates interaction:

```php
public ClientForm $form;

public function save(): void
{
    $this->authorize('create', Client::class);

    $this->form->validate();

    Client::create($this->form->all());

    Flux::toast('Client created.');
}
```

If creation has meaningful entity or cross-model behaviour, call the model/service rather than expanding the Livewire method.

## Interactive Livewire components

Livewire components own:

- page interaction;
- modal state;
- selected records;
- filtering;
- pagination;
- UI event coordination;
- invoking model/service behaviour.

They do not need to be tiny purely for line-count reasons.

When a component grows, review responsibility.

Do not create nested Livewire components for:

```text
Header
Items
Totals
Actions
```

merely to reduce one page file.

Each nested Livewire component creates another state and interaction boundary. Add one only when that boundary is useful.

## Blade and Flux components

Create reusable Blade/Flux components for genuine visual patterns.

The third-use rule is a strong signal.

Good:

```blade
<x-dashboard.stat
    label="Outstanding"
    :value="$outstanding"
    icon="banknotes"
/>
```

when the same statistic pattern is used across the dashboard.

Do not create:

```blade
<x-flux-card-wrapper>
```

that does nothing except render `<flux:card>`.

Use Flux directly when no reusable product pattern exists.

## Validation

Livewire form validation lives in the Form Object.

Controller/API validation lives in Form Requests.

Do not put routine form validation into models.

Use policies/permissions for protected behaviour.

Business workflows should only be reachable through authorised application paths.

## Controllers

Controllers are mainly expected for APIs, webhooks, callbacks, downloads, or delivery boundaries that are not naturally Livewire pages.

Prefer:

```php
public function store(StoreClientRequest $request): JsonResponse
{
    $client = Client::create($request->validated());

    return response()->json($client, 201);
}
```

For cross-model workflow, call a service.

The controller remains slim.

## Branching

Prefer readable guard clauses.

Avoid:

```php
if ($status === 'active') {
    // ...
} elseif ($status === 'pending') {
    // ...
} else {
    // ...
}
```

when a `match` or explicit state handling is clearer.

Avoid deep nesting.

## Permissions

Use `spatie/laravel-permission`.

Protect:

- routes;
- views;
- Livewire actions;
- controller actions;
- model-specific operations through policies.

A route permission does not remove the need for model-level authorisation when the operation depends on a specific record.

## Alpine.js

Use Alpine for browser-local interaction:

- transient UI state;
- keyboard behaviour;
- presentation;
- clipboard;
- local disclosures;
- client-side visual coordination.

Do not let Alpine decide business state, payment success, permissions, or entity transitions.

## Flux UI

Prefer Flux components for standard interface controls.

Do not fight the component library with large custom recreations when Flux already provides the correct primitive.

Customise composition and visual hierarchy to suit the product. Do not make every Flux component live inside a card.
