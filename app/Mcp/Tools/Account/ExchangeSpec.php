<?php

namespace App\Mcp\Tools\Account;

use App\Services\Exchange\ExchangePackage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('exchange_spec')]
#[Description('Get the DreamersPalette exchange Markdown format for handing work back. Write the file as specified and give it to the author to upload; nothing is written through this connection.')]
#[IsReadOnly]
class ExchangeSpec extends AccountTool
{
    public function handle(Request $request)
    {
        $fields = $request->validate(['destination' => 'required|in:world_exercise,poem_analysis']);
        $this->user($request);

        return Response::text(json_encode(ExchangePackage::specification($fields['destination']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    public function schema(JsonSchema $schema): array
    {
        return ['destination' => $schema->string()->enum(['world_exercise', 'poem_analysis'])->description('Where the author will upload the file.')->required()];
    }
}
