<div x-data="{ dirty: false, systemsOpen: false }" @input="if ($event.target.closest('form')) dirty = true" @world-saved.window="dirty = false" @world-rule-opened.window="$nextTick(() => { const rule = document.getElementById('magic-rule-' + $event.detail.id); rule?.scrollIntoView({ block: 'start' }); rule?.focus(); })" @beforeunload.window="if (dirty) { $event.preventDefault(); $event.returnValue = ''; }">
    <header class="studio-header world-header">
        <a href="{{ route('studio.index') }}" class="studio-brand">Dreamer’s Palette</a>
        <nav class="studio-header-links" aria-label="Studio"><a href="{{ route('studio.index') }}">Write</a><a href="{{ route('studio.worlds') }}" aria-current="page">Worlds</a><a href="/dashboard">Dashboard</a><span>{{ auth()->user()->name }}</span></nav>
    </header>
    <x-studio.world-tools :world="$world" />
    <div class="world-mobile-bar"><button @click="systemsOpen = !systemsOpen"><flux:icon.bars-3 variant="micro" /> Systems</button><span>{{ $world->name }}</span></div>
    <div class="world-workbench" :class="{ 'world-list-open': systemsOpen }">
        <aside class="world-navigation" aria-label="Worlds"><h2>Worlds</h2>@foreach($worlds as $listedWorld)<a href="{{ route('studio.world', $listedWorld) }}" @class(['active' => $listedWorld->id === $world->id])><flux:icon.globe-alt variant="micro" />{{ $listedWorld->name }}</a>
@endforeach
<a class="world-all" href="{{ route('studio.worlds') }}">All worlds</a></aside>
        <aside class="world-system-list" aria-label="Magic systems">
            <div class="world-list-title"><h2>Magic systems</h2><button class="world-mobile-close" @click="systemsOpen = false" aria-label="Close systems"><flux:icon.x-mark /></button></div>
            <flux:input label="Search systems" class="world-search" wire:model.live.debounce.250ms="search" icon="magnifying-glass" />
            @can('update', $world)<flux:button icon="plus" variant="primary" wire:click="editSystem(true)">New system</flux:button>
@endcan

            <flux:select label="Show systems" wire:model.live.boolean="archivedSystems"><flux:select.option value="false">Active</flux:select.option><flux:select.option value="true">Archived</flux:select.option></flux:select>
            <div class="world-system-rows">@forelse($systems as $listedSystem)@if($listedSystem->trashed())<div class="world-archive-actions"><span>{{ $listedSystem->name }}</span>@can('update', $world)<flux:button size="sm" icon="arrow-uturn-left" wire:click="archiveSystem({{ $listedSystem->id }}, {{ $listedSystem->revision }}, false)">Restore</flux:button>
@endcan
</div>@else<button @class(['active' => $listedSystem->id === $systemId]) @click="systemsOpen = false; $wire.selectSystem({{ $listedSystem->id }})"><flux:icon.sparkles variant="micro" /><span>{{ $listedSystem->name }}</span></button>
@endif
 @empty<p class="world-empty">{{ $search ? 'No matching systems' : 'No systems' }}</p>
@endforelse
</div>
        </aside>
        <main id="studio-main" class="world-document">
            @if($system)
                <div class="world-entry-heading"><div><h1>{{ $system->name }}</h1><div class="world-aliases">@foreach($system->aliases as $alias)<span>{{ $alias }}</span>
@endforeach
</div></div>@if(!$system->trashed())@can('update', $world)<flux:button size="sm" icon="pencil-square" wire:click="editSystem">Edit world system</flux:button>@if(!$manuscript)<flux:button size="sm" icon="archive-box" wire:click="archiveSystem({{ $system->id }}, {{ $system->revision }}, true)">Archive world system</flux:button>
@endif

@endcan

@endif
</div>
                <div class="world-scope"><label for="world-scope">Scope</label><select id="world-scope" wire:model.live="manuscriptId"><option value="">{{ $world->name }} · World</option>@foreach($linkedManuscripts as $linked)<option value="{{ $linked->id }}">{{ $linked->title }} · Manuscript</option>
