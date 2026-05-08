document.addEventListener('alpine:init', () => {
    Alpine.store('expandableTable', {
        rows: {},

        toggle(key) {
            this.rows[key] = !this.rows[key];
        },

        isExpanded(key) {
            return this.rows[key] ?? false;
        },
    });
});
