<?php

namespace App\Services\Exchange;

use App\Services\Worldbuilding\ProposalTypes;
use App\Services\Worldbuilding\SandboxProposals;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The one contract an external conversation must satisfy to hand work to DreamersPalette.
 * Parsing is deterministic: no model is ever asked to repair a package.
 */
class ExchangePackage
{
    public const MARKER = 'dreamerspalette-exchange';

    public const FORMAT = 'dreamerspalette-exchange';

    public const VERSION = 1;

    public const MAX_FILE_BYTES = 1048576;

    public const MAX_PROSE_CHARACTERS = 400000;

    public const MAX_IDEAS = 150;

    public const MAX_LINKS = 300;

    public const MAX_JSON_DEPTH = 12;

    public const DESTINATIONS = ['world_exercise', 'poem_analysis'];

    private const ENVELOPE_KEYS = ['format', 'version', 'package_id', 'parent_package_id', 'title', 'brief_md', 'destination', 'sources', 'decisions', 'world', 'poetry', 'continuation', 'external_origin'];

    private const IDEA_KEYS = ['kind', 'title', 'target_key', 'target_revision', 'proposal_id', 'proposal_revision', 'basis', 'evidence', 'notes_md', 'uncertainty', 'alternative', 'lane', 'date_label', 'position', 'completeness', 'missing', 'fields'];

    private function __construct(
        public readonly string $body,
        public readonly array $payload,
        public readonly string $rawHash,
        public readonly string $canonicalHash,
    ) {}

    public function destinationType(): string
    {
        return $this->payload['destination']['type'];
    }

    public static function parse(string $markdown): self
    {
        if (strlen($markdown) > self::MAX_FILE_BYTES) {
            self::fail('package', 'The file is larger than '.(self::MAX_FILE_BYTES / 1048576).' MiB.');
        }
        if (! mb_check_encoding($markdown, 'UTF-8')) {
            self::fail('package', 'The file must be UTF-8 text.');
        }
        ['json' => $json, 'body' => $body] = self::split(str_replace(["\r\n", "\r"], "\n", $markdown));
        if (mb_strlen($body) > self::MAX_PROSE_CHARACTERS) {
            self::fail('body', 'The readable text is longer than '.number_format(self::MAX_PROSE_CHARACTERS).' characters.');
        }
        $envelope = json_decode($json, true, self::MAX_JSON_DEPTH);
        if (json_last_error() !== JSON_ERROR_NONE) {
            self::fail('package', 'The '.self::MARKER.' block is not valid JSON ('.json_last_error_msg().'; nesting is limited to '.self::MAX_JSON_DEPTH.' levels).');
        }
        if (! is_array($envelope) || array_is_list($envelope)) {
            self::fail('package', 'The '.self::MARKER.' block must contain one JSON object.');
        }
        $payload = self::validated($envelope, $body);

        return new self($body, $payload, hash('sha256', $markdown), self::canonicalHash($payload));
    }

    /** Finds the single marker block; other fenced blocks (examples, quoted code) are ordinary prose. */
    private static function split(string $markdown): array
    {
        $lines = explode("\n", $markdown);
        $blocks = [];
        $open = null;
        foreach ($lines as $number => $line) {
            if ($open === null && preg_match('/^ {0,3}(`{3,}|~{3,})[ \t]*(.*?)[ \t]*$/', $line, $fence)) {
                if ($fence[1][0] === '`' && str_contains($fence[2], '`')) {
                    continue;
                }
                $open = ['char' => $fence[1][0], 'length' => strlen($fence[1]), 'info' => $fence[2], 'start' => $number];

                continue;
            }
            if ($open !== null && preg_match('/^ {0,3}('.preg_quote($open['char'], '/').'{'.$open['length'].',})[ \t]*$/', $line)) {
                $blocks[] = $open + ['end' => $number];
                $open = null;
            }
        }
        if ($open !== null) {
            $blocks[] = $open + ['end' => count($lines) - 1];
        }
        $exchange = array_values(array_filter($blocks, fn (array $block): bool => $block['info'] === self::MARKER));
        if (count($exchange) !== 1) {
            self::fail('package', count($exchange) === 0
                ? 'No fenced code block labelled "'.self::MARKER.'" was found.'
                : 'Exactly one "'.self::MARKER.'" block is allowed; found '.count($exchange).'.');
        }
        $block = $exchange[0];
        $json = implode("\n", array_slice($lines, $block['start'] + 1, $block['end'] - $block['start'] - 1));
        $prose = array_merge(array_slice($lines, 0, $block['start']), array_slice($lines, $block['end'] + 1));

        return ['json' => $json, 'body' => trim(implode("\n", $prose))];
    }

