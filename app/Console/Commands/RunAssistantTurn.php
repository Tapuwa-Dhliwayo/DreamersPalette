<?php

namespace App\Console\Commands;

use App\Models\AssistantTurn;
use App\Services\Poetry\PoemAnalysis;
use App\Services\Worldbuilding\AssistantConversation;
use Illuminate\Console\Command;

class RunAssistantTurn extends Command
{
    protected $signature = 'studio:assistant-turn {turn}';

    protected $description = 'Run one persisted worldbuilding assistant turn';

    public function handle(AssistantConversation $conversation): int
    {
        $turn = AssistantTurn::findOrFail((int) $this->argument('turn'));
        match (true) {
            $turn->poem_conversation_id !== null => app(PoemAnalysis::class)->run($turn),
            default => $conversation->run($turn),
        };

        return self::SUCCESS;
    }
}