@endforeach
</select>@can('update', $world)<flux:button size="sm" icon="link" wire:click="beginLink">Link manuscript</flux:button>
@endcan
</div>
                <nav class="world-tabs" aria-label="Magic system views">@foreach(['overview' => 'Overview', 'rules' => 'Rules', 'cases' => 'Cases', 'history' => 'History'] as $key => $label)<button wire:click="changeTab('{{ $key }}')" @class(['active' => $tab === $key]) aria-current="{{ $tab === $key ? 'page' : 'false' }}">{{ $label }}</button>
@endforeach
</nav>
                @error('conflict')<p class="studio-alert" role="alert">{{ $message }}</p>@enderror
                @error('source')<p class="studio-alert" role="alert">{{ $message }}</p>@enderror
                @if(in_array($tab, ['rules', 'cases']))<flux:select class="archive-filter" label="Show" wire:model.live.boolean="archivedParts"><flux:select.option value="false">Active</flux:select.option><flux:select.option value="true">Archived</flux:select.option></flux:select>
@endif

                @if($tab === 'overview')
                    @if($system->notes_md)<x-studio.markdown :text="$system->notes_md" />@else<div class="world-empty"><p>No notes</p>@can('update', $world)<flux:button size="sm" wire:click="editSystem" icon="pencil-square">Write notes</flux:button>
@endcan
</div>
@endif

                    <div class="world-overview-links"><button wire:click="changeTab('rules')"><flux:icon.list-bullet />Rules <span>{{ $rules->count() }}</span></button><button wire:click="changeTab('cases')"><flux:icon.document-text />Cases <span>{{ $cases->count() }}</span></button></div>
                @elseif($tab === 'rules')
                    <div class="world-section-heading"><h2>{{ $manuscript ? $manuscript->title.' · Rules' : 'World rules' }}</h2>@can('update', $world)@if(!$manuscript && !$system->trashed())<flux:button size="sm" icon="plus" wire:click="editRule">Add rule</flux:button>
@endif

@endcan
</div>
                    @forelse($rules as $rule)
                        @php($variation = $variations->get($rule->id))
                        <article id="magic-rule-{{ $rule->id }}" tabindex="-1" class="world-rule" wire:key="magic-rule-{{ $rule->id }}">
                            <div class="world-rule-heading">
                                <h3>{{ $rule->title }}</h3>
                                <span class="world-state">{{ $rule->decision->name }}</span>
                                @can('update', $world)
                                    @if(!$manuscript && !$system->trashed())
                                        <flux:button size="sm" :icon="$rule->trashed() ? 'arrow-uturn-left' : 'archive-box'" wire:click="archivePart('rule', {{ $rule->id }}, {{ $rule->trashed() ? 'false' : 'true' }}, {{ $system->revision }})">{{ $rule->trashed() ? 'Restore' : 'Archive' }}</flux:button>
                                    
@endif

                                    @if(!$rule->trashed() && !$system->trashed())
                                        @if($manuscript)
                                            @can('update', $manuscript)
                                                <flux:button size="sm" wire:click="adjustRule({{ $rule->id }})">{{ $variation ? 'Edit adjustment' : 'Adjust for manuscript' }}</flux:button>
                                            
@endcan

                                        @else
                                            <flux:button size="sm" icon="pencil-square" wire:click="editRule({{ $rule->id }})">Edit</flux:button>
                                        
@endif

                                    
@endif

                                
@endcan

                            </div>
                            @if($variation && $variation->hasSourceChanges($rule))
                                <div class="world-source-change"><span>World rule changed since this adjustment.</span><flux:button size="sm" wire:click="compareSource({{ $rule->id }})">Review source</flux:button></div>
                            
@endif

                            @if(!$variation || $variation->mode->key === 'qualify')
                                <x-studio.markdown :text="$rule->statement_md" />
                            
@endif

                            @if($variation)
                                <div class="world-variation">
                                    <h4>{{ $variation->mode->name }}</h4>
                                    @if($variation->text_md)
                                        <x-studio.markdown :text="$variation->text_md" />
                                    
@endif

                                    <x-studio.markdown :text="$variation->reason_md" />
                                </div>
                            
