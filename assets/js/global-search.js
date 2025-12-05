/**
 * Recherche globale avec raccourci clavier Ctrl+K (ou Cmd+K sur Mac)
 */

class GlobalSearch {
    constructor() {
        this.modal = null;
        this.searchInput = null;
        this.resultsContainer = null;
        this.debounceTimer = null;
        this.currentQuery = '';
        this.init();
    }

    init() {
        this.createModal();
        this.bindKeyboardShortcut();
        this.bindEvents();
    }

    createModal() {
        const modalHtml = `
            <div class="modal fade" id="globalSearchModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header border-0 pb-0">
                            <div class="w-100">
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent border-0">
                                        <i class="bx bx-search-alt"></i>
                                    </span>
                                    <input 
                                        type="text" 
                                        class="form-control border-0 shadow-none" 
                                        id="globalSearchInput"
                                        placeholder="Rechercher un projet, client, contributeur, devis..."
                                        autofocus
                                    >
                                    <span class="input-group-text bg-transparent border-0 text-muted">
                                        <small>Ctrl+K</small>
                                    </span>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="globalSearchResults">
                                <div class="text-center text-muted py-5">
                                    <i class="bx bx-search-alt font-size-24 mb-2"></i>
                                    <p class="mb-0">Tapez au moins 2 caractères pour rechercher</p>
                                </div>
                            </div>
                            <div id="globalSearchLoading" class="text-center py-5 d-none">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        this.modal = new bootstrap.Modal(document.getElementById('globalSearchModal'));
        this.searchInput = document.getElementById('globalSearchInput');
        this.resultsContainer = document.getElementById('globalSearchResults');
        this.loadingIndicator = document.getElementById('globalSearchLoading');
    }

    bindKeyboardShortcut() {
        document.addEventListener('keydown', (e) => {
            // Ctrl+K (Windows/Linux) ou Cmd+K (Mac)
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                this.openModal();
            }

            // ESC pour fermer
            if (e.key === 'Escape' && document.getElementById('globalSearchModal').classList.contains('show')) {
                this.closeModal();
            }
        });
    }

    bindEvents() {
        // Recherche en temps réel
        this.searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                this.showEmptyState();
                return;
            }

            // Debounce de 300ms
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.performSearch(query);
            }, 300);
        });

        // Navigation au clavier dans les résultats (TODO: implémenter si besoin)
        this.searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                // TODO: Navigation avec flèches
            }
        });
    }

    openModal() {
        this.modal.show();
        setTimeout(() => {
            this.searchInput.focus();
            this.searchInput.select();
        }, 100);
    }

    closeModal() {
        this.modal.hide();
        this.searchInput.value = '';
        this.showEmptyState();
    }

    showEmptyState() {
        this.resultsContainer.innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="bx bx-search-alt font-size-24 mb-2"></i>
                <p class="mb-0">Tapez au moins 2 caractères pour rechercher</p>
            </div>
        `;
    }

    showLoading() {
        this.resultsContainer.classList.add('d-none');
        this.loadingIndicator.classList.remove('d-none');
    }

    hideLoading() {
        this.resultsContainer.classList.remove('d-none');
        this.loadingIndicator.classList.add('d-none');
    }

    async performSearch(query) {
        this.currentQuery = query;
        this.showLoading();

        try {
            const response = await fetch(`/api/search?q=${encodeURIComponent(query)}`);
            const results = await response.json();
            
            // Vérifier si la requête est toujours d'actualité
            if (query !== this.currentQuery) {
                return;
            }

            this.displayResults(results);
        } catch (error) {
            console.error('Erreur de recherche:', error);
            this.resultsContainer.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <i class="bx bx-error-circle me-2"></i>
                    Une erreur est survenue lors de la recherche
                </div>
            `;
        } finally {
            this.hideLoading();
        }
    }

    displayResults(results) {
        if (Object.keys(results).length === 0) {
            this.resultsContainer.innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="bx bx-search-alt-2 font-size-24 mb-2"></i>
                    <p class="mb-0">Aucun résultat trouvé</p>
                </div>
            `;
            return;
        }

        const typeLabels = {
            projects: { label: 'Projets', icon: 'bx-briefcase-alt-2', color: 'primary' },
            clients: { label: 'Clients', icon: 'bx-building-house', color: 'success' },
            contributors: { label: 'Contributeurs', icon: 'bx-user', color: 'info' },
            orders: { label: 'Devis', icon: 'bx-file', color: 'warning' }
        };

        let html = '';

        for (const [type, items] of Object.entries(results)) {
            const config = typeLabels[type] || { label: type, icon: 'bx-circle', color: 'secondary' };
            
            html += `
                <div class="result-category mb-4">
                    <h6 class="text-muted text-uppercase font-size-12 mb-3">
                        <i class="bx ${config.icon} me-2"></i>
                        ${config.label}
                        <span class="badge badge-soft-${config.color} ms-2">${items.length}</span>
                    </h6>
                    <div class="list-group list-group-flush">
            `;

            items.forEach(item => {
                html += `
                    <a href="${item.url}" class="list-group-item list-group-item-action border-0 rounded mb-1">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <div class="avatar-xs">
                                    <span class="avatar-title rounded-circle bg-light text-${config.color}">
                                        <i class="bx ${config.icon}"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">${this.escapeHtml(item.name)}</h6>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="bx bx-right-arrow-alt font-size-18 text-muted"></i>
                            </div>
                        </div>
                    </a>
                `;
            });

            html += `
                    </div>
                </div>
            `;
        }

        this.resultsContainer.innerHTML = html;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialisation au chargement du DOM
document.addEventListener('DOMContentLoaded', () => {
    new GlobalSearch();
});

export default GlobalSearch;
