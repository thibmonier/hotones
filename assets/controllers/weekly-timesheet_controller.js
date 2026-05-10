import { Controller } from '@hotwired/stimulus';

/**
 * EPIC-003 Phase 3 (sprint-021 US-102) — Stimulus controller pour grille
 * hebdomadaire WorkItem.
 *
 * ADR-0016 Q2.1 auto-save sur change cellule.
 * ADR-0016 Q2.4 warning + override user via modal (boutons Confirmer / Annuler).
 *
 * Endpoints :
 * - POST /timesheet/week/save — JSON {projectId, date, hours, taskId?, comment?, userOverride}
 *   Réponses :
 *   - {status: "saved", workItemId, workItemStatus} → cellule sauvegardée
 *   - {status: "warning", warning: {dailyTotal, dailyMaxHours, excess}} → modal
 *   - {status: "error", error: string} → cellule rouge + message
 */
export default class extends Controller {
    static targets = [
        'cell',
        'status',
        'projectTotal',
        'dayTotal',
        'weekTotal',
        'warningModal',
        'warningTotal',
        'warningMax',
        'warningExcess',
    ];

    static values = {
        saveUrl: String,
        csrfToken: String,
        week: String,
    };

    connect() {
        this.pendingOverride = null;
        this.refreshTotals();
    }

    async onCellChange(event) {
        const cell = event.target;
        const projectId = parseInt(cell.dataset.projectId, 10);
        const date = cell.dataset.date;
        const hours = parseFloat(cell.value || '0');

        if (Number.isNaN(hours) || hours < 0) {
            this.markCellError(cell, 'Valeur invalide');
            return;
        }

        if (hours === 0) {
            // Skip save : 0 heure = absence saisie (sprint-022+ : delete via API)
            this.refreshTotals();
            return;
        }

        await this.saveCell(cell, { projectId, date, hours, userOverride: false });
    }

    async confirmOverride() {
        if (!this.pendingOverride) {
            this.hideWarningModal();
            return;
        }

        const { cell, payload } = this.pendingOverride;
        await this.saveCell(cell, { ...payload, userOverride: true });
        this.pendingOverride = null;
        this.hideWarningModal();
    }

    cancelOverride() {
        if (this.pendingOverride?.cell) {
            this.pendingOverride.cell.value = '';
        }
        this.pendingOverride = null;
        this.hideWarningModal();
        this.refreshTotals();
    }

    async saveCell(cell, payload) {
        cell.classList.add('is-saving');
        this.markCellNeutral(cell);

        try {
            const response = await fetch(this.saveUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfTokenValue,
                    Accept: 'application/json',
                },
                body: JSON.stringify(payload),
                credentials: 'same-origin',
            });

            const data = await response.json();

            if (data.status === 'saved') {
                this.markCellSaved(cell);
                this.refreshTotals();
                this.flashStatus(`Heures sauvegardées (${data.workItemStatus}).`, 'success');
                return;
            }

            if (data.status === 'warning') {
                this.pendingOverride = { cell, payload };
                this.showWarningModal(data.warning);
                return;
            }

            this.markCellError(cell, data.error || 'Erreur inconnue');
            this.flashStatus(data.error || 'Erreur sauvegarde', 'danger');
        } catch (err) {
            this.markCellError(cell, 'Erreur réseau');
            this.flashStatus('Erreur réseau — saisie non enregistrée', 'danger');
        } finally {
            cell.classList.remove('is-saving');
        }
    }

    refreshTotals() {
        const dayTotals = new Map();
        const projectTotals = new Map();
        let weekTotal = 0;

        this.cellTargets.forEach((cell) => {
            const hours = parseFloat(cell.value || '0') || 0;
            const date = cell.dataset.date;
            const projectId = cell.dataset.projectId;

            dayTotals.set(date, (dayTotals.get(date) || 0) + hours);
            projectTotals.set(projectId, (projectTotals.get(projectId) || 0) + hours);
            weekTotal += hours;
        });

        this.dayTotalTargets.forEach((el) => {
            const date = el.dataset.date;
            el.textContent = `${(dayTotals.get(date) || 0).toFixed(2)}h`;
        });

        this.projectTotalTargets.forEach((el) => {
            const projectId = el.dataset.projectId;
            el.textContent = `${(projectTotals.get(projectId) || 0).toFixed(2)}h`;
        });

        if (this.hasWeekTotalTarget) {
            this.weekTotalTarget.textContent = `${weekTotal.toFixed(2)}h`;
        }
    }

    markCellSaved(cell) {
        cell.classList.remove('is-invalid');
        cell.classList.add('is-valid');
    }

    markCellError(cell, message) {
        cell.classList.remove('is-valid');
        cell.classList.add('is-invalid');
        cell.title = message;
    }

    markCellNeutral(cell) {
        cell.classList.remove('is-valid', 'is-invalid');
        cell.title = '';
    }

    flashStatus(message, level) {
        if (!this.hasStatusTarget) return;

        const target = this.statusTarget;
        target.textContent = message;
        target.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-info');
        target.classList.add(`alert-${level}`);

        clearTimeout(this._statusTimer);
        this._statusTimer = setTimeout(() => {
            target.classList.add('d-none');
        }, 4000);
    }

    showWarningModal(warning) {
        if (this.hasWarningTotalTarget) this.warningTotalTarget.textContent = warning.dailyTotal;
        if (this.hasWarningMaxTarget) this.warningMaxTarget.textContent = warning.dailyMaxHours;
        if (this.hasWarningExcessTarget) this.warningExcessTarget.textContent = warning.excess;

        // Bootstrap 5 modal show. Fallback : ajout class 'show' + display.
        const modal = this.warningModalTarget;
        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modal).show();
        } else {
            modal.classList.add('show');
            modal.style.display = 'block';
        }
    }

    hideWarningModal() {
        const modal = this.warningModalTarget;
        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modal).hide();
        } else {
            modal.classList.remove('show');
            modal.style.display = 'none';
        }
    }
}
