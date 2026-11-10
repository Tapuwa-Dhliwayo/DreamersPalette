<?php

namespace App\Mcp\Tools\Account;

use App\Services\Exchange\AccountAccess;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Search the author-selected writing and world records by text. Returns matching ids with titles, links and a short excerpt; read a match in full with fetch. Only material the author has selected is searched.')]
#[IsReadOnly]
class Search extends AccountTool
{
    public function handle(Request $request, AccountAccess $access)
    {
        $fields = $request->validate(['query' => 'required|string|min:2|max:200']);

        return $this->answer(fn (): array => ['results' => $access->search($this->user($request), $fields['query'])]);
    }

    public function schema(JsonSchema $schema): array
    {
        return ['query' => $schema->string()->description('Words or a phrase to find, 2 to 200 characters.')->min(2)->max(200)->required()];
    }
}
