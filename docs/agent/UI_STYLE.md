# UI and Product Design Guide

The goal is not to create a fashionable generic dashboard.

The goal is to create an interface that makes the application's business logic easy to understand and operate.

## Information density

Use balanced density based on the screen's job.

### Dashboard

Use breathing room for:

- key summaries;
- attention states;
- meaningful trends;
- progress;
- health.

Do not make dashboard cards enormous.

### Data index

Be compact and information-dense.

Users should be able to scan meaningful numbers of records.

### Form

Use clean grouping and clear structure.

### Monitoring and operations

Dense operational information is acceptable and often desirable.

The visual hierarchy must make warnings and critical states obvious.

## Cards

A card represents a meaningful information group.

Ask:

> Why is this content inside a separate surface?

If the answer is only "to separate it visually," first consider:

- spacing;
- divider;
- section heading;
- background region;
- grid;
- typography hierarchy.

Avoid:

```text
card
  card
    mini-card
```

Avoid a full page made entirely of equally weighted rounded cards.

Cards are acceptable for a first pass, but review the completed design and remove unnecessary card containers.

## Colour

Do not ship a lifeless black-and-white-only UI unless the product genuinely calls for it.

Start from a restrained neutral foundation.

Add:

- brand colour;
- clear primary action colour;
- selected navigation treatment;
- semantic emphasis;
- charts with deliberate differentiation.

Semantic colour can be conventional:

```text
green  success
amber  warning
red    danger
blue   information
```

but design context may change this.

The rule is consistency and readability, not rigid colour doctrine.

## Actions

Use icon + label for normal actions.

Examples:

```text
Add client
Issue invoice
Record payment
Save section
```

Icon-only is acceptable for:

- close;
- overflow/more menu;
- universally understood controls;
- compact table actions where context is clear.

Dangerous actions should normally have explicit labels in the confirmation flow.

Do not place five independent action icons in every table row when an overflow menu creates a cleaner hierarchy.

## Forms

Group fields by business meaning.

Use columns where fields naturally pair.

Example:

```text
CLIENT DETAILS

Name
Registration number

Email              Phone
Country            Currency
```

Do not aggressively force every form into two or three columns merely to reduce height.

## Tab-based record forms

For large records with several substantial concerns, prefer tab-based forms with an independent save action/state for each section.

Example:

```text
Client
├── Details
├── Contacts
├── Billing
├── Services
└── Settings
```

Each section should communicate its own dirty/saving/saved/error state where useful.

Do not make the user edit five unrelated concerns and press one global Save button at the bottom of a huge page.

Tabs should reflect domain concerns, not arbitrary groups of five fields.

## Tables

The default table should include enough core information to understand and act on a record without opening every row.

Example client core columns:

```text
Name
Status
Account manager
Monthly services
Outstanding
Last activity
```

Do not expose every database column.

Secondary detail belongs on the detail page.

### Configurable columns

For data-heavy screens, support optional columns when useful.

Keep a required primary column set.

Allow users to add/remove secondary columns.

Persist user preference when the feature has clear value.

Do not build column configuration for tiny tables.

## Responsive mobile data

Mobile is first-class.

Do not use horizontal scrolling as the standard responsive table strategy.

Design a mobile-specific representation.

Possible pattern:

```text
Client name                       Active
Account manager: Sarah
Outstanding: R 4,500
Last activity: 2 hours ago

[Open] [More]
```

The mobile version may change information layout and interaction.

It does not need to visually imitate the desktop table.

Preserve the important business data and actions.

## Modals

Use a modal when the action is:

- small;
- focused;
- contextual;
- understandable without leaving the current page.

Examples:

```text
Assign marketer
Change status
Quick note
Record a small payment form
Delete confirmation
```

Use a page when the workflow:

- has several sections;
- requires substantial context;
- has complex validation;
- is a primary business task.

Examples:

```text
Invoice editor
Complex client edit
Compensation contract
Multi-section settings
```

Do not use a full page for a two-field action.

Do not squeeze a complex workflow into a giant modal.

## Loading states

Use expressive loading states that match the interaction.

Examples:

- skeleton rows for a loading table;
- chart skeleton for analytics;
- button state with action-specific text;
- progress indicator for a multi-step upload/process;
- status placeholder for monitoring data.

Do not use the same centred spinner for every screen.

## Progress bars

Use progress bars more often when the data genuinely represents progress or utilisation.

Examples:

```text
storage utilisation
invoice payment progress
project completion
monthly target
quota usage
onboarding completion
pipeline stage progress
```

Do not force progress bars onto unrelated percentages.

## Charts and graphs

Use clean charts when visual comparison or trend recognition is better than a table.

Good uses:

```text
revenue over time
expense trend
lead conversion trend
server utilisation
availability
payment collection trend
```

Never use fake charts or meaningless decorative spark lines.

Every chart needs:

- a real metric;
- a useful time range or category comparison;
- readable labels/tooltips;
- design consistent with the product.

## Animation

Public websites may be expressive.

Use motion to support brand and storytelling.

Business applications use subtle functional motion:

- modal/disclosure transitions;
- loading transitions;
- state change feedback;
- drag/drop feedback;
- chart entry where restrained.

Do not make operational screens feel like marketing websites.

## Anti-AI design rules

Do not use emojis in professional dashboards.

Do not add:

```text
Welcome back 👋
Good morning, Tapuwa
Here's what's happening today
```

unless that wording serves a real product purpose.

Avoid:

- giant hero headings inside admin apps;
- huge blank spaces;
- oversized KPI cards;
- excessive `rounded-2xl`;
- gradient cards everywhere;
- every metric with a different colour;
- nested cards;
- generic dashboard layouts;
- excessive explanatory copy;
- over-designed decorative surfaces.

Use the actual business concepts of the application to drive the interface.

A CRM should feel like its sales workflow.

A monitoring page should feel operational.

A finance screen should make money movement and attention states clear.

A client page should make the client relationship understandable.
