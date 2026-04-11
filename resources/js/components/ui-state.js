const resolveInputActionInput = (element) => {
    return element.closest('[data-slot=input-actions]')?.parentElement?.querySelector('input[data-control-id=input]') ?? null;
};

const tooltipComponent = ({ placement = 'top' } = {}) => ({
    show: false,
    placement,
    showTooltip() {
        this.show = true;
    },
    hideTooltip() {
        this.show = false;
    },
});

const inputRevealToggle = () => ({
    revealed: false,
    toggleReveal() {
        const input = resolveInputActionInput(this.$el);

        if (!input) {
            return;
        }

        this.revealed = !this.revealed;
        input.type = this.revealed ? 'text' : 'password';
    },
});

const inputCopyAction = ({ resetAfter = 2000 } = {}) => ({
    copied: false,
    resetTimeoutId: null,
    async doCopy() {
        try {
            const input = resolveInputActionInput(this.$el);

            if (!input?.value) {
                return;
            }

            await navigator.clipboard.writeText(input.value);
            this.copied = true;

            if (this.resetTimeoutId) {
                window.clearTimeout(this.resetTimeoutId);
            }

            this.resetTimeoutId = window.setTimeout(() => {
                this.copied = false;
                this.resetTimeoutId = null;
            }, resetAfter);
        } catch (error) {
            console.warn('Failed to copy to clipboard:', error);
        }
    },
});

const globalSearchOverlay = ({
    searchRoute,
    storageKey = 'screenbase.recent-searches',
    recentLimit = 5,
} = {}) => ({
    open: false,
    recent: [],
    storageKey,
    recentLimit,
    init() {
        try {
            const storedValue = JSON.parse(window.localStorage.getItem(this.storageKey) ?? '[]');

            this.recent = Array.isArray(storedValue)
                ? storedValue.slice(0, this.recentLimit)
                : [];
        } catch (error) {
            this.recent = [];
        }
    },
    persistRecent() {
        try {
            window.localStorage.setItem(this.storageKey, JSON.stringify(this.recent.slice(0, this.recentLimit)));
        } catch (error) {
            console.warn('Failed to persist recent searches:', error);
        }
    },
    storeRecent(value) {
        const query = String(value ?? '').trim();

        if (query.length < 2) {
            return;
        }

        this.recent = [query, ...this.recent.filter((item) => item.toLowerCase() !== query.toLowerCase())]
            .slice(0, this.recentLimit);
        this.persistRecent();
    },
    removeRecent(value) {
        this.recent = this.recent.filter((item) => item !== value);
        this.persistRecent();
    },
    goToSearch(value) {
        const query = String(value ?? '').trim();

        if (query.length === 0) {
            window.location.assign(searchRoute);

            return;
        }

        this.storeRecent(query);
        window.location.assign(`${searchRoute}?q=${encodeURIComponent(query)}`);
    },
});

const dropdownShell = ({ resetFocus = false } = {}) => ({
    open: false,
    resetFocus,
    toggle() {
        if (this.open) {
            this.close();

            return;
        }

        const firstFocusable = this.$focus.getFirst();

        if (firstFocusable) {
            this.$focus.focus(firstFocusable);
        }

        this.open = true;
    },
    isOpen() {
        return this.open;
    },
    close(focusAfter = null) {
        if (!this.open) {
            return;
        }

        this.open = false;

        if (focusAfter && this.resetFocus) {
            requestAnimationFrame(() => {
                const firstFocusable = this.$focus.getFirst();

                if (firstFocusable) {
                    this.$focus.focus(firstFocusable);
                }
            });
        }
    },
    handleFocusInOut(event) {
        const panel = this.$refs.panel;
        const button = this.$refs.button;
        const target = event.target;

        if (!panel || !button) {
            return;
        }

        if (panel.contains(target) || button.contains(target)) {
            return;
        }

        const lastFocusedElement = document.activeElement;

        if (this.shouldCloseDropdown(button, panel, lastFocusedElement)) {
            this.close(button);
        }
    },
    shouldCloseDropdown(button, panel, lastFocusedElement) {
        return (!button.contains(lastFocusedElement) && !panel.contains(lastFocusedElement))
            && (lastFocusedElement && (button.compareDocumentPosition(lastFocusedElement) & Node.DOCUMENT_POSITION_FOLLOWING));
    },
});

