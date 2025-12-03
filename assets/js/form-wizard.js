/**
 * Form Wizard Component
 *
 * Multi-step form wizard with progress bar, navigation, and validation.
 *
 * Usage:
 *
 * 1. HTML Structure:
 *    - Wrap form in a container with `data-wizard="true"`
 *    - Each step should have `data-wizard-step="1"`, `data-wizard-step="2"`, etc.
 *    - Add navigation buttons with `data-wizard-action="next|prev|submit"`
 *
 * 2. JavaScript:
 *    import { initWizards } from './form-wizard.js';
 *    initWizards();
 *
 * Example:
 * <div class="wizard-container" data-wizard="true" data-wizard-save-state="true">
 *   <div class="wizard-progress">
 *     <div class="wizard-progress-bar"></div>
 *   </div>
 *
 *   <div class="wizard-steps">
 *     <div data-wizard-step="1" class="active">
 *       <h3>Step 1: Basic Info</h3>
 *       <input name="name" required>
 *       <button type="button" data-wizard-action="next">Next</button>
 *     </div>
 *
 *     <div data-wizard-step="2">
 *       <h3>Step 2: Details</h3>
 *       <input name="email" required>
 *       <button type="button" data-wizard-action="prev">Previous</button>
 *       <button type="button" data-wizard-action="next">Next</button>
 *     </div>
 *
 *     <div data-wizard-step="3">
 *       <h3>Step 3: Confirmation</h3>
 *       <button type="button" data-wizard-action="prev">Previous</button>
 *       <button type="submit" data-wizard-action="submit">Submit</button>
 *     </div>
 *   </div>
 * </div>
 */

/**
 * Initialize all wizards on the page
 */
export function initWizards() {
    const wizardContainers = document.querySelectorAll('[data-wizard="true"]');

    wizardContainers.forEach(container => {
        new FormWizard(container);
    });
}

/**
 * FormWizard class
 * Manages multi-step form with validation and state persistence
 */
class FormWizard {
    constructor(container) {
        this.container = container;
        this.form = container.querySelector('form') || container.closest('form');
        this.steps = Array.from(container.querySelectorAll('[data-wizard-step]'));
        this.currentStep = 1;
        this.totalSteps = this.steps.length;
        this.saveState = container.getAttribute('data-wizard-save-state') === 'true';
        this.stateKey = container.getAttribute('data-wizard-state-key') || 'wizard-state';
        this.validateOnNext = container.getAttribute('data-wizard-validate') !== 'false';

        if (this.steps.length === 0) {
            console.warn('No wizard steps found in container');
            return;
        }

        this.init();
    }

    init() {
        // Hide all steps except the first
        this.steps.forEach((step, index) => {
            step.style.display = index === 0 ? 'block' : 'none';
            if (index === 0) {
                step.classList.add('active');
            }
        });

        // Initialize progress bar
        this.initProgressBar();

        // Initialize navigation buttons
        this.initNavigation();

        // Restore state if enabled
        if (this.saveState) {
            this.restoreState();
        }

        // Update progress
        this.updateProgress();

        // Trigger initialization event
        this.triggerEvent('wizard:init', { step: this.currentStep, totalSteps: this.totalSteps });
    }

    initProgressBar() {
        let progressBar = this.container.querySelector('.wizard-progress-bar');

        // Create progress bar if it doesn't exist
        if (!progressBar) {
            const progressContainer = document.createElement('div');
            progressContainer.className = 'wizard-progress mb-4';
            progressContainer.innerHTML = '<div class="wizard-progress-bar"></div>';

            this.container.insertBefore(progressContainer, this.container.firstChild);
            progressBar = progressContainer.querySelector('.wizard-progress-bar');
        }

        this.progressBar = progressBar;

        // Create step indicators if configured
        if (this.container.getAttribute('data-wizard-show-steps') === 'true') {
            this.createStepIndicators();
        }
    }

    createStepIndicators() {
        const indicators = document.createElement('div');
        indicators.className = 'wizard-step-indicators d-flex justify-content-between mb-3';

        this.steps.forEach((step, index) => {
            const stepNumber = index + 1;
            const stepTitle = step.getAttribute('data-wizard-title') || `Step ${stepNumber}`;

            const indicator = document.createElement('div');
            indicator.className = 'wizard-step-indicator';
            indicator.innerHTML = `
                <div class="step-number ${stepNumber === 1 ? 'active' : ''}">${stepNumber}</div>
                <div class="step-title">${stepTitle}</div>
            `;

            indicators.appendChild(indicator);
        });

        this.container.insertBefore(indicators, this.progressBar.parentElement.nextSibling);
        this.stepIndicators = indicators.querySelectorAll('.step-number');
    }