@endif

                            <details class="world-rule-details">
                                <summary>{{ $variation ? 'World rule and details' : 'Conditions, consequences and reasoning' }}</summary>
                                @if($variation && $variation->mode->key !== 'qualify')
                                    <h4>World rule</h4><x-studio.markdown :text="$rule->statement_md" />
                                
@endif

                                @foreach(['conditions_md' => 'Conditions', 'consequences_md' => 'Consequences', 'exceptions_md' => 'Exceptions', 'reasoning_md' => 'Reasoning'] as $field => $label)
                                    @if($rule->{$field})
                                        <h4>{{ $label }}</h4><x-studio.markdown :text="$rule->{$field}" />
                                    
@endif

                                
@endforeach

                            </details>
                        </article>
                    @empty
                        <p class="world-empty">No rules</p>
                    
@endforelse

                @elseif($tab === 'cases')
                    <div class="world-section-heading"><h2>World cases</h2>@if(!$system->trashed())@can('update', $world)<flux:button size="sm" icon="plus" wire:click="editCase">Add world case</flux:button>
@endcan

@endif
</div>
                    @forelse($cases as $case)
                        <article class="world-rule" wire:key="magic-case-{{ $case->id }}">
                            <div class="world-rule-heading">
                                <h3>{{ $case->title }}</h3>
                                @can('update', $world)
                                    @if(!$manuscript && !$system->trashed())
                                        <flux:button size="sm" :icon="$case->trashed() ? 'arrow-uturn-left' : 'archive-box'" wire:click="archivePart('case', {{ $case->id }}, {{ $case->trashed() ? 'false' : 'true' }}, {{ $system->revision }})">{{ $case->trashed() ? 'Restore' : 'Archive' }}</flux:button>
                                    
@endif

                                    @if(!$case->trashed() && !$system->trashed())
                                        <flux:button size="sm" wire:click="editCase({{ $case->id }})" icon="pencil-square">Edit world case</flux:button>
                                    
@endif

                                
@endcan

                            </div>
                            <h4>Situation</h4><x-studio.markdown :text="$case->situation_md" />
                            @if($case->outcome_md)
                                <h4>Outcome</h4><x-studio.markdown :text="$case->outcome_md" />
                            
@endif

                            @if($case->reasoning_md)
                                <h4>Reasoning</h4><x-studio.markdown :text="$case->reasoning_md" />
                            
@endif

                            <div class="world-case-links">
                                @forelse($case->rules as $linkedRule)
                                    <button wire:click="openRule({{ $linkedRule->id }})">{{ $linkedRule->title }} <span>{{ $linkedRule->decision->name }}{{ $linkedRule->trashed() ? ' · Archived' : '' }}</span></button>
                                @empty
                                    <span>No linked rules</span>
                                
@endforelse

                            </div>
                        </article>
                    @empty
                        <p class="world-empty">No cases</p>
                    
@endforelse

                @else
                    <div class="world-section-heading"><h2>World versions</h2></div>
                    @foreach($versions as $savedVersion)<button class="world-version-row" wire:click="viewVersion({{ $savedVersion->id }})"><span>Version {{ $savedVersion->revision }}</span><span>{{ $savedVersion->created_at->asDateTime() }}</span><flux:icon.arrow-up-right variant="micro" /></button>
@endforeach

                
@endif

            @else
                <div class="studio-empty"><h1>{{ $world->name }}</h1><p>No magic systems</p>@can('update', $world)<flux:button variant="primary" icon="plus" wire:click="editSystem(true)">New system</flux:button>
@endcan
</div>
            
@endif

        </main>
    </div>
    <flux:modal wire:model="editingSystem" class="world-form-modal" :dismissible="false" :closable="false"><form wire:submit="saveSystem" class="studio-form"><flux:heading size="lg">{{ $newSystem ? 'New world system' : 'Edit world system' }}</flux:heading>@error('conflict')<p role="alert" class="studio-alert">{{ $message }}</p>@enderror<flux:input label="Name" wire:model="systemForm.name" maxlength="255" /><flux:textarea label="Aliases · one per line" badge="Optional" wire:model="systemForm.aliases" rows="2" /><flux:textarea label="Notes · Markdown" wire:model="systemForm.notes_md" rows="12" /><div class="studio-form-actions"><flux:button @click="dirty = false" wire:click="$set('editingSystem', false)">Cancel</flux:button><flux:button variant="primary" type="submit">Save system</flux:button></div></form></flux:modal>
    <flux:modal wire:model="editingRule" class="world-form-modal" :dismissible="false" :closable="false"><form wire:submit="saveRule" class="studio-form"><flux:heading size="lg">{{ $ruleId ? 'Edit world rule' : 'New world rule' }}</flux:heading>@error('conflict')<p role="alert" class="studio-alert">{{ $message }}</p>@enderror<flux:input label="Title" wire:model="ruleForm.title" maxlength="255" /><flux:select label="Decision" wire:model="ruleForm.decision_id">@foreach($decisions as $decision)<flux:select.option value="{{ $decision->id }}">{{ $decision->name }}</flux:select.option>
