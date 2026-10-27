<?php

namespace App\Livewire\Studio\Manuscripts;

use App\Livewire\Forms\Studio\CompileForm;
use App\Models\Manuscript;
use App\Models\ManuscriptCompile;
use App\Services\ManuscriptCompiler;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

#[Layout('layouts.studio')]
class Compile extends Component
{
    public Manuscript $manuscript;

    public CompileForm $form;

    public ?int $previewChapter = null;

    #[Locked]
    public string $requestUuid;

    #[Locked]
    public ?int $readyId = null;

    public string $notice = '';

    #[Locked]
    public string $assistantConfig = '';

    #[Locked]
    public string $assistantCodexConfig = '';

    #[Locked]
    public ?int $assistantPacketId = null;

    public function mount(Manuscript $manuscript): void
    {
        $this->authorize('view', $manuscript);
        $this->authorize('export', $manuscript);
        $this->manuscript = $manuscript;
        $preferences = $manuscript->compile_preferences;
        if ($preferences) {
            $this->form->format = $preferences['format'];
            $this->form->prose = $preferences['prose'];
            $this->form->summaries = $preferences['summaries'];
            $this->form->world = $preferences['world'] ?? false;
        }
        $this->refreshSource();
    }

    private function chapters()
    {
        return $this->form->source === 'working' ? $this->manuscript->chapters() : $this->manuscript->drafts()->findOrFail((int) substr($this->form->source, 6))->chapters();
    }

    public function refreshSource(): void
    {
        $this->authorize('export', $this->manuscript);
        abort_unless(preg_match('/^(working|draft:[1-9][0-9]*)$/', $this->form->source), 404);
        $this->resetValidation();
        $this->manuscript->refresh();
        $this->form->revision = $this->manuscript->revision;
        $this->form->chapters = $this->chapters()->pluck('id')->all();
        $this->previewChapter = $this->form->chapters[0] ?? null;
        $this->readyId = null;
        $this->requestUuid = (string) Str::uuid();
    }

    public function updatedForm(mixed $value, string $key): void
    {
        if ($key === 'source') {
            $this->refreshSource();

            return;
        }
        $this->readyId = null;
        $this->requestUuid = (string) Str::uuid();
    }

    public function selectAll(bool $selected): void
    {
        $this->authorize('export', $this->manuscript);
        $this->form->chapters = $selected ? $this->chapters()->pluck('id')->all() : [];
        $this->readyId = null;
        $this->requestUuid = (string) Str::uuid();
    }

    public function prepare(ManuscriptCompiler $compiler): void
    {
        $this->authorize('export', $this->manuscript);
        $this->notice = '';
        $this->resetValidation();
        $compile = ManuscriptCompile::capture($this->manuscript, $this->form->fields(), auth()->id(), $this->requestUuid);
        try {
            $compiler->generate($compile);
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('export', 'The export could not be prepared. Your writing is safe. Please try again.');

            return;
        }
        $this->readyId = $compile->id;
        $this->notice = 'Export ready';
    }

    public function enableAssistant(int $id): void
    {
        abort_unless(config('studio.local_mcp_enabled'), 403);
        $this->authorize('export', $this->manuscript);
        $compile = ManuscriptCompile::where('manuscript_id', $this->manuscript->id)->where('created_by', auth()->id())->findOrFail($id);
        $token = $compile->enableAssistantAccess();
        $this->assistantPacketId = $compile->id;
        $quote = fn (string $text) => json_encode($text, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $this->assistantCodexConfig = "[mcp_servers.dreamers-review]\ncommand = ".$quote(PHP_BINARY)."\nargs = [".$quote(base_path('artisan')).", \"mcp:start\", \"dreamers-review\"]\n\n[mcp_servers.dreamers-review.env]\nDREAMERS_REVIEW_ID = ".$quote((string) $compile->id)."\nDREAMERS_REVIEW_TOKEN = ".$quote($token)."\n";

        $this->assistantConfig = json_encode(['mcpServers' => ['dreamers-review' => ['command' => PHP_BINARY, 'args' => [base_path('artisan'), 'mcp:start', 'dreamers-review'], 'env' => ['DREAMERS_REVIEW_ID' => (string) $compile->id, 'DREAMERS_REVIEW_TOKEN' => $token]]]], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public function revokeAssistant(int $id): void
    {
        $this->authorize('export', $this->manuscript);
        ManuscriptCompile::where('manuscript_id', $this->manuscript->id)->where('created_by', auth()->id())->findOrFail($id)->revokeAssistantAccess();
        if ($this->assistantPacketId === $id) {
            $this->assistantConfig = '';
            $this->assistantCodexConfig = '';
            $this->assistantPacketId = null;
        }
        $this->notice = 'Assistant access revoked';
    }

    public function render()
    {
        $this->authorize('view', $this->manuscript);
        $this->authorize('export', $this->manuscript);
        $chapters = $this->chapters()->get();
        $recent = ManuscriptCompile::where('manuscript_id', $this->manuscript->id)->where('created_by', auth()->id())->where('status', 'ready')->where('expires_at', '>', now())->latest('id')->limit(5)->get();
        $links = $recent->mapWithKeys(fn ($compile) => [$compile->id => URL::temporarySignedRoute('studio.compile.download', now()->addMinutes(30), ['manuscript' => $this->manuscript->id, 'compile' => $compile->id])]);

        return view('livewire.studio.manuscripts.compile', ['chapters' => $chapters, 'preview' => $chapters->firstWhere('id', $this->previewChapter), 'drafts' => $this->manuscript->drafts()->get(), 'recent' => $recent, 'links' => $links]);
    }
}
