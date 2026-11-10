<?php

namespace App\Services\Exchange;

use App\Services\Worldbuilding\ProposalTypes;

/** The published contract text; ExchangePackage::specification() returns it verbatim to external clients. */
class ExchangeSpecification
{
    private const PACKAGE_ID = '3f2b8c1e-6d4a-4e7b-9a15-0c8d2e4f6a10';

    public static function for(string $destination): array
    {
        $specification = [
            'marker' => ExchangePackage::MARKER,
            'format' => ExchangePackage::FORMAT,
            'version' => ExchangePackage::VERSION,
            'how_to_deliver' => 'Write one Markdown file. Put the readable work (the discussion, timeline, analysis) as ordinary Markdown. Add exactly one fenced code block whose info string is exactly "'.ExchangePackage::MARKER.'" containing one JSON object. The JSON is authoritative; the Markdown around it is what the author reads. The author uploads the file in DreamersPalette, reviews a preview and saves it. Nothing is applied until they accept it.',
            'limits' => [
                'file_bytes' => ExchangePackage::MAX_FILE_BYTES,
                'prose_characters' => ExchangePackage::MAX_PROSE_CHARACTERS,
                'world_ideas' => ExchangePackage::MAX_IDEAS,
                'world_links' => ExchangePackage::MAX_LINKS,
                'json_depth' => ExchangePackage::MAX_JSON_DEPTH,
                'sources' => 200,
                'decisions' => 100,
            ],
            'envelope' => self::envelope($destination),
            'rules' => [
                'Unknown fields anywhere in the JSON are errors. Omit what you do not mean.',
                'Fields you leave out of an update are unchanged. Nothing is ever deleted by leaving it out.',
                'package_id is a fresh UUID per package. Re-uploading the same package is safe; a changed package needs a new package_id (set parent_package_id to the one it follows).',
                'external_origin is self-reported attribution only. It is never fetched or trusted.',
                'Imported work stays pending until the author accepts it inside DreamersPalette; an import never changes accepted records.',
            ],
        ];

        return $specification + match ($destination) {
            'world_exercise' => self::world(),
            'poem_analysis' => self::poetry(),
            default => throw new \InvalidArgumentException('Unknown exchange destination: '.$destination),
        };
    }

    private static function envelope(string $destination): array
    {
        $common = [
            'format' => 'string, required, must be "'.ExchangePackage::FORMAT.'"',
            'version' => 'integer, required, must be '.ExchangePackage::VERSION,
            'package_id' => 'UUID, required',
            'parent_package_id' => 'UUID or null',
            'title' => 'string, required, at most 255 characters',
            'brief_md' => 'string or null, at most 20000 characters: the request this work answers, in the author\'s terms',
            'sources' => 'list of at most 200 {ref: string, version: string|null, range: string|null}: what you read, with the version and range you read',
            'decisions' => 'list of at most 100 {by: "author"|"model", text: string up to 4000}: choices made so far',
            'continuation' => 'optional {completed_scope: string, missing_scope: string, open_questions: list of strings, next_work: string} so a later session can continue',
            'external_origin' => 'optional {client: string, model: string, url: string}: attribution only',
        ];

        return match ($destination) {
            'world_exercise' => $common + [
                'destination' => '{type: "world_exercise", world: integer required, exercise: integer or null}. Null creates a new exercise from title and brief_md.',
                'world' => 'object, required: {deliverable, ideas, links}. See world_section.',
            ],
            'poem_analysis' => $common + [
                'destination' => '{type: "poem_analysis", poem: integer required, conversation: integer or null}. Null creates a new analysis conversation from title.',
                'poetry' => 'object, required: {snapshot, comparisons, author_note_ids, suggested_notes}. See poetry_section.',
            ],
            default => throw new \InvalidArgumentException('Unknown exchange destination: '.$destination),
        };
    }

    private static function world(): array
    {
        return [
            'world_section' => [
                'deliverable' => 'one of discussion, records, timeline, arc, character, comparison, alignment, system, knowledge, investigation, map, calendar, naming, mixed. A declared timeline needs event ideas, arc needs arc ideas, and so on.',
                'ideas' => 'list of 1 to '.ExchangePackage::MAX_IDEAS.' ideas. Each idea may only contain: kind, title, target_key, target_revision, proposal_id, proposal_revision, basis, evidence, notes_md, uncertainty, alternative, lane, date_label, position, completeness, missing, fields.',
                'links' => 'list of at most '.ExchangePackage::MAX_LINKS.' {source, target, kind, label}.',
                'idea' => [
                    'kind' => 'required, one of: '.implode(', ', ProposalTypes::kinds()),
                    'title' => 'required, at most 255 characters',
                    'basis' => 'textual_evidence | interpretation | author_decision (default interpretation)',
                    'evidence' => 'at most 4000 characters: what supports it',
                    'notes_md' => 'free Markdown notes, at most 30000 characters',
                    'completeness' => 'required: complete | partial | unsupported',
                    'missing' => 'list of strings (each at most 500) naming what is absent; required for partial and unsupported, forbidden for complete',
                    'fields' => 'the kind\'s structured fields; see kinds',
                    'position, lane, date_label, alternative, uncertainty' => 'optional ordering and labelling',
                ],
            ],
            'references' => [
                'existing_record' => 'To update an existing record set target_key (for example "person:12") and target_revision: the revision you read for that record. The kind must match the key prefix. If the record has changed since, the author is shown a conflict.',
                'refine_pending_proposal' => 'To refine a proposal already waiting on the author\'s board set proposal_id and proposal_revision as you read them (and the same target_key if it has one). If it changed since, your version is offered as an alternative; it never overwrites the author\'s corrections. A proposal the author already accepted or archived cannot be refined.',
                'new_records_referencing_each_other' => 'Link endpoints are the zero-based position of an idea in this package (for example 0), or an existing record key such as "person:12". Arc beats reference an event idea in this package with event_ref "idea:N", an event already on the board with "board:ID", or an accepted event with "event:ID".',
                'omitted_fields' => 'Fields you omit on an update are unchanged. To drop a child (a beat, a rule) you must tell the author in prose; the importer never deletes by omission.',
            ],
            'kinds' => ProposalTypes::prompt(),
            'links' => [
                'kinds' => [
                    'participant' => 'one event and one person (either order)',
                    'before' => 'two events: source happens before target',
                    'relationship' => 'two of person, entry, system',
                    'depends' => 'two of person, entry, system',
                    'connection' => 'any two endpoints',
                ],
                'endpoint_rules' => 'Each endpoint is an idea index in this package or an existing key person:N, event:N, entry:N or system:N. At least one endpoint must be an idea in this package; a link between two existing records is a relationship idea instead. label is required.',
            ],
            'completeness' => 'Mark an idea complete only when it carries the real structure of its kind (an arc has beats, a comparison has subjects, a person has profile or roles, a knowledge record has truth_md, an investigation has question_md). Otherwise mark it partial and list what is missing. Never pad structure to look complete.',
            'example_markdown' => self::worldExample(),
        ];
    }

