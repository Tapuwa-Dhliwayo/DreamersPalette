<div x-data="{ browse: false, dirty: false }" @input="if ($event.target.closest('form')) dirty = true" @world-saved.window="dirty = false" @beforeunload.window="if (dirty) { $event.preventDefault(); $event.returnValue = ''; }">
    <header class="studio-header"><a href="{{ route('studio.index') }}" class="studio-brand">Dreamer’s Palette</a><nav class="studio-header-links" aria-label="Studio"><a href="{{ route('studio.index') }}">Write</a><a href="{{ route('studio.worlds') }}">Worlds</a><a href="/dashboard">Dashboard</a></nav></header>
    <x-studio.world-tools :world="$world" :active="$section" />
    <div class="world-development" :class="{ 'browse-open': browse }">
        <aside class="world-browser" aria-label="{{ ucfirst($section) }}">
            <div class="world-section-heading"><h2>{{ ucfirst($section) }}</h2><button class="development-browse-toggle" @click="browse = false" aria-label="Close browse"><flux:icon.x-mark /></button></div>
            <flux:input label="Search" icon="magnifying-glass" wire:model.live.debounce.250ms="search" />
            <flux:select label="Show" wire:model.live.boolean="archived"><option value="false">Active</option><option value="true">Archived</option></flux:select>
            @if($section === 'knowledge')
            <flux:select label="Whose understanding?" wire:model.live="knowerFilter"><option value="">Everyone</option>
                @foreach($choices as $subject)<option value="{{ $subject['key'] }}">{{ $subject['name'] }}</option>
                @endforeach
            </flux:select>
            @endif
            @can('update', $world)<flux:button variant="primary" icon="plus" wire:click="edit">{{ $section === 'investigations' ? 'New investigation' : 'Track knowledge' }}</flux:button>
            @endcan
            <div class="development-entries">
                @forelse($records as $listed)
                <button wire:key="reasoning-{{ $section }}-{{ $listed->id }}" wire:click="select({{ $listed->id }})" @click="browse = false" @class(['active' => $selected === $listed->id])><span>{{ $listed->title }}</span><small>{{ $section === 'investigations' ? ($listed->is_resolved ? 'Resolved' : 'Open') : count($listed->knowers).' perspectives' }}</small></button>
                @empty
                <p class="world-empty">{{ $search ? 'No matches' : 'Nothing added yet' }}</p>
                @endforelse
            </div>
        </aside>
        <main id="studio-main" class="world-document development-document reasoning-document" :inert="browse">
            <button class="development-browse-toggle" @click="browse = true"><flux:icon.bars-3 variant="micro" />Browse {{ $section }}</button>
            <p role="status" class="development-notice">{{ $notice }}</p>
            <flux:error name="conflict" />
            @if($record)
            <div class="world-entry-heading"><h1>{{ $record->title }}</h1>
                @can('update', $world)<div class="thinking-actions">
                    @if(!$record->trashed())<flux:button icon="pencil-square" size="sm" wire:click="edit({{ $record->id }})">Edit</flux:button>
                    @endif
                    <flux:button size="sm" :icon="$record->trashed() ? 'arrow-uturn-left' : 'archive-box'" wire:click="archiveRecord({{ $record->id }}, {{ $record->revision }}, {{ $record->trashed() ? 'false' : 'true' }})">{{ $record->trashed() ? 'Restore' : 'Archive' }}</flux:button>
                </div>
                @endcan
            </div>
            @if($record->trashed())<p class="studio-alert">Archived. Finished drafts retain their captured copy.</p>
            @endif
            @if($section === 'investigations')
                <section><h2>Question</h2><x-studio.markdown :text="$record->question_md" />
                    <div class="thinking-actions">
                    @foreach($record->subjects as $subject)<span>{{ $subject['name'] }}</span>
                    @endforeach
                    </div>
                </section>
                <div class="reasoning-evidence-grid">
                @foreach([true => 'Supporting evidence', false => 'Counter-evidence'] as $supports => $heading)
                    <section><h2>{{ $heading }}</h2>
                        @forelse(collect($record->evidence)->filter(fn($evidence) => (bool)$evidence['supports'] === (bool)$supports) as $evidence)
                            <article class="reasoning-evidence"><x-studio.markdown :text="$evidence['text']" /><p class="reasoning-source">Source: {{ $evidence['source'] }}</p></article>
                        @empty
                            <p class="comparison-unknown">None recorded</p>
                        @endforelse
                    </section>
                @endforeach
                </div>
                <section><h2>Open questions</h2><x-studio.markdown :text="$record->open_questions_md ?: '_None recorded._'" /></section>
                <section><h2>{{ $record->is_resolved ? 'Decision' : 'Working conclusion' }}</h2><x-studio.markdown :text="$record->conclusion_md ?: '_Not decided._'" /></section>
            @else
                <section><h2>Author’s truth</h2><x-studio.markdown :text="$record->truth_md ?: '_Not established._'" /></section>
                <div class="comparison-scroll" role="region" aria-label="Knowledge perspectives" tabindex="0"><table class="world-comparison"><thead><tr><th scope="col">Who</th><th scope="col">What they believe</th><th scope="col">Learned at</th></tr></thead><tbody>
                    @forelse($record->knowers as $knower)<tr><th scope="row">{{ $knower['name'] }}</th><td><x-studio.markdown :text="$knower['belief']" /></td><td>{{ $knower['event_title'] ?: 'Not dated' }}</td></tr>
                    @empty
                    <tr><td colspan="3">No perspectives recorded. Absence means unrecorded, not that a character knows nothing.</td></tr>
                    @endforelse
                </tbody></table></div>
                <section><h2>Reader reveal</h2>
                    @if($record->revealChapter)<p>{{ $record->manuscript?->title }} · {{ $record->revealChapter->title }}{{ $record->revealChapter->trashed() ? ' (archived chapter)' : '' }}</p>
                    @else
                    <p class="comparison-unknown">Not scheduled</p>
                    @endif
                </section>
                <x-studio.markdown :text="$record->notes_md ?? ''" />
            @endif
            @else
                <h1>{{ ucfirst($section) }}</h1>
                <p>{{ $section === 'investigations' ? 'Follow a question through evidence, objections and a decision.' : 'Separate what is true, what each character believes, and when the reader learns it.' }}</p>
            @endif
            @if($section === 'knowledge' && $revealOrder->isNotEmpty())
            <section class="reasoning-reveals"><h2>Reveal order</h2><ol>
                @foreach($revealOrder as $fact)<li><button type="button" wire:click="select({{ $fact->id }})">{{ $fact->manuscript?->title }} · {{ $fact->revealChapter?->title ?? 'Chapter unavailable' }} — {{ $fact->title }}</button></li>
                @endforeach
            </ol></section>
            @endif
        </main>
    </div>
    <flux:modal wire:model="editing" class="thinking-modal" :dismissible="false" :closable="false">
        <form wire:submit="save" class="thinking-form">
            <h2>{{ $editingId ? 'Edit' : 'New' }} {{ $editingSection === 'investigations' ? 'investigation' : 'knowledge record' }}</h2>
            <flux:error name="conflict" />
            @if($editingSection === 'investigations')
                <flux:input label="Title" wire:model="investigationForm.title" />
                <flux:textarea label="Question or claim to investigate" wire:model="investigationForm.question_md" rows="3" />
                <section><h3>Linked subjects</h3>
                    @foreach($investigationForm->subjects as $index => $subject)
                    <div class="thinking-field-action" wire:key="investigation-subject-{{ $index }}"><flux:select label="Subject {{ $index + 1 }}" wire:model="investigationForm.subjects.{{ $index }}.key"><option value="">Choose subject</option>
                        @foreach($choices as $choice)<option value="{{ $choice['key'] }}">{{ $choice['name'] }} · {{ $choice['type'] }}</option>
                        @endforeach
                    </flux:select><flux:button icon="trash" aria-label="Remove subject {{ $index + 1 }}" wire:click="removeRow('subjects', {{ $index }})" /></div>
                    @endforeach
                    <flux:button size="sm" icon="plus" wire:click="addRow('subjects')">Link subject</flux:button>
                </section>
                <section><h3>Evidence</h3>
                    @foreach($investigationForm->evidence as $index => $evidence)
                    <div class="reasoning-evidence" wire:key="investigation-evidence-{{ $index }}">
                        <div class="thinking-field-action"><flux:select label="Direction {{ $index + 1 }}" wire:model.boolean="investigationForm.evidence.{{ $index }}.supports"><option value="true">Supports</option><option value="false">Challenges</option></flux:select><flux:button icon="trash" aria-label="Remove evidence {{ $index + 1 }}" wire:click="removeRow('evidence', {{ $index }})" /></div>
                        <flux:textarea label="Evidence {{ $index + 1 }}" wire:model="investigationForm.evidence.{{ $index }}.text" rows="3" />
                        <flux:input label="Source {{ $index + 1 }}" wire:model="investigationForm.evidence.{{ $index }}.source" placeholder="Entry, chapter, observation or conversation" />
                    </div>
                    @endforeach
                    <flux:button size="sm" icon="plus" wire:click="addRow('evidence')">Add evidence</flux:button>
                </section>
                <flux:textarea label="Open questions" wire:model="investigationForm.open_questions_md" rows="3" />
                <flux:textarea label="Conclusion / decision" wire:model="investigationForm.conclusion_md" rows="3" />
                <flux:checkbox label="Resolved" wire:model="investigationForm.is_resolved" />
            @else
                <flux:input label="Fact or secret" wire:model="knowledgeForm.title" />
                <flux:textarea label="Author’s truth (leave blank if undecided)" wire:model="knowledgeForm.truth_md" rows="3" />
                <section><h3>Perspectives</h3>
                    @foreach($knowledgeForm->knowers as $index => $knower)
                    <div class="reasoning-evidence" wire:key="knowledge-knower-{{ $index }}">
                        <div class="thinking-field-action"><flux:select label="Who {{ $index + 1 }}" wire:model="knowledgeForm.knowers.{{ $index }}.key"><option value="">Choose subject</option>
                            @foreach($choices as $choice)<option value="{{ $choice['key'] }}">{{ $choice['name'] }} · {{ $choice['type'] }}</option>
                            @endforeach
                        </flux:select><flux:button icon="trash" aria-label="Remove perspective {{ $index + 1 }}" wire:click="removeRow('knowers', {{ $index }})" /></div>
                        <flux:textarea label="Their understanding {{ $index + 1 }}" wire:model="knowledgeForm.knowers.{{ $index }}.belief" rows="3" />
                        <flux:select label="Learned at event {{ $index + 1 }} (optional)" wire:model="knowledgeForm.knowers.{{ $index }}.event_id"><option value="">Not dated</option>
                            @foreach($events as $event)<option value="{{ $event->id }}">{{ $event->title }}{{ $event->trashed() ? ' (archived)' : '' }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    @endforeach
                    <flux:button icon="plus" size="sm" wire:click="addRow('knowers')">Add perspective</flux:button>
                </section>
                <div class="thinking-form-pair"><flux:select label="Reveal manuscript (optional)" wire:model.live="knowledgeForm.manuscript_id"><option value="">Not scheduled</option>
                    @foreach($manuscripts as $manuscript)<option value="{{ $manuscript->id }}">{{ $manuscript->title }}</option>
                    @endforeach
                </flux:select><flux:select label="Reveal chapter" wire:model="knowledgeForm.reveal_chapter_id"><option value="">Choose chapter</option>
                    @foreach($manuscripts->firstWhere('id', $knowledgeForm->manuscript_id)?->chapters ?? [] as $chapter)<option value="{{ $chapter->id }}">{{ $chapter->title }}</option>
                    @endforeach
                </flux:select></div>
                <flux:textarea label="Continuity notes" wire:model="knowledgeForm.notes_md" rows="3" />
            @endif
            <div class="thinking-actions"><flux:button wire:click="$set('editing', false)" @click="dirty = false">Cancel</flux:button><flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled" wire:target="save">Save</flux:button></div>
        </form>
    </flux:modal>
</div>
