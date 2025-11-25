/**
 * Form Validation JavaScript Module
 * Validation temps réel (AJAX) pour les formulaires
 */

document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner tous les champs avec validation AJAX
    const validationFields = document.querySelectorAll('[data-validation-url]');

    // Délai avant validation (debounce)
    const VALIDATION_DELAY = 500;
    const validationTimers = new Map();

    /**
     * Affiche un message de validation
     */
    function showValidationMessage(field, message, type = 'error') {
        // Supprimer les anciens messages
        const oldFeedback = field.parentElement.querySelector('.validation-feedback');
        if (oldFeedback) {
            oldFeedback.remove();
        }

        // Créer le nouveau message
        const feedback = document.createElement('div');
        feedback.className = `validation-feedback text-${type === 'error' ? 'danger' : 'success'} small mt-1`;
        feedback.textContent = message;

        // Ajouter le message après le champ
        field.parentElement.appendChild(feedback);

        // Mettre à jour les classes du champ
        field.classList.remove('is-valid', 'is-invalid');
        field.classList.add(type === 'error' ? 'is-invalid' : 'is-valid');
    }

    /**
     * Retire les messages de validation
     */
    function clearValidationMessage(field) {
        const feedback = field.parentElement.querySelector('.validation-feedback');
        if (feedback) {
            feedback.remove();
        }
        field.classList.remove('is-valid', 'is-invalid');
    }

    /**
     * Valide un champ via AJAX
     */
    async function validateField(field) {
        const url = field.dataset.validationUrl;
        const value = field.value.trim();
        const validationType = field.dataset.validationType || 'generic';

        // Si le champ est vide, ne pas valider (sauf si requis)
        if (!value && !field.required) {
            clearValidationMessage(field);
            return;
        }

        // Validation locale rapide avant AJAX
        if (!localValidation(field, value, validationType)) {
            return; // Le message d'erreur a déjà été affiché
        }

        // Afficher un indicateur de chargement
        field.classList.add('is-validating');

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    value: value,
                    type: validationType,
                    field: field.name
                })
            });

            const data = await response.json();

            field.classList.remove('is-validating');

            if (data.valid) {
                showValidationMessage(field, data.message || '✓ Valide', 'success');
            } else {
                showValidationMessage(field, data.message || 'Valeur invalide', 'error');
            }
        } catch (error) {
            console.error('Erreur de validation:', error);
            field.classList.remove('is-validating');
            showValidationMessage(field, 'Erreur de validation', 'error');
        }
    }

    /**
     * Validation locale (côté client) avant l'appel AJAX
     */
    function localValidation(field, value, type) {
        switch (type) {
            case 'email':
                if (!isValidEmail(value)) {
                    showValidationMessage(field, 'Format d\'email invalide', 'error');
                    return false;
                }
                break;

            case 'siret':
                if (!isValidSiret(value)) {
                    showValidationMessage(field, 'Le SIRET doit contenir 14 chiffres', 'error');
                    return false;
                }
                break;

            case 'phone':
                if (!isValidPhone(value)) {
                    showValidationMessage(field, 'Format de téléphone invalide', 'error');
                    return false;
                }
                break;

            case 'url':
                if (!isValidUrl(value)) {
                    showValidationMessage(field, 'Format d\'URL invalide', 'error');
                    return false;
                }
                break;

            case 'date':
                if (!isValidDate(value)) {
                    showValidationMessage(field, 'Format de date invalide', 'error');
                    return false;
                }
                break;

            case 'number':
                if (isNaN(value) || value === '') {
                    showValidationMessage(field, 'Veuillez saisir un nombre valide', 'error');
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * Validators
     */
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    function isValidSiret(siret) {
        const cleaned = siret.replace(/\s/g, '');
        return /^\d{14}$/.test(cleaned);
    }

    function isValidPhone(phone) {
        const cleaned = phone.replace(/[\s\-\.\(\)]/g, '');
        return /^(\+33|0)[1-9]\d{8}$/.test(cleaned);
    }

    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }

    function isValidDate(date) {
        const parsed = Date.parse(date);
        return !isNaN(parsed);
    }

    /**
     * Attacher les événements aux champs
     */
    validationFields.forEach(field => {
        // Validation lors de la saisie (avec debounce)
        field.addEventListener('input', function() {
            // Annuler le timer précédent
            if (validationTimers.has(field)) {
                clearTimeout(validationTimers.get(field));
            }

            // Créer un nouveau timer
            const timer = setTimeout(() => {
                validateField(field);
            }, VALIDATION_DELAY);

            validationTimers.set(field, timer);
        });

        // Validation lors de la perte de focus
        field.addEventListener('blur', function() {
            // Annuler le timer si existe
            if (validationTimers.has(field)) {
                clearTimeout(validationTimers.get(field));
                validationTimers.delete(field);
            }

            // Valider immédiatement
            if (field.value.trim()) {
                validateField(field);
            }
        });
    });

    /**
     * Validation du formulaire avant soumission
     */
    const forms = document.querySelectorAll('form[data-validate-on-submit]');
    forms.forEach(form => {
        form.addEventListener('submit', async function(e) {
            const invalidFields = form.querySelectorAll('.is-invalid');
            const validatingFields = form.querySelectorAll('.is-validating');

            if (invalidFields.length > 0 || validatingFields.length > 0) {
                e.preventDefault();

                // Afficher un message d'erreur global
                let errorMessage = form.querySelector('.form-validation-error');
                if (!errorMessage) {
                    errorMessage = document.createElement('div');
                    errorMessage.className = 'form-validation-error alert alert-danger mt-3';
                    form.appendChild(errorMessage);
                }

                if (validatingFields.length > 0) {
                    errorMessage.textContent = 'Veuillez attendre la fin de la validation...';
                } else {
                    errorMessage.textContent = 'Veuillez corriger les erreurs avant de soumettre le formulaire.';
                }

                // Scroller jusqu'au premier champ invalide
                invalidFields[0]?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    });

    // Style CSS pour l'indicateur de chargement
    const style = document.createElement('style');
    style.textContent = `
        .is-validating {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24'%3E%3Cpath fill='%23999' d='M12,1A11,11,0,1,0,23,12,11,11,0,0,0,12,1Zm0,19a8,8,0,1,1,8-8A8,8,0,0,1,12,20Z' opacity='.25'/%3E%3Cpath fill='%23999' d='M12,4a8,8,0,0,1,7.89,6.7A1.53,1.53,0,0,0,21.38,12h0a1.5,1.5,0,0,0,1.48-1.75,11,11,0,0,0-21.72,0A1.5,1.5,0,0,0,2.62,12h0a1.53,1.53,0,0,0,1.49-1.3A8,8,0,0,1,12,4Z'%3E%3CanimateTransform attributeName='transform' type='rotate' dur='0.75s' values='0 12 12;360 12 12' repeatCount='indefinite'/%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px 20px;
            padding-right: 40px !important;
        }
    `;
    document.head.appendChild(style);
});
