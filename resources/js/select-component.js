/**
 * Reusable custom Select — Alpine.js data factory.
 *
 * Registered on `alpine:init` (Alpine ships inside Livewire 3, so we must hook
 * that event rather than calling Alpine.data() at import time).
 *
 * Pairs with: resources/views/components/forms/select.blade.php
 *
 * Responsibilities:
 *  - Single & multi select state
 *  - Local filtering (in-browser) OR remote search (debounced Livewire call)
 *  - Keyboard navigation (Up/Down/Enter/Escape/Tab/Backspace)
 *  - Open/close + outside-click + escape handling
 *  - Two-way sync with a Livewire `wire:model` entangled property
 *
 * No jQuery. No third-party select library.
 */
document.addEventListener("alpine:init", () => {
    window.Alpine.data("selectComponent", (config = {}) => ({
        // ---- Configuration (from Blade) -------------------------------------
        multiple: config.multiple ?? false,
        liveSearch: config.liveSearch ?? false,
        searchMethod: config.searchMethod ?? null,
        placeholder: config.placeholder ?? "Select...",
        searchPlaceholder: config.searchPlaceholder ?? "Search...",
        noResultsText: config.noResultsText ?? "No results found",
        loadingText: config.loadingText ?? "Loading...",
        disabled: config.disabled ?? false,
        minSearchChars: config.minSearchChars ?? 0,
        debounceMs: config.debounceMs ?? 300,
        closeOnSelect: config.closeOnSelect ?? !(config.multiple ?? false),

        // ---- State ----------------------------------------------------------
        // `selected` is the Livewire-entangled value: scalar (single) | array (multi).
        // Bound in Blade via x-modelable / entangle.
        selected: config.multiple ? [] : null,
        // Full option pool. For local mode this is the preloaded list; for remote
        // mode it holds the latest fetched results plus any "known" selected labels.
        options: config.options ?? [],
        // Cache of value => option so selected badges keep their label even when
        // the option leaves the current (remote) result set.
        labelCache: {},

        open: false,
        search: "",
        loading: false,
        highlighted: -1,
        _debounceTimer: null,
        _requestToken: 0,

        // ---- Lifecycle ------------------------------------------------------
        init() {
            // Seed the label cache from preloaded options.
            this.cacheOptions(this.options);

            // Seed cache from any pre-selected values whose option we already have.
            this.normalizeSelected();

            // When Livewire morphs and the bound value changes server-side,
            // Alpine's reactivity on `selected` keeps the UI in sync automatically
            // because `selected` is entangled.
        },

        // ---- Derived --------------------------------------------------------
        get isMultiple() {
            return this.multiple;
        },

        get hasSelection() {
            return this.isMultiple
                ? Array.isArray(this.selected) && this.selected.length > 0
                : this.selected !== null && this.selected !== "" && this.selected !== undefined;
        },

        /** Options shown in the dropdown after filtering. */
        get visibleOptions() {
            if (this.liveSearch) {
                // Remote mode: server already filtered; show as-is.
                return this.options;
            }

            const term = this.search.trim().toLowerCase();
            if (term === "") {
                return this.options;
            }

            return this.options.filter((o) => {
                const label = (o.label ?? "").toString().toLowerCase();
                const subtitle = (o.subtitle ?? "").toString().toLowerCase();
                return label.includes(term) || subtitle.includes(term);
            });
        },

        get showEmptyState() {
            return !this.loading && this.visibleOptions.length === 0;
        },

        /** Resolved option objects for the current selection (for badges/label). */
        get selectedOptions() {
            const values = this.isMultiple
                ? this.selected ?? []
                : this.hasSelection
                  ? [this.selected]
                  : [];

            return values.map((v) => this.resolveOption(v));
        },

        get displayLabel() {
            if (!this.hasSelection) return this.placeholder;
            const opt = this.resolveOption(this.selected);
            return opt?.label ?? String(this.selected);
        },

        // ---- Selection ------------------------------------------------------
        isSelected(value) {
            if (this.isMultiple) {
                return (this.selected ?? []).some((v) => this.looseEq(v, value));
            }
            return this.looseEq(this.selected, value);
        },

        toggle(option) {
            if (option?.disabled || this.disabled) return;

            this.cacheOptions([option]);

            if (this.isMultiple) {
                const exists = (this.selected ?? []).some((v) => this.looseEq(v, option.value));
                this.selected = exists
                    ? this.selected.filter((v) => !this.looseEq(v, option.value))
                    : [...(this.selected ?? []), option.value];
            } else {
                this.selected = option.value;
            }

            if (this.closeOnSelect) {
                this.closeDropdown();
            } else {
                // Keep open for multi; reset search to surface remaining options.
                this.search = "";
                if (this.liveSearch) this.options = [];
            }
        },

        remove(value) {
            if (this.disabled) return;
            if (this.isMultiple) {
                this.selected = (this.selected ?? []).filter((v) => !this.looseEq(v, value));
            } else {
                this.selected = null;
            }
        },

        clearAll() {
            if (this.disabled) return;
            this.selected = this.isMultiple ? [] : null;
        },

        // ---- Dropdown -------------------------------------------------------
        toggleDropdown() {
            if (this.disabled) return;
            this.open ? this.closeDropdown() : this.openDropdown();
        },

        openDropdown() {
            if (this.disabled) return;
            this.open = true;
            this.highlighted = -1;
            this.$nextTick(() => {
                const input = this.$refs.searchInput;
                if (input) input.focus();
            });
        },

        closeDropdown() {
            this.open = false;
            this.search = "";
            this.highlighted = -1;
        },

        // ---- Search ---------------------------------------------------------
        onSearchInput() {
            this.highlighted = -1;

            if (!this.liveSearch) return; // local mode filters reactively

            const term = this.search.trim();
            clearTimeout(this._debounceTimer);

            if (term.length < this.minSearchChars) {
                this.options = [];
                this.loading = false;
                return;
            }

            this.loading = true;
            this._debounceTimer = setTimeout(() => this.fetchRemote(term), this.debounceMs);
        },

        async fetchRemote(term) {
            if (!this.searchMethod) {
                this.loading = false;
                return;
            }

            const token = ++this._requestToken;
            try {
                // Calls the Livewire method named in `searchMethod`, returns array.
                const results = await this.$wire.call(this.searchMethod, term);

                // Drop stale responses (out-of-order debounced requests).
                if (token !== this._requestToken) return;

                this.options = Array.isArray(results) ? results : [];
                this.cacheOptions(this.options);
            } catch (e) {
                if (token === this._requestToken) this.options = [];
                // Surface failures in dev without breaking the UI.
                console.error("[x-forms.select] remote search failed:", e);
            } finally {
                if (token === this._requestToken) this.loading = false;
            }
        },

        // ---- Keyboard -------------------------------------------------------
        onKeydown(e) {
            if (this.disabled) return;

            switch (e.key) {
                case "ArrowDown":
                    e.preventDefault();
                    if (!this.open) return this.openDropdown();
                    this.move(1);
                    break;
                case "ArrowUp":
                    e.preventDefault();
                    if (!this.open) return this.openDropdown();
                    this.move(-1);
                    break;
                case "Enter":
                    e.preventDefault();
                    if (!this.open) return this.openDropdown();
                    this.chooseHighlighted();
                    break;
                case "Escape":
                    if (this.open) {
                        e.preventDefault();
                        this.closeDropdown();
                    }
                    break;
                case "Tab":
                    this.closeDropdown();
                    break;
                case "Backspace":
                    // In multi mode, backspace on an empty search removes last badge.
                    if (this.isMultiple && this.search === "" && this.hasSelection) {
                        const last = this.selected[this.selected.length - 1];
                        this.remove(last);
                    }
                    break;
            }
        },

        move(dir) {
            const opts = this.visibleOptions;
            if (opts.length === 0) return;

            let next = this.highlighted;
            for (let i = 0; i < opts.length; i++) {
                next = (next + dir + opts.length) % opts.length;
                if (!opts[next]?.disabled) break;
            }
            this.highlighted = next;
            this.scrollHighlightedIntoView();
        },

        chooseHighlighted() {
            const opt = this.visibleOptions[this.highlighted];
            if (opt) this.toggle(opt);
        },

        scrollHighlightedIntoView() {
            this.$nextTick(() => {
                const list = this.$refs.optionsList;
                if (!list) return;
                const el = list.querySelector(`[data-index="${this.highlighted}"]`);
                if (el) el.scrollIntoView({ block: "nearest" });
            });
        },

        // ---- Helpers --------------------------------------------------------
        /** Loose equality so "1" (string) and 1 (int) match across PHP/JS. */
        looseEq(a, b) {
            return String(a) === String(b);
        },

        resolveOption(value) {
            const fromOptions = this.options.find((o) => this.looseEq(o.value, value));
            if (fromOptions) return fromOptions;

            const key = String(value);
            if (this.labelCache[key]) return this.labelCache[key];

            return { value, label: String(value) };
        },

        cacheOptions(options) {
            (options ?? []).forEach((o) => {
                if (o && o.value !== undefined && o.value !== null) {
                    this.labelCache[String(o.value)] = o;
                }
            });
        },

        normalizeSelected() {
            if (this.isMultiple && !Array.isArray(this.selected)) {
                this.selected = this.selected == null ? [] : [this.selected];
            }
        },
    }));
});
