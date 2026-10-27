<?php

namespace App\Mcp\Tools;

use App\Services\ReviewPacketAccess;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read the fixed authorised packet. Start with manifest for coverage and available IDs. Read a chapter or world group by ID, following next_offset for long records. map_image returns a captured image by map ID. No live records, arbitrary paths or writes are available.')]
#[IsReadOnly]
class ReadReviewPacket extends Tool
{
    public function handle(Request $request): Response
    {
        $fields = $request->validate(['section' => 'required|in:manifest,chapter,references,systems,entries,people,events,arcs,alignments,relationships,comparisons,maps,calendars,investigations,knowledge,map_image', 'id' => 'nullable|integer|min:1|required_unless:section,manifest', 'offset' => 'sometimes|integer|min:0', 'limit' => 'sometimes|integer|min:100|max:16000']);
        $access = app(ReviewPacketAccess::class);
        // Recheck each call: an already-running client must observe revocation.
        $compile = $access->authorisedCompile();
        if ($fields['section'] === 'map_image') {
            [$bytes, $mime] = $access->mapImage($compile, (int) $fields['id']);

            return Response::image($bytes, $mime);
        }

        return Response::text(json_encode($access->read($compile, $fields['section'], isset($fields['id']) ? (int) $fields['id'] : null, $fields['offset'] ?? 0, $fields['limit'] ?? 12000), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    public function schema(JsonSchema $schema): array
    {
        return ['section' => $schema->string()->enum(['manifest', 'chapter', 'references', 'systems', 'entries', 'people', 'events', 'arcs', 'alignments', 'relationships', 'comparisons', 'maps', 'calendars', 'investigations', 'knowledge', 'map_image'])->required(), 'id' => $schema->integer()->min(1), 'offset' => $schema->integer()->min(0), 'limit' => $schema->integer()->min(100)->max(16000)];
    }
}
