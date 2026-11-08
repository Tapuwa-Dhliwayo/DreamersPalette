import { Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

Alpine.data('sandboxBoard', (initial) => ({
    nodes: Object.fromEntries(initial.map(node => [node.id, { ...node }])),
    moving: null,
    error: '',
    style(id) {
        const node = this.nodes[id];
        return `left:${node.x}px;top:${node.y}px`;
    },
    path(source, target, compact = false) {
        const from = this.nodes[source];
        const to = this.nodes[target];
        if (!from || !to) return '';
        // Lines join card centres; compact cards are 112px tall instead of 160px.
        const middle = compact ? 56 : 80;
        return `M ${from.x + 115} ${from.y + middle} L ${to.x + 115} ${to.y + middle}`;
    },
    start(event, id) {
        if (event.button !== 0 || this.moving) return;
        const node = this.nodes[id];
        const original = { ...node };
        this.moving = id;
        const startX = event.clientX, startY = event.clientY;
        const handle = event.currentTarget;
        handle.setPointerCapture(event.pointerId);
        const move = e => {
            node.x = Math.max(0, Math.min(10000, original.x + Math.round(e.clientX - startX)));
            node.y = Math.max(0, Math.min(10000, original.y + Math.round(e.clientY - startY)));
        };
        const clean = () => {
            handle.removeEventListener('pointermove', move);
            handle.removeEventListener('pointerup', finish);
            handle.removeEventListener('pointercancel', cancel);
            this.moving = null;
        };
        const finish = async () => {
            clean();
            if (node.x !== original.x || node.y !== original.y) await this.persist(node, original);
        };
        const cancel = () => { Object.assign(node, original); clean(); };
        handle.addEventListener('pointermove', move);
        handle.addEventListener('pointerup', finish);
        handle.addEventListener('pointercancel', cancel);
    },
    async nudge(id, x, y) {
        if (this.moving) return;
        const node = this.nodes[id];
        const original = { ...node };
        node.x = Math.max(0, Math.min(10000, node.x + x));
        node.y = Math.max(0, Math.min(10000, node.y + y));
        await this.persist(node, original);
    },
    async persist(node, original) {
        try {
            const saved = await this.$wire.moveIdea(node.id, original.revision, node.x, node.y);
            if (!saved?.revision) throw new Error('Move was not confirmed');
            node.revision = saved.revision;
            this.error = '';
        } catch {
            Object.assign(node, original);
            this.error = 'Move was not saved. Your piece returned to its previous position; refresh before retrying.';
            this.$dispatch('sandbox-move-failed', { message: this.error });
        }
    },
}));


const COMPACT_KEY = 'sandbox.compact-cards';

function storedCompactCards() {
    try {
        return window.localStorage.getItem(COMPACT_KEY) !== 'false';
    } catch {
        return true;
    }
}

Alpine.data('sandboxWorkspace', () => ({
    compact: storedCompactCards(),
    toggleCompact() {
        this.compact = !this.compact;
        try {
            window.localStorage.setItem(COMPACT_KEY, String(this.compact));
        } catch {
            // Remembering the card size is a convenience; the toggle still works without storage.
        }
    },
    // Tidy fills the board's visible width, then returns to the top so the new layout starts in view.
    async tidyBoard(wire) {
        const width = document.querySelector('.sandbox-board-scroll')?.clientWidth ?? 900;
        await wire.tidyBoard(this.compact, Math.max(1, Math.floor((width - 40) / 260)));
        document.querySelector('.sandbox-board-scroll')?.scrollTo({ top: 0, left: 0 });
    },
    openCitation(event, link, wire, exerciseId) {
        const target = new URL(link.href, window.location.href);
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || target.origin !== window.location.origin || target.pathname !== window.location.pathname || Number(target.searchParams.get('exercise')) !== exerciseId || !target.searchParams.has('reading')) return false;
        event.preventDefault();
        event.stopImmediatePropagation();
        if (this.dirty && !window.confirm('You have unsaved details. Discard them and open this source?')) return true;
        this.dirty = false;
        wire.openSource(Number(target.searchParams.get('reading')), Number(target.searchParams.get('from') || 1), Number(target.searchParams.get('to') || 12));
        return true;
    },
}));