@endforeach
</flux:select><flux:textarea label="Rule · Markdown" wire:model="ruleForm.statement_md" rows="4" /><flux:textarea label="Conditions" badge="Optional" wire:model="ruleForm.conditions_md" rows="3" /><flux:textarea label="Consequences" badge="Optional" wire:model="ruleForm.consequences_md" rows="3" /><flux:textarea label="Exceptions" badge="Optional" wire:model="ruleForm.exceptions_md" rows="3" /><flux:textarea label="Reasoning" badge="Optional" wire:model="ruleForm.reasoning_md" rows="3" /><div class="studio-form-actions"><flux:button @click="dirty = false" wire:click="$set('editingRule', false)">Cancel</flux:button><flux:button type="submit" variant="primary">Save rule</flux:button></div></form></flux:modal>
    <flux:modal wire:model="editingCase" class="world-form-modal" :dismissible="false" :closable="false"><form wire:submit="saveCase" class="studio-form"><flux:heading size="lg">{{ $caseId ? 'Edit world case' : 'New world case' }}</flux:heading>@error('conflict')<p role="alert" class="studio-alert">{{ $message }}</p>@enderror<flux:input label="Title" wire:model="caseForm.title" maxlength="255" /><flux:textarea label="Situation · Markdown" wire:model="caseForm.situation_md" rows="4" /><flux:textarea label="Outcome" badge="Optional" wire:model="caseForm.outcome_md" rows="3" /><flux:textarea label="Reasoning" badge="Optional" wire:model="caseForm.reasoning_md" rows="3" /><fieldset class="world-rule-choices"><legend>Linked rules</legend>@foreach($caseRuleChoices as $rule)<label><input type="checkbox" wire:model="caseForm.rule_ids" value="{{ $rule->id }}" />{{ $rule->title }}{{ $rule->trashed() ? ' · Archived' : '' }}</label>
@endforeach
 @error('caseForm.rule_ids.*')<p role="alert">{{ $message }}</p>@enderror</fieldset><div class="studio-form-actions"><flux:button @click="dirty = false" wire:click="$set('editingCase', false)">Cancel</flux:button><flux:button type="submit" variant="primary">Save case</flux:button></div></form></flux:modal>
    <flux:modal wire:model="editingVariation" class="world-form-modal" :dismissible="false" :closable="false"><form wire:submit="saveVariation" class="studio-form"><flux:heading size="lg">Adjust rule · {{ $manuscript?->title }}</flux:heading>@error('conflict')<p role="alert" class="studio-alert">{{ $message }}</p>@enderror<flux:select label="Behaviour" wire:model="variationForm.mode_id"><flux:select.option value="0">Use world rule unchanged</flux:select.option>@foreach($modes as $mode)<flux:select.option value="{{ $mode->id }}">{{ $mode->name }}</flux:select.option>
@endforeach
</flux:select><flux:textarea label="Manuscript adjustment · Markdown" wire:model="variationForm.text_md" rows="5" /><flux:textarea label="Reason" wire:model="variationForm.reason_md" rows="3" /><div class="studio-form-actions"><flux:button @click="dirty = false" wire:click="$set('editingVariation', false)">Cancel</flux:button><flux:button variant="primary" type="submit">Save adjustment</flux:button></div></form></flux:modal>
    <flux:modal wire:model="linking" class="md:w-[32rem]" :dismissible="false" :closable="false"><form wire:submit="linkManuscript" class="studio-form"><flux:heading size="lg">Link manuscript to {{ $world->name }}</flux:heading><flux:select label="Manuscript" wire:model.live="linkForm.manuscript_id"><flux:select.option value="0">Choose manuscript</flux:select.option>@foreach($availableManuscripts as $available)<flux:select.option value="{{ $available->id }}">{{ $available->title }}</flux:select.option>
