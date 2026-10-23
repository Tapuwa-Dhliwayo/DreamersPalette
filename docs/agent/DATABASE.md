# Database and Eloquent Guide

## Table self-explanation

A row should make sense as its own business record.

A relationship can add context, but understanding the row's fundamental meaning should not require several joins.

Example:

A payment may contain `client_id` even when it can reach a client through an invoice if the client is part of the payment's own business identity.

Do not duplicate:

```text
client_name
client_email
invoice_number
```

merely to avoid Eloquent relationships.

Ask:

> Does this field describe the record itself, or is it only a query shortcut?

Keep the field when it has its own historical or business meaning.

## Foreign keys and cascades

Do not add foreign-key constraints by default.

Prefer:

```php
$table->uuid('client_id')->index();
```

over automatically writing:

```php
$table->foreignUuid('client_id')
    ->constrained()
    ->cascadeOnDelete();
```

The model/application workflow owns relationship safety.

Never use cascade deletion as the default.

A delete must have explicit business meaning.

Examples:

```text
Can the client be deleted?
Should the client be archived?
What happens to invoices?
What happens to payments?
What happens to project history?
```

Answer these in code and model behaviour.

If related data becomes invalid after deletion, fix the domain workflow.

## Indexes

Tapuwa strongly prefers deliberate indexes.

Review indexes for:

- foreign/reference ID lookups;
- status filters;
- slug/reference lookups;
- common date-range queries;
- common ordering;
- unique business references;
- idempotency keys;
- composite filters used repeatedly;
- reporting paths.

Do not add every possible index.

Consider write overhead and actual query shape.

## Lookup tables

Business statuses and changeable classifications normally use lookup tables.

Example:

```text
statuses
- id
- name
- slug
- active
```

Use stable slugs for code comparisons where appropriate.

Prefer relationships:

```php
$invoice->status
```

and expressive model methods:

```php
$invoice->isPaid()
```

Do not spread raw status slug comparisons through the application.

The model can centralise the interpretation.

Lookup tables are preferred because business values evolve and seed/migration history can document change.

Use PHP enums for fixed technical states when the values are not expected to become managed business data.

## Migration history

### Before first v1 production deployment

The schema is still being designed.

Edit the original migration.

Consolidate corrections.

Use:

```bash
php artisan migrate:fresh --seed
```

as often as needed.

Avoid this pre-v1 history:

```text
create_clients_table
add_slug_to_clients_table
change_client_name_length
fix_client_slug
make_client_slug_unique
```

when no real deployment has ever used the earlier schema.

The final create migration should describe the intended initial v1 schema.

### After first v1 production deployment

Never rewrite deployed migration history.

Create a new migration.

Production history matters from this point.

## Seeders

Use seeders aggressively during development for lookup/reference data.

Seeders should make a fresh development database useful.

For lookup data, prefer stable known slugs.

Be deliberate about whether a seeder is:

- required system/reference data;
- development/demo data.

Keep those concerns distinguishable.

## Eloquent queries

Prefer relationships and scopes.

Good:

```php
$client->invoices()
    ->unpaid()
    ->overdue()
    ->latest()
    ->get();
```

Avoid repository abstractions that only rename Eloquent.

A repository is not useful when it merely wraps:

```php
Invoice::query()->where(...)
```

without a real persistence boundary.

## Third-use query rule

A repeated query appearing for the third time should normally become a scope or cohesive reusable query method.

Example:

```php
public function scopeOutstanding(Builder $query): Builder
{
    return $query->whereHas('status', fn (Builder $query) => $query
        ->whereIn('slug', ['issued', 'partially-paid']));
}
```

A scope may be introduced earlier when it expresses an obvious domain concept and makes code read naturally.

## Deletion and history

Do not assume `delete()` is the correct business action.

Financial, compensation, historical, and audit records often need state transitions such as:

```text
void
cancel
archive
disable
reverse
refund
```

Model these in application code.

Do not use a database cascade to erase history.

Do not add soft deletes to every table automatically. Use them when record restoration or archive semantics are actually useful.
