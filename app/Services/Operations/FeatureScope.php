<?php

namespace App\Services\Operations;

use App\Livewire\Studio\Account\Profile;
use App\Livewire\Studio\Account\Security;
use App\Livewire\Studio\AssistantConnections;
use App\Livewire\Studio\Manuscripts\Compile;
use App\Livewire\Studio\Manuscripts\Details;
use App\Livewire\Studio\Manuscripts\Publishing;
use App\Livewire\Studio\Manuscripts\RestoreDraft;
use App\Livewire\Studio\Manuscripts\ReviewAccess;
use App\Livewire\Studio\Worlds\Calendars;
use App\Livewire\Studio\Worlds\Development;
use App\Livewire\Studio\Worlds\Index;
use App\Livewire\Studio\Worlds\Maps;
use App\Livewire\Studio\Worlds\Reasoning;
use App\Livewire\Studio\Worlds\Sandbox;
use App\Livewire\Studio\Worlds\Thinking;
use App\Livewire\Studio\Worlds\Workspace;
use Illuminate\Http\Request;
use Livewire\Component;

class FeatureScope
{
    public function forRequest(Request $request): ?string
    {
        $route = $request->route();
        $name = $route?->getName() ?? '';
        if (in_array($name, ['account.profile', 'account.security'], true)) {
            return $name;
        }
        if ($name === 'studio.world.develop') {
            return 'world.'.match ($request->query('section', 'people')) {
                'entries' => 'entries', 'timeline' => 'timeline', 'arcs' => 'arcs', 'alignments' => 'alignments', default => 'people',
            };
        }
        if (str_starts_with($name, 'studio.')) {
            return match ($name) {
                'studio.world.sandbox' => 'world.sandbox',
                'studio.world.thinking' => 'world.connections',
                'studio.world.reasoning' => 'world.reasoning',
                'studio.world.calendars' => 'world.calendars',
                'studio.world.maps', 'studio.world.map.image' => 'world.maps',
                'studio.world', 'studio.worlds' => 'world.systems',
                'studio.manuscript' => 'manuscripts.writing',
                'studio.compile', 'studio.compile.download' => 'manuscripts.compile',
                'studio.manuscript.publishing' => 'manuscripts.publishing',
                'studio.review', 'studio.reviews' => 'manuscripts.reviews',
                'studio.assistant-connections' => 'assistant.connections',
                // The page that resumes paused features must never be paused itself.
                'studio.incidents' => null,
                default => 'manuscripts.management',
            };
        }
        $uri = $route?->uri() ?? '';
        $boundary = match (true) {
            str_starts_with($uri, 'api/auth/security') => 'account.security',
            $uri === 'api/uploads' => 'assets.upload',
            $uri === 'api/generated-assets' => 'assets.generation',
            $uri === 'api/writing-assist' => 'writing.assistance',
            default => null,
        };
        if ($boundary) {
            return $boundary;
        }
        $resource = $route?->parameter('resource');
        if (str_starts_with($uri, 'api/') && in_array($resource, ['collections', 'poems', 'books', 'chapters'], true)) {
            return 'publishing.'.$resource;
        }
        if (str_starts_with($uri, 'api/studio/poem-analysis/')) {
            return 'poems.analysis';
        }
        if (preg_match('#^api/(collections|poems|books|chapters)(?:/|$)#', $uri, $parts)) {
            return 'publishing.'.$parts[1];
        }

        return null;
    }

    public function forComponent(Component $component): ?string
    {
        if ($component instanceof Development) {
            return 'world.'.match ($component->section) {
                'entries' => 'entries', 'timeline' => 'timeline', 'arcs' => 'arcs', 'alignments' => 'alignments', default => 'people',
            };
        }

        return match ($component::class) {
            Profile::class => 'account.profile',
            Security::class => 'account.security',
            Sandbox::class => 'world.sandbox',
            Thinking::class => 'world.connections',
            Reasoning::class => 'world.reasoning',
            Calendars::class => 'world.calendars',
            Maps::class => 'world.maps',
            Workspace::class, Index::class => 'world.systems',
            \App\Livewire\Studio\Manuscripts\Workspace::class => 'manuscripts.writing',
            Compile::class => 'manuscripts.compile',
            Publishing::class => 'manuscripts.publishing',
            Details::class, \App\Livewire\Studio\Manuscripts\Index::class,
            RestoreDraft::class, ReviewAccess::class => 'manuscripts.management',
            \App\Livewire\Studio\Reviews\Workspace::class, \App\Livewire\Studio\Reviews\Index::class => 'manuscripts.reviews',
            AssistantConnections::class => 'assistant.connections',
            default => null,
        };
    }
}
