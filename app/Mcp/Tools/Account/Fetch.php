<?php

namespace App\Mcp\Tools\Account;

use App\Services\Exchange\AccountAccess;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read one item by id exactly as the author wrote it: world:1, world:1/person:3, manuscript:1, chapter:5, exercise:2, exercise:2/messages, collection:3, poem:9 or analysis:4. Long text is paged; keep calling with next_offset until it is null.')]
#[IsReadOnly]
class Fetch extends AccountTool
{
    public function handle(Request $request, AccountAccess $access)
    {
        $fields = $request->validate(['id' => 'required|string|max:100', 'offset' => 'sometimes|integer|min:0', 'limit' => 'sometimes|integer|min:100|max:16000']);

        return $this->answer(fn (): array => $access->fetch($this->user($request), $fields['id'], $fields['offset'] ?? 0, $fields['limit'] ?? 12000));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('An id from list_material or search.')->required(),
            'offset' => $schema->integer()->description('Character offset to continue from; use next_offset from the previous page.')->min(0),
            'limit' => $schema->integer()->description('Characters per page, 100 to 16000. Default 12000.')->min(100)->max(16000),
        ];
    }
}
