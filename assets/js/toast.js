/**
 * Toast Notification Helper
 * Wrapper around toastr library with consistent styling
 */

import toastr from 'toastr';
import 'toastr/build/toastr.min.css';

// Configure toastr defaults
toastr.options = {
    closeButton: true,
    debug: false,
    newestOnTop: true,
    progressBar: true,
    positionClass: 'toast-top-right',
    preventDuplicates: true,
    onclick: null,
    showDuration: '300',
    hideDuration: '1000',
    timeOut: '5000',
    extendedTimeOut: '1000',
    showEasing: 'swing',
    hideEasing: 'linear',
    showMethod: 'fadeIn',
    hideMethod: 'fadeOut'
};

/**
 * Toast notification utility
 */
const Toast = {
    /**
     * Show success toast
     * @param {string} message - The message to display
     * @param {string} title - Optional title
     * @param {object} options - Optional toastr options override
     */
    success: function(message, title = '', options = {}) {
        toastr.success(message, title, options);
    },

    /**
     * Show info toast
     * @param {string} message - The message to display
     * @param {string} title - Optional title
     * @param {object} options - Optional toastr options override
     */
    info: function(message, title = '', options = {}) {
        toastr.info(message, title, options);
    },

    /**
     * Show warning toast
     * @param {string} message - The message to display
     * @param {string} title - Optional title
     * @param {object} options - Optional toastr options override
     */
    warning: function(message, title = '', options = {}) {
        toastr.warning(message, title, options);
    },

    /**
     * Show error toast
     * @param {string} message - The message to display
     * @param {string} title - Optional title
     * @param {object} options - Optional toastr options override
     */
    error: function(message, title = '', options = {}) {
        toastr.error(message, title, options);
    },

    /**
     * Clear all toasts
     */
    clear: function() {
        toastr.clear();
    },

    /**
     * Remove specific toast
     * @param {object} toast - The toast object to remove
     */
    remove: function(toast) {
        toastr.remove(toast);
    }
};

// Make Toast globally available
window.Toast = Toast;

// Auto-convert Symfony flash messages to toasts
document.addEventListener('DOMContentLoaded', function() {
    // Convert existing flash messages
    const flashContainer = document.querySelector('.flash-messages');
    if (flashContainer) {
        const alerts = flashContainer.querySelectorAll('.alert');

        alerts.forEach(function(alert) {
            let type = 'info';
            let message = alert.textContent.trim();

            // Determine type from alert classes
            if (alert.classList.contains('alert-success')) {
                type = 'success';
            } else if (alert.classList.contains('alert-danger')) {
                type = 'error';
            } else if (alert.classList.contains('alert-warning')) {
                type = 'warning';
            } else if (alert.classList.contains('alert-info')) {
                type = 'info';
            }

            // Show toast
            Toast[type](message);

            // Remove original alert
            alert.remove();
        });

        // Hide container if empty
        if (flashContainer.children.length === 0) {
            flashContainer.style.display = 'none';
        }
    }
});

// Export for ES6 modules
export default Toast;
