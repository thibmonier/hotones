/**
 * HotOnes - Public Pages Theme Toggle
 * Gère le basculement entre thème light et dark avec persistence localStorage
 * et détection automatique de la préférence système
 */

(function() {
    'use strict';

    // Détection de la préférence système
    const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');

    // Récupération du thème sauvegardé ou détection auto
    const savedTheme = localStorage.getItem('public-theme');
    const initialTheme = savedTheme || (prefersDarkScheme.matches ? 'dark' : 'light');

    /**
     * Applique le thème au document
     * @param {string} theme - 'light' ou 'dark'
     */
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        updateIcon(theme);
        updateLogo(theme);
    }

    /**
     * Met à jour l'icône du bouton toggle
     * @param {string} theme - 'light' ou 'dark'
     */
    function updateIcon(theme) {
        const icon = document.getElementById('theme-icon');
        if (!icon) return;

        if (theme === 'dark') {
            icon.className = 'bx bx-sun'; // Soleil en mode dark (pour passer en light)
        } else {
            icon.className = 'bx bx-moon'; // Lune en mode light (pour passer en dark)
        }
    }

    /**
     * Met à jour le logo de la navbar en fonction du thème
     * @param {string} theme - 'light' ou 'dark'
     */
    function updateLogo(theme) {
        const logo = document.getElementById('navbar-logo');
        if (!logo) return;

        // En mode light, on utilise le logo dark (contraste)
        // En mode dark, on utilise le logo light (contraste)
        const logoPath = theme === 'dark'
            ? '/assets/images/logo-hotones-light.svg'
            : '/assets/images/logo-hotones-dark.svg';

        logo.src = logoPath;
    }

    /**
     * Bascule entre les thèmes
     */
    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        applyTheme(newTheme);
        localStorage.setItem('public-theme', newTheme);

        // Analytics (si disponible)
        if (typeof gtag === 'function') {
            gtag('event', 'theme_toggle', {
                'event_category': 'engagement',
                'event_label': newTheme,
                'value': 1
            });
        }
    }

    /**
     * Gère le changement de préférence système
     */
    function handleSystemThemeChange(e) {
        // Ne change que si l'utilisateur n'a pas de préférence sauvegardée
        if (!localStorage.getItem('public-theme')) {
            const newTheme = e.matches ? 'dark' : 'light';
            applyTheme(newTheme);
        }
    }

    // ====================================
    // INITIALISATION
    // ====================================

    // Applique le thème immédiatement (évite le flash)
    applyTheme(initialTheme);

    // Écoute les changements de préférence système
    prefersDarkScheme.addEventListener('change', handleSystemThemeChange);

    // Attache le toggle au bouton une fois le DOM chargé
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initToggleButton);
    } else {
        // DOM déjà chargé
        initToggleButton();
    }

    function initToggleButton() {
        const toggleButton = document.getElementById('theme-toggle');

        if (toggleButton) {
            toggleButton.addEventListener('click', toggleTheme);

            // Ajoute un raccourci clavier (Ctrl/Cmd + Shift + T)
            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'T') {
                    e.preventDefault();
                    toggleTheme();
                }
            });
        }
    }

    // Export pour utilisation externe (si besoin)
    window.HotOnesTheme = {
        toggle: toggleTheme,
        setTheme: applyTheme,
        getTheme: function() {
            return document.documentElement.getAttribute('data-bs-theme') || 'light';
        }
    };

})();
