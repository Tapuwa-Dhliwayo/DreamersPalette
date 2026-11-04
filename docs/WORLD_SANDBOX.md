# World sandbox implementation

Approved scope: local only. No deployment or remote data changes.

Conversation → sandbox → reviewed canon. Exercises retain messages and context;
ideas remain separate from existing world records until accepted. Naming is the
first specialist capability. Existing world tools and finished snapshots remain.

## Delivery ledger

- Foundation: additive schema, rich models, owner/world policies.
- Connections: local Codex/Claude logins and separately configured API connections.
- Exercises: durable conversation, selected context and structured proposals.
- Sandbox: editable board, links, alternatives and relative timeline.
- Canon: reviewed atomic changes, revision guards and guarded reversal.
- Naming: reviewed conventions, shortlists and existing-name awareness.
- Review: real execution checks and one end-to-end manual charter; no new tests.

T3 reference: pingdotgg/t3code revision 8b44f42b14c8e273156474a924ccf29680dd7857.
Patterns are reimplemented in the existing Laravel/Livewire application rather
than importing the T3 orchestration framework. No T3 source was copied.

Local subscription use depends on a logged-in CLI under the application user's
account. A browser stays in DreamersPalette while a bounded local process runs.
Connection setup may require the provider's own sign-in. No account credentials
are included in the repository or copied to Anubis. Hosted OAuth remains outside
this local increment.