const dropdownSubmenu = () => ({
    isOpen: false,
    open() {
        this.isOpen = true;
    },
    close() {
        this.isOpen = false;
    },
    focusFirstPanelItem() {
        this.$nextTick(() => {
            const panel = this.$refs.panel;

            if (!panel) {
                return;
            }

            const firstFocusable = this.$focus.within(panel).getFirst();

            if (firstFocusable) {
                this.$focus.focus(firstFocusable);
            }
        });
    },
});

const dropdownToggleable = () => ({
    checked: false,
    init() {
        queueMicrotask(() => {
            this.syncFromInput();
        });
    },
    syncFromInput() {
        this.checked = Boolean(this.$refs.input?.checked);
    },
});

const popupVisibility = () => ({
    shown: false,
    init() {
        this.$nextTick(() => {
            const observer = new MutationObserver(() => {
                this.shown = Boolean(this.$el._x_isShown);
            });

            observer.observe(this.$el, { attributes: true, attributeFilter: ['style'] });
        });
    },
});

const popoverRoot = () => ({
    open: false,
    toggle() {
        this.open = !this.open;
    },
    show() {
        this.open = true;
    },
    hide() {
        this.open = false;
    },
});

const switchState = ({ checked = false } = {}) => ({
    _checked: checked,
    toggle() {
        this._checked = !this._checked;
    },
});

const textareaAutosize = ({ initialHeight } = {}) => ({
    initialHeight: `${initialHeight ?? 5.25}rem`,
    state: '',
    resizeObserver: null,
    init() {
        this.$nextTick(() => {
            this.state = this.$root?._x_model?.get() ?? this.$el?.value ?? '';
        });

        this.$watch('state', (value) => {
            this.$root?._x_model?.set(value);

            const wireModel = this.$root?.getAttributeNames().find((attributeName) => attributeName.startsWith('wire:model'));

            if (! this.$wire || ! wireModel) {
                return;
            }

            const property = this.$root.getAttribute(wireModel);

            this.$wire.set(property, value, wireModel.includes('.live'));
        });

        if (! this.$el) {
            return;
        }

        this.$el.style.height = this.initialHeight;

        this.resizeObserver = new ResizeObserver(() => {
            this.resize();
        });

        this.resizeObserver.observe(this.$el);
    },
    resize() {
        if (! this.$el) {
            return;
        }

        this.$el.style.height = 'auto';

        if (this.$el.scrollHeight < Number.parseFloat(this.initialHeight)) {
            this.$el.style.height = this.initialHeight;

            return;
        }

        this.$el.style.height = `${this.$el.scrollHeight}px`;
    },
    destroy() {
        this.resizeObserver?.disconnect();
    },
});

const accordionRoot = () => ({
    active: null,
});

const accordionItem = ({ expanded = false, disabled = false } = {}) => ({
    id: null,
    init() {
        this.id = this.$id('accordion');

        if (expanded) {
            this.active = this.id;
        }
    },
    toggle() {
        if (disabled) {
            return;
        }

        this.isVisible = !this.isVisible;
    },
    get isVisible() {
        return this.active === this.id && !disabled;
    },
    set isVisible(value) {
        this.active = value ? this.id : null;
    },
    get triggerId() {
        return `${this.id}-trigger`;
    },
    get panelId() {
        return `${this.id}-panel`;
    },
});

export default function registerUiStateComponents(Alpine) {
    Alpine.data('accordionItem', accordionItem);
    Alpine.data('accordionRoot', accordionRoot);
    Alpine.data('dropdownShell', dropdownShell);
    Alpine.data('dropdownSubmenu', dropdownSubmenu);
    Alpine.data('dropdownToggleable', dropdownToggleable);
    Alpine.data('globalSearchOverlay', globalSearchOverlay);
    Alpine.data('inputCopyAction', inputCopyAction);
    Alpine.data('inputRevealToggle', inputRevealToggle);
    Alpine.data('popoverRoot', popoverRoot);
    Alpine.data('popupVisibility', popupVisibility);
    Alpine.data('switchState', switchState);
    Alpine.data('textareaAutosize', textareaAutosize);
    Alpine.data('tooltipComponent', tooltipComponent);
}
