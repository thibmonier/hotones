/**
 * Dependent Fields Helper
 *
 * Utility for creating cascading/dependent select fields where the options
 * of one select depend on the value of another.
 *
 * Usage:
 *
 * 1. HTML Attributes:
 *    - Add `data-dependent-source="selector"` to the dependent field
 *    - Add `data-dependent-url="/api/endpoint?parent={value}"` to the dependent field
 *    - Optional: `data-dependent-placeholder="Choose..."` for the placeholder text
 *    - Optional: `data-dependent-on-load="true"` to populate on page load if source has value
 *
 * 2. JavaScript:
 *    import { initDependentFields } from './dependent-fields.js';
 *    initDependentFields();
 *
 * Example:
 * <select id="project" name="project" class="form-select">
 *   <option value="">Select project...</option>
 *   <option value="1">Project A</option>
 * </select>
 *
 * <select id="task" name="task" class="form-select"
 *   data-dependent-source="#project"
 *   data-dependent-url="/api/projects/{value}/tasks"
 *   data-dependent-placeholder="Select task..."
 *   data-dependent-on-load="true">
 *   <option value="">Select project first</option>
 * </select>
 */

/**
 * Initialize all dependent fields on the page
 */
export function initDependentFields() {
    const dependentFields = document.querySelectorAll('[data-dependent-source]');

    dependentFields.forEach(field => {
        const sourceSelector = field.getAttribute('data-dependent-source');
        const sourceField = document.querySelector(sourceSelector);

        if (!sourceField) {
            console.warn(`Dependent field source not found: ${sourceSelector}`);
            return;
        }

        // Initialize the dependent field handler
        new DependentField(sourceField, field);
    });
}

/**
 * DependentField class
 * Handles the relationship between a source field and a dependent field
 */
class DependentField {
    constructor(sourceField, dependentField) {
        this.sourceField = sourceField;
        this.dependentField = dependentField;
        this.url = dependentField.getAttribute('data-dependent-url');
        this.placeholder = dependentField.getAttribute('data-dependent-placeholder') || 'Select...';
        this.loadOnInit = dependentField.getAttribute('data-dependent-on-load') === 'true';
        this.valueField = dependentField.getAttribute('data-dependent-value-field') || 'id';
        this.labelField = dependentField.getAttribute('data-dependent-label-field') || 'name';
        this.originalValue = dependentField.value; // Store original value for restoration

        this.init();
    }

    init() {
        // Listen to source field changes
        this.sourceField.addEventListener('change', () => {
            this.handleSourceChange();
        });

        // Load on init if requested and source has a value
        if (this.loadOnInit && this.sourceField.value) {
            this.handleSourceChange(this.originalValue);
        }
    }

    async handleSourceChange(selectValue = null) {
        const sourceValue = this.sourceField.value;

        // If source is empty, reset dependent field
        if (!sourceValue) {
            this.resetDependentField();
            return;
        }

        // Show loading state
        this.setLoadingState(true);

        try {
            // Replace {value} in URL with the source value
            const url = this.url.replace('{value}', sourceValue);

            const response = await fetch(url);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            // Populate dependent field with options
            this.populateDependentField(data, selectValue);

        } catch (error) {
            console.error('Error loading dependent field options:', error);
            this.showError();
        } finally {
            this.setLoadingState(false);
        }
    }

    resetDependentField() {
        this.dependentField.innerHTML = `<option value="">${this.placeholder}</option>`;
        this.dependentField.disabled = true;
        this.triggerChange();
    }

    populateDependentField(options, selectValue = null) {
        // Clear existing options
        this.dependentField.innerHTML = '';

        // Add placeholder
        const placeholderOption = document.createElement('option');
        placeholderOption.value = '';
        placeholderOption.textContent = this.placeholder;
        this.dependentField.appendChild(placeholderOption);

        // Add options from API response
        options.forEach(option => {
            const optionElement = document.createElement('option');

            // Support both object and simple value responses
            if (typeof option === 'object') {
                optionElement.value = option[this.valueField];
                optionElement.textContent = option[this.labelField];
            } else {
                optionElement.value = option;
                optionElement.textContent = option;
            }

            this.dependentField.appendChild(optionElement);
        });

        // Enable the field
        this.dependentField.disabled = false;

        // Restore original value if it exists in the new options
        if (selectValue) {
            this.dependentField.value = selectValue;
        }

        this.triggerChange();
    }

    setLoadingState(loading) {
        if (loading) {
            this.dependentField.disabled = true;
            this.dependentField.classList.add('is-loading');
            this.dependentField.innerHTML = '<option value="">Loading...</option>';
        } else {
            this.dependentField.classList.remove('is-loading');
        }
    }

    showError() {
        this.dependentField.innerHTML = '<option value="">Error loading options</option>';
        this.dependentField.disabled = true;
    }

    triggerChange() {
        // Trigger change event for other dependent fields or listeners
        this.dependentField.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

/**
 * Programmatically create a dependent field relationship
 * @param {HTMLElement} sourceField - The source select element
 * @param {HTMLElement} dependentField - The dependent select element
 * @param {Object} options - Configuration options
 * @returns {DependentField}
 */
export function createDependentField(sourceField, dependentField, options = {}) {
    // Set required attributes
    dependentField.setAttribute('data-dependent-source', `#${sourceField.id}`);
    dependentField.setAttribute('data-dependent-url', options.url);

    if (options.placeholder) {
        dependentField.setAttribute('data-dependent-placeholder', options.placeholder);
    }

    if (options.loadOnInit) {
        dependentField.setAttribute('data-dependent-on-load', 'true');
    }

    if (options.valueField) {
        dependentField.setAttribute('data-dependent-value-field', options.valueField);
    }

    if (options.labelField) {
        dependentField.setAttribute('data-dependent-label-field', options.labelField);
    }

    // Create and return the DependentField instance
    return new DependentField(sourceField, dependentField);
}

// Auto-initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    initDependentFields();
});
