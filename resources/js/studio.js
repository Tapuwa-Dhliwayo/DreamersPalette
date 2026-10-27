import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import { getGrammarSuggestions, getThesaurusSuggestions, getDefinitions, getSpellingSuggestions } from './services/writingAssistantService';

Alpine.data('manuscriptEditor', (initial) => ({
    chapterId: null, chapterNumber: 0, canWrite: initial.can_write, manuscriptId: initial.manuscript_id, userId: initial.user_id,
    title: '', content: '', summary: '', revision: 1, draftNumber: 1, manuscriptRevision: initial.manuscript_revision,
    dirty: false, busy: false, undoing: false, conflict: false, error: '', status: 'Saved to server',
    mode: 'write', focus: false, chaptersOpen: false, previewHtml: '',
    worldCatalogue: [], selection: '', selectionStart: 0, assistOpen: false, assistStatus: '', suggestions: [],
    recovery: null, showRecovery: false, serverText: '', sequence: 0,
    timer: null, previewTimer: null, savingPromise: null, pendingSave: null, assistSequence: 0, previewSequence: 0, checkpointTimer: null,
    init() {
        this.load(initial);
        this.checkpointTimer = setInterval(() => { if (this.dirty && !this.busy) this.flush(); }, 15000);
        this.beforeLeave = (event) => {
            if (this.dirty || this.recovery || this.pendingSave) {
                this.checkpoint(); event.preventDefault(); event.returnValue = '';
            }
        };
        window.addEventListener('beforeunload', this.beforeLeave);
        this.escapeFocus = (event) => { if (event.key === 'Escape') this.focus = false; };
        window.addEventListener('keydown', this.escapeFocus);
    },
    destroy() {
        clearTimeout(this.timer); clearTimeout(this.previewTimer); clearInterval(this.checkpointTimer);
        window.removeEventListener('beforeunload', this.beforeLeave);
        window.removeEventListener('keydown', this.escapeFocus);
    },
    recoveryKey() { return `dreamers:studio:${this.userId}:${this.manuscriptId}:${this.chapterId}:draft-${this.draftNumber}`; },
    load(chapter) {
        clearTimeout(this.timer);
        this.worldCatalogue = chapter.world_catalogue || [];
        this.chapterId = chapter.id; this.chapterNumber = chapter.chapter_number; this.title = chapter.title; this.content = chapter.content_md;
        this.summary = chapter.summary_md; this.revision = chapter.revision; this.draftNumber = chapter.draft_number; this.manuscriptRevision = chapter.manuscript_revision;
        this.dirty = false; this.conflict = false; this.pendingSave = null; this.error = ''; this.recovery = null;
        this.previewSequence++; this.sequence = 0; this.assistOpen = false; this.assistSequence++;
        this.status = 'Saved to server'; this.serverText = this.content;
        if (!this.chapterId) return;
        try {
            const local = JSON.parse(localStorage.getItem(this.recoveryKey()) || 'null');
            if (local && typeof local.content_md === 'string' && typeof local.title === 'string' && typeof local.summary_md === 'string') {
                if (local.content_md !== this.content || local.title !== this.title || local.summary_md !== this.summary) {
                    this.recovery = local; this.status = 'Local recovery available';
                } else localStorage.removeItem(this.recoveryKey());
            }
        } catch { this.status = 'Device recovery is unavailable. Save to the server regularly.'; }
        this.updatePreview();
    },
    changed() {
        if (!this.canWrite) return;
        this.dirty = true; this.sequence++; this.status = 'Changes on this device';
        this.assistOpen = false; this.assistSequence++; this.selection = '';
        this.checkpoint(); clearTimeout(this.timer);
        if (!this.conflict && !this.recovery) this.timer = setTimeout(() => this.flush(), 1500);
        clearTimeout(this.previewTimer);
        if (this.mode !== 'write') this.previewTimer = setTimeout(() => this.updatePreview(), 600);
    },
    checkpoint() {
        if (!this.chapterId || this.recovery) return;
        try {
            localStorage.setItem(this.recoveryKey(), JSON.stringify({ title: this.title, content_md: this.content,
                summary_md: this.summary, revision: this.revision, saved_at: new Date().toISOString() }));
        } catch { this.error = 'Device recovery could not be stored. Save to the server or download your local copy before leaving.'; }
    },
    async flush() {
        clearTimeout(this.timer);
        if (this.conflict || this.recovery) return false;
        if (this.savingPromise) return this.savingPromise;
        this.savingPromise = this.saveQueue();
        try { return await this.savingPromise; } finally { this.savingPromise = null; }
    },
    async saveQueue() {
        this.busy = true;
        try {
            while (this.dirty || this.pendingSave) {
                this.pendingSave ||= { chapterId: this.chapterId, sequence: this.sequence, title: this.title,
                    content_md: this.content, summary_md: this.summary, revision: this.revision,
                    draft_number: this.draftNumber, request_uuid: crypto.randomUUID() };
                const request = this.pendingSave;
                this.status = 'Saving…';
                const response = await this.$wire.saveChapter(request.chapterId, request);
                if (!response.ok) {
                    this.conflict = Boolean(response.conflict); this.error = response.message;
                    this.status = this.conflict ? 'Review newer server writing' : 'Not saved to server';
                    // Validation failures have no accepted write; a corrected buffer gets a fresh request.
                    this.pendingSave = null; return false;
                }
                if (response.draft_number !== this.draftNumber) {
                    this.conflict = true; this.error = 'The draft was finished in another session. Download your local work, then reload.';
                    return false;
                }
                this.worldCatalogue = response.world_catalogue || this.worldCatalogue;
                this.revision = response.revision; this.manuscriptRevision = response.manuscript_revision; this.pendingSave = null;
                this.dirty = request.sequence !== this.sequence;
                if (!this.dirty) {
                    try { localStorage.removeItem(this.recoveryKey()); } catch { /* Server acknowledgement remains authoritative. */ }
                    this.error = ''; this.status = 'Saved to server'; this.serverText = this.content;
                } else this.checkpoint();
            }
            return true;
        } catch {
            this.checkpoint(); this.error = 'The save could not be confirmed. Your local writing is retained. Check your connection or session, then retry.';
            this.status = 'Not saved to server'; return false;
        } finally { this.busy = false; }
    },
    async switchChapter(id) {
        if (id === this.chapterId || !(await this.flush())) return;
        try { this.load(await this.$wire.selectChapter(id)); this.chaptersOpen = false; }
        catch { this.error = 'That chapter could not be opened. Your current writing is still here.'; }
    },
    async newChapter() {
        if (!(await this.flush())) return;
        try { this.load(await this.$wire.addChapter(this.draftNumber, this.manuscriptRevision)); this.chaptersOpen = false; }
        catch { this.error = 'A chapter could not be created. Refresh if another session finished this draft.'; }
    },
    async showTab(tab) { if (this.undoing) return; if (await this.flush()) await this.$wire.changeTab(tab); },
    async finish() {
        if (!(await this.flush())) return;
        try { await this.$wire.beginFinish(this.draftNumber); }
        catch { this.error = 'The draft could not be opened for finishing. Save and refresh before trying again.'; }
    },
    async undoFinish(id) {
        if (this.undoing || !(await this.flush())) return;
        this.undoing = true;
        try {
            const response = await this.$wire.undoFinish(id, this.manuscriptRevision);
            if (!response.ok) { this.error = response.message; return; }
            this.load(response.chapter);
        } catch { this.error = 'The undo could not be confirmed. Refresh to check the current draft before trying again.'; }
        finally { this.undoing = false; }
    },
    async move(id, direction) {
        // A save increments the manuscript revision, so refresh its current token after checkpointing.
        if (!(await this.flush())) return;
        try { this.load(await this.$wire.moveChapter(id, direction, this.manuscriptRevision)); }
        catch { this.error = 'The chapter order changed. Refresh before trying again.'; }
    },
    async guardLink(event) {
        if (!this.dirty && !this.recovery && !this.pendingSave) return;
        event.preventDefault(); const target = event.currentTarget.href;
        if (await this.flush()) window.location.assign(target);
    },
    restoreRecovery() {
        if (!this.recovery) return;
        this.title = this.recovery.title; this.content = this.recovery.content_md; this.summary = this.recovery.summary_md;
        this.recovery = null; this.changed();
    },
    discardRecovery() {
        this.recovery = null; try { localStorage.removeItem(this.recoveryKey()); } catch { /* No server content is changed. */ }
        this.status = 'Saved to server';
    },
    async compareServer() {
        try {
            const server = await this.$wire.selectChapter(this.chapterId);
            this.serverText = server.content_md; this.showRecovery = true;
        } catch { this.error = 'The server copy could not be loaded. Download your local writing before leaving.'; }
    },
    downloadLocal() {
        const local = this.recovery || { title: this.title, content_md: this.content, summary_md: this.summary };
        const blob = new Blob([`# ${local.title}\n\n${local.content_md}\n\n---\n\nPrivate summary:\n${local.summary_md}`], { type: 'text/markdown;charset=utf-8' });
        const url = URL.createObjectURL(blob); const link = document.createElement('a');
        link.href = url; link.download = `local-chapter-${this.chapterId || 'new'}.md`; link.click();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    },
    wordCount() { return this.content.trim() ? this.content.trim().split(/\s+/u).length : 0; },
    async setMode(mode) { this.mode = mode; if (mode !== 'write') await this.updatePreview(); },
    async updatePreview() {
        if (this.mode === 'write') return;
        const sequence = ++this.previewSequence;
        try {
            const html = await this.$wire.previewMarkdown(this.content);
            if (sequence === this.previewSequence) this.previewHtml = this.highlightReferences(html);
        } catch { this.error = 'Preview is unavailable. You can continue writing and saving.'; }
    },
    async linkWorld() {
        if (await this.flush()) await this.$wire.beginWorldLink();
    },
    async referenceBrowser(useSelection = false, name = '', key = '') {
        const source = this.content;
        const wording = useSelection ? this.selection : '';
        const start = this.selectionStart;
        if (!(await this.flush())) return;
        if (useSelection && this.content !== source) { this.error = 'The passage changed. Select it again.'; return; }
        const context = useSelection ? {
            chapter_id: this.chapterId, source_revision: this.revision, draft_number: this.draftNumber,
            wording, prefix: Array.from(source.slice(0, start)).slice(-64).join(''),
            suffix: Array.from(source.slice(start + wording.length)).slice(0, 64).join(''),
        } : null;
        await this.$wire.openReferences(context);
        if (name) this.$wire.referenceSearch = name;
        if (key) await this.$wire.inspectReference(key);
    },
    inspectPreviewReference(event) {
        const reference = event.target.closest('[data-world-reference]');
        if (reference) this.referenceBrowser(false, reference.textContent, reference.dataset.worldReference);
    },
    highlightReferences(html) {
        if (!this.worldCatalogue.length) return html;
        const terms = new Map();
        for (const entry of this.worldCatalogue) {
            for (const name of [entry.name, ...entry.aliases]) {
                if (!name || name.length > 255) continue;
                const term = name.toLocaleLowerCase();
                terms.set(term, [...new Set([...(terms.get(term) || []), entry.key])]);
            }
        }
        const escaped = [...terms.keys()].sort((a,b) => b.length-a.length).slice(0, 1500).map(term => Array.from(term).map(letter => '^$.*+?()[]{}|'.includes(letter) || letter.charCodeAt(0) === 92 ? String.fromCharCode(92) + letter : letter).join(''));
        if (!escaped.length) return html;
        const expression = new RegExp(String.raw`(?<![\p{L}\p{N}_])(${escaped.join('|')})(?![\p{L}\p{N}_])`, 'giu');
        const template = document.createElement('template'); template.innerHTML = html;
        const walker = document.createTreeWalker(template.content, NodeFilter.SHOW_TEXT);
        const nodes = [];
        while (walker.nextNode()) if (!walker.currentNode.parentElement?.closest('a,code,pre,button')) nodes.push(walker.currentNode);
        for (const node of nodes) {
            expression.lastIndex = 0;
            const matches = [...node.textContent.matchAll(expression)];
            if (!matches.length) continue;
            const fragment = document.createDocumentFragment(); let offset = 0;
            for (const match of matches) {
                fragment.append(document.createTextNode(node.textContent.slice(offset, match.index)));
                const button = document.createElement('button'); button.type = 'button'; button.className = 'studio-world-reference';
                button.textContent = match[0]; const candidates = terms.get(match[0].toLocaleLowerCase()) || [];
                button.dataset.worldReference = candidates.length === 1 ? candidates[0] : '';
                button.title = candidates.length === 1 ? 'Open world reference' : 'Choose between matching world references';
                fragment.append(button); offset = match.index + match[0].length;
            }
            fragment.append(document.createTextNode(node.textContent.slice(offset))); node.replaceWith(fragment);
        }
        return template.innerHTML;
    },
    async archiveChapter(id, archive) {
        if (!(await this.flush())) return;
        this.load(await this.$wire.archiveChapter(id, archive, this.draftNumber, this.manuscriptRevision));
    },
    wrap(marker) {
        const input = this.$refs.writing; if (!input) return;
        const start = input.selectionStart, end = input.selectionEnd;
        input.focus(); document.execCommand('insertText', false, marker + this.content.slice(start, end) + marker);
        this.content = input.value; input.setSelectionRange(start + marker.length, end + marker.length); this.changed();
    },
    prefix(marker) {
        const input = this.$refs.writing; if (!input) return;
        const start = this.content.lastIndexOf('\n', Math.max(0, input.selectionStart - 1)) + 1;
        input.focus(); input.setSelectionRange(start, start); document.execCommand('insertText', false, marker);
        this.content = input.value; this.changed();
    },
    shortcut(event) {
        if (!(event.ctrlKey || event.metaKey)) return;
        const key = event.key.toLowerCase();
        if (key === 's') { event.preventDefault(); this.flush(); }
        if (key === 'b' || key === 'i') { event.preventDefault(); this.wrap(key === 'b' ? '**' : '*'); }
    },
    captureSelection() {
        const input = this.$refs.writing; if (!input) return;
        const raw = input.value.slice(input.selectionStart, input.selectionEnd);
        this.selection = raw.trim(); this.selectionStart = input.selectionStart + raw.length - raw.trimStart().length;
    },
    openAssist() {
        if (!this.selection) return;
        if (this.selection.length > 1000) { this.error = 'Select up to 1,000 characters for writing assistance.'; return; }
        this.assistSnapshot = this.content; this.assistChapter = this.chapterId; this.assistSelection = this.selection;
        this.assistStart = this.selectionStart; this.assistOpen = true; this.suggestions = [];
        this.assistStatus = 'Choose a tool for the selected writing.';
    },
    async lookup(tool) {
        const request = ++this.assistSequence; this.assistStatus = 'Looking at your selection…'; this.suggestions = [];
        try {
            let suggestions;
            if (tool === 'grammar') suggestions = await getGrammarSuggestions(this.assistSelection);
            else if (tool === 'synonyms') {
                const words = await getThesaurusSuggestions(this.assistSelection);
                suggestions = [...words.synonyms, ...words.antonyms, ...words.related];
            } else if (tool === 'spelling') suggestions = await getSpellingSuggestions(this.assistSelection);
            else suggestions = await getDefinitions(this.assistSelection);
            if (request !== this.assistSequence || !this.assistOpen) return;
            this.suggestions = suggestions; this.assistStatus = suggestions.length ? 'Choose a replacement, or keep your wording.' : 'No suggestions for this selection.';
        } catch { if (request === this.assistSequence) this.assistStatus = 'This tool is unavailable right now. Your writing has not changed.'; }
    },
    replaceSuggestion(replacement, offset = 0, length = this.assistSelection.length) {
        if (this.content !== this.assistSnapshot || this.chapterId !== this.assistChapter) {
            this.assistStatus = 'The writing changed. Select the passage again before replacing it.'; return;
        }
        const start = this.assistStart + offset, end = start + length;
        const input = this.$refs.writing;
        input.focus(); input.setSelectionRange(start, end); document.execCommand('insertText', false, replacement);
        this.content = input.value; this.assistOpen = false; this.changed();
    },
}));

