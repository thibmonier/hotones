import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['form', 'search', 'resetButton'];

    connect() {
        this._debounceTimer = null;
    }

    disconnect() {
        if (this._debounceTimer) {
            clearTimeout(this._debounceTimer);
        }
    }

    /**
     * Submit filters on select change (instant).
     */
    filterChanged() {
        this.formTarget.requestSubmit();
    }

    /**
     * Submit filters on search input (debounced 300ms).
     */
    searchInput() {
        if (this._debounceTimer) {
            clearTimeout(this._debounceTimer);
        }
        this._debounceTimer = setTimeout(() => {
            this.formTarget.requestSubmit();
        }, 300);
    }

    /**
     * Reset all filters by navigating to clean URL.
     */
    reset(event) {
        event.preventDefault();
        window.location.href = this.element.dataset.resetUrl;
    }
}