@endforeach
</flux:select><div class="studio-form-actions"><flux:button @click="dirty = false" wire:click="$set('linking', false)">Cancel</flux:button><flux:button type="submit" variant="primary">Link manuscript</flux:button></div></form></flux:modal>
    <flux:modal wire:model="reviewingSource" class="studio-draft-modal">@if($sourceRule && $sourceVariation)<flux:heading size="lg">Review world rule · {{ $sourceRule->title }}</flux:heading><div class="draft-compare-columns"><article><h4>Source used by this adjustment</h4><h3>{{ $sourceVariation->source_rule['title'] }}</h3><p class="world-state">{{ $sourceVariation->source_rule['decision_name'] ?? $decisions->firstWhere('id', $sourceVariation->source_rule['decision_id'])?->name }}</p>@foreach(['statement_md'=>'Rule','conditions_md'=>'Conditions','consequences_md'=>'Consequences','exceptions_md'=>'Exceptions','reasoning_md'=>'Reasoning'] as $field=>$label)@if($sourceVariation->source_rule[$field] ?? '')<h4>{{ $label }}</h4><x-studio.markdown :text="$sourceVariation->source_rule[$field]" />
@endif
 
@endforeach
</article><article><h4>Current world rule</h4><h3>{{ $sourceRule->title }}</h3><p class="world-state">{{ $sourceRule->decision->name }}</p>@foreach(['statement_md'=>'Rule','conditions_md'=>'Conditions','consequences_md'=>'Consequences','exceptions_md'=>'Exceptions','reasoning_md'=>'Reasoning'] as $field=>$label)@if($sourceRule->{$field})<h4>{{ $label }}</h4><x-studio.markdown :text="$sourceRule->{$field}" />
@endif
 
@endforeach
</article></div>@error('source')<p role="alert" class="studio-alert">{{ $message }}</p>@enderror @can('update', $world)@if($manuscript)@can('update', $manuscript)<div class="studio-form-actions"><flux:button variant="primary" wire:click="confirmSource">Mark current source reviewed</flux:button></div>
@endcan
 
@endif
 
@endcan
 
@endif
</flux:modal>
    <flux:modal wire:model="readingVersion" class="studio-draft-modal">@if($version)<flux:heading size="lg">{{ $version->snapshot['name'] }} · Version {{ $version->revision }}</flux:heading><div class="world-aliases">@foreach($version->snapshot['aliases'] as $alias)<span>{{ $alias }}</span>
@endforeach
</div><x-studio.markdown :text="$version->snapshot['notes_md']" />@foreach($version->snapshot['rules'] as $savedRule)<article class="world-rule"><h3>{{ $savedRule['title'] }}</h3><span class="world-state">{{ $savedRule['decision']['name'] }}</span>@foreach(['statement_md'=>'Rule','conditions_md'=>'Conditions','consequences_md'=>'Consequences','exceptions_md'=>'Exceptions','reasoning_md'=>'Reasoning'] as $field=>$label)@if($savedRule[$field])<h4>{{ $label }}</h4><x-studio.markdown :text="$savedRule[$field]" />
@endif
 
@endforeach
</article>
@endforeach
 @foreach($version->snapshot['cases'] as $savedCase)<article class="world-rule"><h3>{{ $savedCase['title'] }}</h3><x-studio.markdown :text="$savedCase['situation_md']" />@if($savedCase['outcome_md'])<h4>Outcome</h4><x-studio.markdown :text="$savedCase['outcome_md']" />
@endif
 @if($savedCase['reasoning_md'])<h4>Reasoning</h4><x-studio.markdown :text="$savedCase['reasoning_md']" />
@endif
<div class="world-case-links">@foreach($savedCase['rules'] as $savedLinkedRule)<span>{{ $savedLinkedRule['title'] }}</span>
@endforeach
</div></article>
@endforeach
 
@endif
</flux:modal>
</div>