    initNavigation() {
        // Find all navigation buttons
        const nextButtons = this.container.querySelectorAll('[data-wizard-action="next"]');
        const prevButtons = this.container.querySelectorAll('[data-wizard-action="prev"]');
        const submitButtons = this.container.querySelectorAll('[data-wizard-action="submit"]');

        nextButtons.forEach(button => {
            button.addEventListener('click', () => this.next());
        });

        prevButtons.forEach(button => {
            button.addEventListener('click', () => this.prev());
        });

        submitButtons.forEach(button => {
            button.addEventListener('click', () => this.submit());
        });

        // Handle Enter key for next step
        if (this.container.getAttribute('data-wizard-enter-next') === 'true') {
            this.steps.forEach((step, index) => {
                step.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter' && index < this.totalSteps - 1) {
                        e.preventDefault();
                        this.next();
                    }
                });
            });
        }
    }

    async next() {
        // Validate current step if enabled
        if (this.validateOnNext && !await this.validateStep(this.currentStep)) {
            this.triggerEvent('wizard:validation-failed', { step: this.currentStep });
            return false;
        }

        if (this.currentStep < this.totalSteps) {
            this.goToStep(this.currentStep + 1);
            return true;
        }

        return false;
    }

    prev() {
        if (this.currentStep > 1) {
            this.goToStep(this.currentStep - 1);
            return true;
        }

        return false;
    }

    goToStep(stepNumber) {
        if (stepNumber < 1 || stepNumber > this.totalSteps) {
            return false;
        }

        // Hide current step
        this.steps[this.currentStep - 1].style.display = 'none';
        this.steps[this.currentStep - 1].classList.remove('active');

        // Show new step
        this.currentStep = stepNumber;
        this.steps[this.currentStep - 1].style.display = 'block';
        this.steps[this.currentStep - 1].classList.add('active');

        // Update progress
        this.updateProgress();

        // Save state if enabled
        if (this.saveState) {
            this.saveCurrentState();
        }

        // Trigger step change event
        this.triggerEvent('wizard:step-changed', {
            step: this.currentStep,
            totalSteps: this.totalSteps,
            isFirst: this.currentStep === 1,
            isLast: this.currentStep === this.totalSteps
        });

        // Scroll to top of wizard
        this.container.scrollIntoView({ behavior: 'smooth', block: 'start' });

        return true;
    }

    updateProgress() {
        const progress = ((this.currentStep - 1) / (this.totalSteps - 1)) * 100;
        this.progressBar.style.width = `${progress}%`;
        this.progressBar.setAttribute('aria-valuenow', progress);

        // Update step indicators if they exist
        if (this.stepIndicators) {
            this.stepIndicators.forEach((indicator, index) => {
                indicator.classList.toggle('active', index < this.currentStep);
                indicator.classList.toggle('completed', index < this.currentStep - 1);
            });
        }
    }

    async validateStep(stepNumber) {
        const step = this.steps[stepNumber - 1];
        const inputs = step.querySelectorAll('input, select, textarea');

        let isValid = true;

        // Check HTML5 validation
        inputs.forEach(input => {
            if (!input.checkValidity()) {
                input.reportValidity();
                isValid = false;
            }
        });

        // Trigger custom validation event
        const validationEvent = new CustomEvent('wizard:validate-step', {
            detail: { step: stepNumber, isValid },
            cancelable: true
        });

        this.container.dispatchEvent(validationEvent);

        // If event was cancelled, validation failed
        if (validationEvent.defaultPrevented) {
            isValid = false;
        }

        return isValid;
    }

    submit() {
        // Validate all steps before submit
        if (this.validateOnNext) {
            for (let i = 1; i <= this.totalSteps; i++) {
                if (!this.validateStep(i)) {
                    this.goToStep(i);
                    this.triggerEvent('wizard:submit-failed', { failedStep: i });
                    return false;
                }
            }
        }

        // Clear saved state
        if (this.saveState) {
            this.clearState();
        }

        // Trigger submit event
        this.triggerEvent('wizard:submit', { totalSteps: this.totalSteps });

        // Submit the form if it exists
        if (this.form) {
            this.form.submit();
        }

        return true;
    }

    saveCurrentState() {
        const state = {
            currentStep: this.currentStep,
            formData: this.getFormData()
        };

        localStorage.setItem(this.stateKey, JSON.stringify(state));
    }

    restoreState() {
        const savedState = localStorage.getItem(this.stateKey);

        if (!savedState) {
            return;
        }

        try {
            const state = JSON.parse(savedState);

            // Restore form data
            if (state.formData) {
                this.setFormData(state.formData);
            }

            // Restore step
            if (state.currentStep && state.currentStep > 1) {
                this.goToStep(state.currentStep);
            }

        } catch (error) {
            console.error('Failed to restore wizard state:', error);
        }
    }

    clearState() {
        localStorage.removeItem(this.stateKey);
    }

    getFormData() {
        if (!this.form) {
            return {};
        }

        const formData = new FormData(this.form);
        const data = {};

        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }

        return data;
    }

    setFormData(data) {
        if (!this.form) {
            return;
        }

        Object.keys(data).forEach(key => {
            const input = this.form.querySelector(`[name="${key}"]`);

            if (input) {
                if (input.type === 'checkbox' || input.type === 'radio') {
                    input.checked = input.value === data[key];
                } else {
                    input.value = data[key];
                }
            }
        });
    }

    triggerEvent(eventName, detail = {}) {
        const event = new CustomEvent(eventName, {
            detail,
            bubbles: true,
            cancelable: true
        });

        this.container.dispatchEvent(event);
    }

    // Public API methods
    reset() {
        this.goToStep(1);
        if (this.form) {
            this.form.reset();
        }
        this.clearState();
        this.triggerEvent('wizard:reset');
    }

    getCurrentStep() {
        return this.currentStep;
    }

    getTotalSteps() {
        return this.totalSteps;
    }
}

/**
 * Programmatically create a wizard
 * @param {HTMLElement} container - The wizard container element
 * @param {Object} options - Configuration options
 * @returns {FormWizard}
 */
export function createWizard(container, options = {}) {
    container.setAttribute('data-wizard', 'true');

    if (options.saveState) {
        container.setAttribute('data-wizard-save-state', 'true');
    }

    if (options.stateKey) {
        container.setAttribute('data-wizard-state-key', options.stateKey);
    }

    if (options.validate === false) {
        container.setAttribute('data-wizard-validate', 'false');
    }

    if (options.showSteps) {
        container.setAttribute('data-wizard-show-steps', 'true');
    }

    if (options.enterNext) {
        container.setAttribute('data-wizard-enter-next', 'true');
    }

    return new FormWizard(container);
}

// Auto-initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    initWizards();
});
