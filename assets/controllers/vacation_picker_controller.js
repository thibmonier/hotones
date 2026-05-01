import { Controller } from '@hotwired/stimulus';

/*
 * Stimulus controller for the vacation request form (US-066).
 *
 * Wires the start/end DateType inputs together so that:
 *  - selecting a start date auto-floors the end date to be >= start
 *  - the duration in business days (Mon-Fri) is shown live in the summary alert
 *
 * Targets:
 *   startInput      — input[type="date"] for vacation.startDate
 *   endInput        — input[type="date"] for vacation.endDate
 *   summary         — wrapper div around the live duration alert
 *   businessDays    — span receiving the integer count
 */
export default class extends Controller {
    static targets = ['startInput', 'endInput', 'summary', 'businessDays'];

    connect() {
        if (this.hasStartInputTarget) {
            this.startInputTarget.addEventListener('change', this.onStartChange);
        }
        if (this.hasEndInputTarget) {
            this.endInputTarget.addEventListener('change', this.refresh);
        }
        this.refresh();
    }

    disconnect() {
        if (this.hasStartInputTarget) {
            this.startInputTarget.removeEventListener('change', this.onStartChange);
        }
        if (this.hasEndInputTarget) {
            this.endInputTarget.removeEventListener('change', this.refresh);
        }
    }

    onStartChange = () => {
        if (!this.hasStartInputTarget || !this.hasEndInputTarget) {
            return;
        }
        const start = this.startInputTarget.value;
        if (!start) {
            this.refresh();
            return;
        }
        // Floor end on start so the form never accepts end < start visually.
        if (!this.endInputTarget.value || this.endInputTarget.value < start) {
            this.endInputTarget.value = start;
        }
        this.endInputTarget.min = start;
        this.refresh();
    };

    refresh = () => {
        if (!this.hasSummaryTarget || !this.hasBusinessDaysTarget) {
            return;
        }
        const alert = this.summaryTarget.querySelector('.alert');
        if (!alert) {
            return;
        }

        const days = this.computeBusinessDays();
        if (days === null) {
            alert.classList.add('d-none');
            return;
        }
        this.businessDaysTarget.textContent = String(days);
        alert.classList.remove('d-none');
    };

    computeBusinessDays() {
        if (!this.hasStartInputTarget || !this.hasEndInputTarget) {
            return null;
        }
        const startValue = this.startInputTarget.value;
        const endValue = this.endInputTarget.value;
        if (!startValue || !endValue) {
            return null;
        }
        const start = new Date(`${startValue}T00:00:00`);
        const end = new Date(`${endValue}T00:00:00`);
        if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end < start) {
            return null;
        }
        let count = 0;
        const cursor = new Date(start);
        while (cursor <= end) {
            const day = cursor.getDay();
            if (day !== 0 && day !== 6) {
                count += 1;
            }
            cursor.setDate(cursor.getDate() + 1);
        }
        return count;
    }
}
