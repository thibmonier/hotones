/**
 * Lazy Chart Loading avec Intersection Observer
 *
 * Charge les graphiques Chart.js uniquement quand ils deviennent visibles,
 * améliorant les performances de chargement initial des dashboards.
 *
 * Usage:
 * <canvas
 *   id="myChart"
 *   data-chart-lazy="true"
 *   data-chart-type="line"
 *   data-chart-data='{"labels":[],"datasets":[]}'
 *   data-chart-options='{}'
 * ></canvas>
 */

class LazyChartLoader {
    constructor() {
        this.observer = null;
        this.charts = new Map();
        this.init();
    }

    init() {
        // Vérifier si IntersectionObserver est disponible
        if (!('IntersectionObserver' in window)) {
            console.warn('IntersectionObserver non disponible, chargement immédiat des graphiques');
            this.loadAllCharts();
            return;
        }

        // Créer l'observer avec un seuil de 10% de visibilité
        this.observer = new IntersectionObserver(
            (entries) => this.handleIntersection(entries),
            {
                root: null,
                rootMargin: '50px', // Charger 50px avant d'entrer dans le viewport
                threshold: 0.1
            }
        );

        // Observer tous les canvas avec data-chart-lazy
        const lazyCharts = document.querySelectorAll('canvas[data-chart-lazy="true"]');
        lazyCharts.forEach(canvas => {
            // Ajouter un indicateur de chargement
            const wrapper = canvas.parentElement;
            if (wrapper) {
                wrapper.style.position = 'relative';
                const loader = document.createElement('div');
                loader.className = 'chart-loader';
                loader.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div>';
                loader.style.cssText = 'position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10;';
                wrapper.appendChild(loader);
            }

            this.observer.observe(canvas);
        });
    }

    handleIntersection(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const canvas = entry.target;
                this.loadChart(canvas);
                this.observer.unobserve(canvas);
            }
        });
    }

    async loadChart(canvas) {
        // Vérifier si Chart.js est chargé
        if (typeof Chart === 'undefined') {
            console.error('Chart.js n\'est pas chargé');
            return;
        }

        try {
            const type = canvas.dataset.chartType;
            const data = JSON.parse(canvas.dataset.chartData || '{}');
            const options = JSON.parse(canvas.dataset.chartOptions || '{}');

            // Créer le graphique
            const ctx = canvas.getContext('2d');
            const chart = new Chart(ctx, {
                type: type,
                data: data,
                options: options
            });

            // Stocker la référence pour usage ultérieur
            this.charts.set(canvas.id, chart);

            // Supprimer le loader
            const loader = canvas.parentElement?.querySelector('.chart-loader');
            if (loader) {
                loader.remove();
            }

            // Émettre un événement personnalisé
            canvas.dispatchEvent(new CustomEvent('chart:loaded', { detail: { chart } }));

        } catch (error) {
            console.error('Erreur lors du chargement du graphique:', canvas.id, error);

            // Afficher un message d'erreur
            const loader = canvas.parentElement?.querySelector('.chart-loader');
            if (loader) {
                loader.innerHTML = '<div class="text-danger"><i class="bx bx-error"></i> Erreur de chargement</div>';
            }
        }
    }

    loadAllCharts() {
        // Fallback pour navigateurs sans IntersectionObserver
        const lazyCharts = document.querySelectorAll('canvas[data-chart-lazy="true"]');
        lazyCharts.forEach(canvas => this.loadChart(canvas));
    }

    getChart(canvasId) {
        return this.charts.get(canvasId);
    }

    destroy() {
        if (this.observer) {
            this.observer.disconnect();
        }
        this.charts.forEach(chart => chart.destroy());
        this.charts.clear();
    }
}

// Initialiser automatiquement au chargement du DOM
let lazyChartLoader = null;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        lazyChartLoader = new LazyChartLoader();
    });
} else {
    lazyChartLoader = new LazyChartLoader();
}

// Exporter pour usage global
window.LazyChartLoader = LazyChartLoader;
window.lazyChartLoader = lazyChartLoader;