Alpine.data('alignmentMarker', (initial) => ({
    x: initial.x, y: initial.y, moving: false, dragged: false, busy: false,
    startX: 0, startY: 0, originalX: initial.x, originalY: initial.y,
    start(event) {
        if (event.button !== 0 || this.busy) return;
        this.moving = true; this.dragged = false;
        this.startX = event.clientX; this.startY = event.clientY;
        this.originalX = this.x; this.originalY = this.y;
        event.currentTarget.setPointerCapture(event.pointerId);
    },
    move(event) {
        if (!this.moving) return;
        if (Math.hypot(event.clientX - this.startX, event.clientY - this.startY) < 4 && !this.dragged) return;
        this.dragged = true;
        const bounds = this.$el.parentElement.getBoundingClientRect();
        this.x = Math.max(-100, Math.min(100, Math.round((event.clientX - bounds.left) / bounds.width * 200 - 100)));
        this.y = Math.max(-100, Math.min(100, Math.round(100 - (event.clientY - bounds.top) / bounds.height * 200)));
    },
    async finish() {
        if (!this.moving) return;
        this.moving = false;
        if (this.dragged) await this.persist();
    },
    cancel() {
        this.moving = false; this.x = this.originalX; this.y = this.originalY;
    },
    async nudge(dx, dy) {
        if (this.busy) return;
        this.originalX = this.x; this.originalY = this.y;
        this.x = Math.max(-100, Math.min(100, this.x + dx));
        this.y = Math.max(-100, Math.min(100, this.y + dy));
        await this.persist();
    },
    async persist() {
        this.busy = true;
        try {
            const saved = await this.$wire.movePlacement(initial.id, this.x, this.y, initial.revision);
            if (saved !== true) this.cancel();
            if (saved === true) {
                this.$dispatch('alignment-moved');
                this.$nextTick(() => document.querySelector(`[data-alignment-id="${initial.id}"]`)?.focus({ preventScroll: true }));
            }
        } catch {
            this.cancel();
            this.$dispatch('alignment-move-failed');
        } finally {
            this.busy = false;
        }
    },
}));

Livewire.start();
