<?php

namespace App\Console\Commands;

use App\Models\AssistantTurn;
use App\Services\Worldbuilding\AssistantConversation;
use Illuminate\Console\Command;

class RunAssistantTurn extends Command
{
    protected $signature = 'studio:assistant-turn {turn}';

    protected $description = 'Run one persisted worldbuilding assistant turn';

    public function handle(AssistantConversation $conversation): int
    {
        $conversation->run(AssistantTurn::findOrFail((int) $this->argument('turn')));

        return self::SUCCESS;
    }
}
