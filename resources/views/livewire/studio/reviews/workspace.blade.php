<div x-data="{ browse: false }">
    <header class="studio-header"><a class="studio-brand" href="{{ route('studio.reviews') }}">Dreamer’s Palette</a>@can('manage', $grant)<a href="{{ route('studio.manuscript.review-access', $grant->manuscript_id) }}">Review access</a>
    @endcan</header>
    <div class="compile-heading"><div><h1>{{ $grant->draft->manuscript_title }}</h1><p>{{ $grant->draft->name }} · Locked review copy</p></div></div>
    <div class="world-development" :class="{ 'browse-open': browse }"><aside class="world-browser" aria-label="Review chapters"><div class="world-section-heading"><h2>Chapters</h2><button class="development-browse-toggle" @click="browse = false" aria-label="Close chapter browse"><flux:icon.x-mark /></button></div><div class="development-entries">@foreach($chapters as $listed)<button wire:click="selectChapter({{ $listed->id }})" @click="browse = false" @class(['active' => $listed->id === $chapterId])>{{ $listed->title }}</button>
    @endforeach</div></aside>
    <main id="studio-main" class="world-document development-document" :inert="browse"><button class="development-browse-toggle" @click="browse = true"><flux:icon.bars-3 variant="micro" />Browse chapters</button><p role="status">{{ $notice }}</p>@error('conflict')<p class="studio-alert" role="alert">{{ $message }}</p>
    @enderror
    @if($chapter)<h1>{{ $chapter->title }}</h1><x-studio.markdown :text="$chapter->content_md" />
        <section class="compile-recent"><h2>Review notes</h2>@forelse($comments as $comment)<article class="review-note"><p>{{ \App\Models\User::find($comment->created_by)?->name }} · {{ $comment->created_at->asDateTime() }}</p><x-studio.markdown :text="$comment->body_md" />
            @if($comment->suggested_md !== null)<div class="draft-compare-columns"><article><h3>Reviewed text</h3><x-studio.markdown :text="$chapter->content_md" /></article><article><h3>Proposed revision</h3><x-studio.markdown :text="$comment->suggested_md" /><details><summary>Proposed Markdown</summary><pre>{{ $comment->suggested_md }}</pre></details></article></div>
                @if($comment->accepted_at)<p>Applied to current writing · {{ $comment->accepted_at->asDateTime() }}</p>
                @elseif($comment->dismissed_at)<p>Dismissed</p>
                @else
                @can('manage', $grant)<div class="thinking-actions"><flux:button icon="x-mark" size="sm" wire:click="dismiss({{ $comment->id }})">Dismiss</flux:button><flux:button icon="check" size="sm" wire:click="accept({{ $comment->id }})">Apply to working chapter</flux:button></div>
                @endcan
                @endif
            @endif</article>
        @empty<p>No notes for this chapter.</p>
        @endforelse</section>
        @can('comment', $grant)<form wire:submit="saveComment" class="studio-form"><flux:textarea label="Review note" wire:model="form.body_md" rows="4" />
            @can('suggest', $grant)@if(!$form->suggesting)<flux:button icon="pencil-square" wire:click="beginSuggestion">Propose a revision</flux:button>
            @else<flux:textarea label="Proposed chapter Markdown" wire:model="form.suggested_md" rows="14" /><flux:button wire:click="$set('form.suggesting', false)">Keep note only</flux:button>
            @endif
            @endcan<div class="thinking-actions"><flux:button type="submit" variant="primary" icon="check">Save review note</flux:button></div></form>
        @endcan
    @else<p>This review copy has no chapters.</p>
    @endif</main></div>
</div>