    private static function validated(array $envelope, string $body): array
    {
        $errors = [];
        self::strictKeys($errors, $envelope, self::ENVELOPE_KEYS, '');
        $validator = Validator::make($envelope, [
            'format' => ['required', Rule::in([self::FORMAT])],
            'version' => ['required', 'integer', Rule::in([self::VERSION])],
            'package_id' => ['required', 'uuid'],
            'parent_package_id' => ['nullable', 'uuid'],
            'title' => ['required', 'string', 'max:255'],
            'brief_md' => ['nullable', 'string', 'max:20000'],
            'destination' => ['required', 'array'],
            'destination.type' => ['required', Rule::in(self::DESTINATIONS)],
            'sources' => ['sometimes', 'array', 'list', 'max:200'],
            'sources.*' => ['array'],
            'sources.*.ref' => ['required', 'string', 'max:500'],
            'sources.*.version' => ['nullable', 'string', 'max:200'],
            'sources.*.range' => ['nullable', 'string', 'max:200'],
            'decisions' => ['sometimes', 'array', 'list', 'max:100'],
            'decisions.*' => ['array'],
            'decisions.*.by' => ['required', Rule::in(['author', 'model'])],
            'decisions.*.text' => ['required', 'string', 'max:4000'],
            'continuation' => ['sometimes', 'array'],
            'continuation.completed_scope' => ['nullable', 'string', 'max:4000'],
            'continuation.missing_scope' => ['nullable', 'string', 'max:4000'],
            'continuation.open_questions' => ['sometimes', 'array', 'list', 'max:50'],
            'continuation.open_questions.*' => ['string', 'max:1000'],
            'continuation.next_work' => ['nullable', 'string', 'max:4000'],
            'external_origin' => ['sometimes', 'array'],
            'external_origin.client' => ['nullable', 'string', 'max:255'],
            'external_origin.model' => ['nullable', 'string', 'max:255'],
            'external_origin.url' => ['nullable', 'string', 'max:2000'],
        ]);
        $errors = array_merge_recursive($errors, $validator->errors()->toArray());
        foreach (['sources.*' => ['ref', 'version', 'range'], 'decisions.*' => ['by', 'text']] as $pattern => $allowed) {
            $collection = rtrim($pattern, '.*');
            foreach ($envelope[$collection] ?? [] as $index => $entry) {
                is_array($entry) && self::strictKeys($errors, $entry, $allowed, $collection.'.'.$index);
            }
        }
        foreach (['continuation' => ['completed_scope', 'missing_scope', 'open_questions', 'next_work'], 'external_origin' => ['client', 'model', 'url']] as $section => $allowed) {
            is_array($envelope[$section] ?? null) && self::strictKeys($errors, $envelope[$section], $allowed, $section);
        }
        $type = $envelope['destination']['type'] ?? null;
        $section = match ($type) {
            'world_exercise' => self::worldSection($errors, $envelope),
            'poem_analysis' => self::poetrySection($errors, $envelope, $body),
            default => null,
        };
        if (is_array($envelope['destination'] ?? null)) {
            self::destinationRules($errors, $envelope['destination'], $type);
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $payload = [
            'format' => $envelope['format'],
            'version' => $envelope['version'],
            'package_id' => strtolower($envelope['package_id']),
            'parent_package_id' => isset($envelope['parent_package_id']) ? strtolower($envelope['parent_package_id']) : null,
            'title' => $envelope['title'],
            'brief_md' => $envelope['brief_md'] ?? null,
            'destination' => $envelope['destination'],
            'sources' => $envelope['sources'] ?? [],
            'decisions' => $envelope['decisions'] ?? [],
        ];
        $payload[$type === 'world_exercise' ? 'world' : 'poetry'] = $section;
        foreach (['continuation', 'external_origin'] as $optional) {
            if (isset($envelope[$optional])) {
                $payload[$optional] = $envelope[$optional];
            }
        }

        return $payload;
    }

    private static function destinationRules(array &$errors, array $destination, ?string $type): void
    {
        [$required, $optional] = match ($type) {
            'world_exercise' => ['world', 'exercise'],
            'poem_analysis' => ['poem', 'conversation'],
            default => [null, null],
        };
        if ($required === null) {
            return;
        }
        self::strictKeys($errors, $destination, ['type', $required, $optional], 'destination');
        $validator = Validator::make($destination, [$required => ['required', 'integer', 'min:1'], $optional => ['nullable', 'integer', 'min:1']]);
        foreach ($validator->errors()->toArray() as $key => $messages) {
            $errors['destination.'.$key] = $messages;
        }
    }

    private static function worldSection(array &$errors, array $envelope): ?array
    {
        if (isset($envelope['poetry'])) {
            $errors['poetry'][] = 'A world_exercise package cannot carry a poetry section.';
        }
        $world = $envelope['world'] ?? null;
        if (! is_array($world)) {
            $errors['world'][] = 'A world_exercise package needs a world section.';

            return null;
        }
        self::strictKeys($errors, $world, ['deliverable', 'ideas', 'links'], 'world');
        $validator = Validator::make($world, [
            'ideas' => ['required', 'array', 'list', 'min:1', 'max:'.self::MAX_IDEAS],
            'ideas.*' => ['array'],
            'ideas.*.target_revision' => ['nullable', 'integer', 'min:1'],
            'links' => ['sometimes', 'array', 'list', 'max:'.self::MAX_LINKS],
            'links.*' => ['array'],
        ]);
        foreach ($validator->errors()->toArray() as $key => $messages) {
            $errors['world.'.$key] = $messages;
        }
        foreach ((array) ($world['ideas'] ?? []) as $index => $idea) {
            if (! is_array($idea)) {
                continue;
            }
            self::strictKeys($errors, $idea, self::IDEA_KEYS, 'world.ideas.'.$index);
            if (is_array($idea['fields'] ?? null) && in_array($idea['kind'] ?? null, ProposalTypes::kinds(), true)) {
                self::strictKeys($errors, $idea['fields'], ProposalTypes::fieldNames($idea['kind']), 'world.ideas.'.$index.'.fields');
            }
            if (! empty($idea['target_key']) && empty($idea['target_revision'])) {
                $errors['world.ideas.'.$index.'.target_revision'][] = 'target_revision is required with target_key: an import has no turn to supply the revision you read.';
            }
            if (! empty($idea['proposal_id']) && empty($idea['proposal_revision'])) {
                $errors['world.ideas.'.$index.'.proposal_revision'][] = 'proposal_revision is required with proposal_id.';
            }
        }
        foreach ((array) ($world['links'] ?? []) as $index => $link) {
            is_array($link) && self::strictKeys($errors, $link, ['source', 'target', 'kind', 'label'], 'world.links.'.$index);
        }
        if (array_filter(array_keys($errors), fn (string $key): bool => str_starts_with($key, 'world'))) {
            return null;
        }

        return self::stagedWorld($errors, $world);
    }

    /** Runs the same proposal rules as the assistant path, with the package's larger limits. */
    private static function stagedWorld(array &$errors, array $world): ?array
    {
        $proposal = ['intent' => 'propose', 'schema_version' => 3, 'reply' => 'Imported package', 'deliverable' => $world['deliverable'] ?? null, 'ideas' => $world['ideas'], 'links' => $world['links'] ?? []];
        try {
            $validated = app(SandboxProposals::class)->validate($proposal, true, self::MAX_IDEAS, self::MAX_LINKS);
        } catch (ValidationException $failure) {
            foreach ($failure->errors() as $key => $messages) {
                $errors['world.'.$key] = $messages;
            }

            return null;
        }
        // validate() has no rule for target_revision, so the package's own checked value is carried across by position.
        foreach ($validated['ideas'] as $index => &$idea) {
            if (! empty($idea['target_key'])) {
                $idea['target_revision'] = (int) $world['ideas'][$index]['target_revision'];
            }
        }
        unset($idea);

        return ['deliverable' => $validated['deliverable'], 'ideas' => $validated['ideas'], 'links' => $validated['links'] ?? []];
    }

    private static function poetrySection(array &$errors, array $envelope, string $body): ?array
    {
        if (isset($envelope['world'])) {
            $errors['world'][] = 'A poem_analysis package cannot carry a world section.';
        }
        if (trim($body) === '') {
            $errors['body'][] = 'A poem_analysis package needs the analysis as readable Markdown outside the block.';
        }
        $poetry = $envelope['poetry'] ?? null;
        if (! is_array($poetry)) {
            $errors['poetry'][] = 'A poem_analysis package needs a poetry section.';

            return null;
        }
        self::strictKeys($errors, $poetry, ['snapshot', 'comparisons', 'author_note_ids', 'suggested_notes'], 'poetry');
        $fingerprint = ['required', 'string', 'regex:/^[a-f0-9]{64}$/'];
        $validator = Validator::make($poetry, [
            'snapshot' => ['required', 'array'],
            'snapshot.fingerprint' => $fingerprint,
            'comparisons' => ['sometimes', 'array', 'list', 'max:4'],
            'comparisons.*' => ['array'],
            'comparisons.*.poem' => ['required', 'integer', 'min:1', 'distinct'],
            'comparisons.*.fingerprint' => $fingerprint,
            'author_note_ids' => ['sometimes', 'array', 'list', 'max:100'],
            'author_note_ids.*' => ['integer', 'min:1', 'distinct'],
            'suggested_notes' => ['sometimes', 'array', 'list', 'max:20'],
            'suggested_notes.*' => ['string', 'max:4000'],
        ]);
        foreach ($validator->errors()->toArray() as $key => $messages) {
            $errors['poetry.'.$key] = $messages;
        }
        is_array($poetry['snapshot'] ?? null) && self::strictKeys($errors, $poetry['snapshot'], ['fingerprint'], 'poetry.snapshot');
        foreach ((array) ($poetry['comparisons'] ?? []) as $index => $comparison) {
            is_array($comparison) && self::strictKeys($errors, $comparison, ['poem', 'fingerprint'], 'poetry.comparisons.'.$index);
        }

        return [
            'snapshot' => ['fingerprint' => $poetry['snapshot']['fingerprint'] ?? null],
            'comparisons' => $poetry['comparisons'] ?? [],
            'author_note_ids' => $poetry['author_note_ids'] ?? [],
            'suggested_notes' => $poetry['suggested_notes'] ?? [],
        ];
    }

    private static function strictKeys(array &$errors, array $value, array $allowed, string $path): void
    {
        foreach (array_diff(array_keys($value), $allowed) as $unknown) {
            $key = ltrim($path.'.'.$unknown, '.');
            $errors[$key][] = 'Unknown field "'.$unknown.'". Unknown fields are errors; omit it.';
        }
    }

    private static function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }

    /** Prose and attribution are excluded, so edited narrative or a different client around the same structure still matches. */
    private static function canonicalHash(array $payload): string
    {
        unset($payload['external_origin']);

        return hash('sha256', json_encode(self::sorted($payload), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private static function sorted(array $value): array
    {
        foreach ($value as $key => $child) {
            $value[$key] = is_array($child) ? self::sorted($child) : $child;
        }
        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }

    public static function specification(string $destination): array
    {
        return ExchangeSpecification::for($destination);
    }
}
