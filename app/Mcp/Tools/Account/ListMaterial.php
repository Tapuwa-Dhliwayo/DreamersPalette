<?php

namespace App\Mcp\Tools\Account;

use App\Services\Exchange\AccountAccess;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list_material')]
#[Description('List everything the author has selected for this connection: worlds, sandbox exercises, manuscripts, poetry collections and poems, each with an id to pass to fetch. Start here.')]
#[IsReadOnly]
class ListMaterial extends AccountTool
{
    public function handle(Request $request, AccountAccess $access)
    {
        return $this->answer(fn (): array => $access->list($this->user($request)));
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