    private static function worldExample(): string
    {
        $package = [
            'format' => ExchangePackage::FORMAT,
            'version' => ExchangePackage::VERSION,
            'package_id' => self::PACKAGE_ID,
            'parent_package_id' => null,
            'title' => 'The first winter: two events',
            'brief_md' => 'Draft the first two events of the winter siege and tie the first to Mara.',
            'destination' => ['type' => 'world_exercise', 'world' => 1, 'exercise' => null],
            'sources' => [['ref' => 'person:12', 'version' => 'revision 4', 'range' => null]],
            'decisions' => [['by' => 'author', 'text' => 'The siege starts before the harvest festival.']],
            'world' => [
                'deliverable' => 'timeline',
                'ideas' => [
                    ['kind' => 'event', 'title' => 'The northern road closes', 'basis' => 'author_decision', 'evidence' => 'Author brief.', 'completeness' => 'complete', 'fields' => ['position' => 10, 'date_label' => 'Day 1', 'person_ids' => []]],
                    ['kind' => 'event', 'title' => 'The granaries are counted', 'basis' => 'interpretation', 'completeness' => 'complete', 'fields' => ['position' => 20, 'date_label' => 'Day 9']],
                ],
                'links' => [
                    ['source' => 0, 'target' => 'person:12', 'kind' => 'participant', 'label' => 'Mara closes the road'],
                    ['source' => 0, 'target' => 1, 'kind' => 'before', 'label' => 'Then'],
                ],
            ],
        ];

        return "# The first winter\n\nTwo events open the siege. Mara is present at the first.\n\n- Day 1: the northern road closes.\n- Day 9: the granaries are counted.\n\n```".ExchangePackage::MARKER."\n".json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n```\n";
    }

    private static function poetry(): array
    {
        return [
            'poetry_section' => [
                'snapshot' => '{fingerprint: 64 hex characters, required}: the poem version you analysed, exactly as DreamersPalette reported it when you read the poem. If it is no longer the current or a retained version, the import is rejected: re-read the poem and regenerate the analysis.',
                'comparisons' => 'optional list of at most 4 {poem: integer, fingerprint: 64 hex characters}: other poems by the same author you compared against, with the version you read',
                'author_note_ids' => 'optional list of integers: the author\'s saved notes that informed the analysis',
                'suggested_notes' => 'optional list of at most 20 strings (each at most 4000): notes the author may choose to keep. They are shown as suggestions; nothing becomes an author note until the author promotes it. The poem is never edited.',
                'analysis' => 'The analysis is the Markdown outside the block and must not be empty.',
            ],
            'writing_guidance' => [
                'Cover cadence, repetition, rhyme, mouthfeel, sonic ambiguity, lineation and meaning.',
                'Quote the exact lines you are discussing; do not paraphrase a line as if it were a quotation.',
                'Separate the speaker from the author. The speaker is a voice in the poem; do not attribute its claims to the author unless the author said so.',
                'Treat interpretations as possibilities, not facts. Say what in the text supports each reading and where it stays ambiguous.',
            ],
            'example_markdown' => self::poetryExample(),
        ];
    }

    private static function poetryExample(): string
    {
        $package = [
            'format' => ExchangePackage::FORMAT,
            'version' => ExchangePackage::VERSION,
            'package_id' => self::PACKAGE_ID,
            'parent_package_id' => null,
            'title' => 'Sound and turn in "Low Tide"',
            'brief_md' => 'How does the poem use sound to carry its turn?',
            'destination' => ['type' => 'poem_analysis', 'poem' => 1, 'conversation' => null],
            'poetry' => [
                'snapshot' => ['fingerprint' => str_repeat('0', 64)],
                'comparisons' => [],
                'author_note_ids' => [],
                'suggested_notes' => ['The repeated "again" at the line ends may be the poem\'s only refusal to move on.'],
            ],
        ];

        return "# Sound and turn in \"Low Tide\"\n\n**Cadence.** The lines shorten as the tide goes out: \"the water leaves / the water leaves again\" loses a beat the second time, which reads as the speaker running out of breath rather than ideas.\n\n**Repetition.** \"again\" ends three lines. It can be heard as habit or as grief; the poem does not choose.\n\n**Meaning.** The speaker may be addressing a lost person, or the sea. The text supports both, and nothing in it ties the speaker to the author's own life.\n\n```".ExchangePackage::MARKER."\n".json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n```\n";
    }
}
