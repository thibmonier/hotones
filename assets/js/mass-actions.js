/**
 * Mass Actions JavaScript Module
 * Gère les actions en masse sur les tableaux de données (sélection, suppression, export)
 */

document.addEventListener('DOMContentLoaded', function() {
    // Éléments du DOM
    const selectAllCheckbox = document.getElementById('selectAll');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const massActionBar = document.getElementById('massActionBar');
    const selectedCountSpan = document.getElementById('selectedCount');
    const massDeleteBtn = document.getElementById('massDeleteBtn');
    const massExportBtn = document.getElementById('massExportBtn');

    if (!selectAllCheckbox || rowCheckboxes.length === 0) {
        return; // Pas de fonctionnalité d'actions en masse sur cette page
    }

    /**
     * Met à jour l'affichage de la barre d'actions en masse
     */
    function updateMassActionBar() {
        const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
        const count = checkedBoxes.length;

        if (selectedCountSpan) {
            selectedCountSpan.textContent = count;
        }

        if (massActionBar) {
            if (count > 0) {
                massActionBar.classList.remove('d-none');
            } else {
                massActionBar.classList.add('d-none');
            }
        }

        // Mettre à jour l'état de la checkbox "Tout sélectionner"
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = count === rowCheckboxes.length && count > 0;
            selectAllCheckbox.indeterminate = count > 0 && count < rowCheckboxes.length;
        }
    }

    /**
     * Récupère les IDs des lignes sélectionnées
     */
    function getSelectedIds() {
        const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
        return Array.from(checkedBoxes).map(cb => cb.value);
    }

    // Gestion de la checkbox "Tout sélectionner"
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateMassActionBar();
        });
    }

    // Gestion des checkboxes individuelles
    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateMassActionBar);
    });

    // Gestion du bouton de suppression en masse
    if (massDeleteBtn) {
        massDeleteBtn.addEventListener('click', function() {
            const selectedIds = getSelectedIds();
            
            if (selectedIds.length === 0) {
                return;
            }

            const confirmMessage = `Êtes-vous sûr de vouloir supprimer ${selectedIds.length} élément(s) ?`;
            
            if (confirm(confirmMessage)) {
                // Créer un formulaire pour la suppression en masse
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.pathname + '/bulk-delete';

                // Ajouter un token CSRF si disponible
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (csrfToken) {
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = csrfToken.content;
                    form.appendChild(csrfInput);
                }

                // Ajouter les IDs à supprimer
                selectedIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = id;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    // Gestion du bouton d'export en masse
    if (massExportBtn) {
        massExportBtn.addEventListener('click', function() {
            const selectedIds = getSelectedIds();
            
            if (selectedIds.length === 0) {
                return;
            }

            // Créer une URL avec les IDs sélectionnés
            const exportUrl = new URL(window.location.pathname + '/export', window.location.origin);
            selectedIds.forEach(id => {
                exportUrl.searchParams.append('ids[]', id);
            });

            // Rediriger vers l'URL d'export
            window.location.href = exportUrl.toString();
        });
    }

    // Gestion des liens avec confirmation
    document.querySelectorAll('.delete-confirm').forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
                e.preventDefault();
            }
        });
    });

    // Gestion du tri des colonnes
    document.querySelectorAll('.sortable').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const column = this.dataset.column;
            const url = new URL(window.location);
            
            // Déterminer la direction du tri
            const currentSort = url.searchParams.get('sort');
            const currentOrder = url.searchParams.get('order');
            
            let newOrder = 'asc';
            if (currentSort === column && currentOrder === 'asc') {
                newOrder = 'desc';
            }
            
            url.searchParams.set('sort', column);
            url.searchParams.set('order', newOrder);
            
            window.location.href = url.toString();
        });
    });
});
