import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['modal', 'iframe', 'spinner', 'error'];
    static values = { url: String };

    connect() {
        this._onKeydown = this._handleKeydown.bind(this);
    }

    open(event) {
        event.preventDefault();

        const modal = this.modalTarget;
        const iframe = this.iframeTarget;

        // Show modal and spinner
        modal.classList.add('show');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        this.spinnerTarget.classList.remove('d-none');
        this.errorTarget.classList.add('d-none');
        iframe.classList.add('d-none');
        iframe.src = '';

        // Load PDF
        iframe.src = this.urlValue;
        iframe.onload = () => {
            this.spinnerTarget.classList.add('d-none');
            iframe.classList.remove('d-none');
        };
        iframe.onerror = () => {
            this.spinnerTarget.classList.add('d-none');
            this.errorTarget.classList.remove('d-none');
        };

        // Listen for Escape key
        document.addEventListener('keydown', this._onKeydown);
    }

    close() {
        const modal = this.modalTarget;
        const iframe = this.iframeTarget;

        modal.classList.remove('show');
        modal.style.display = 'none';
        document.body.style.overflow = '';
        iframe.src = '';

        document.removeEventListener('keydown', this._onKeydown);
    }

    closeOnOverlay(event) {
        if (event.target === this.modalTarget) {
            this.close();
        }
    }

    download(event) {
        event.preventDefault();
        const downloadUrl = this.urlValue.replace('/preview', '');
        window.location.href = downloadUrl;
    }

    _handleKeydown(event) {
        if (event.key === 'Escape') {
            this.close();
        }
    }

    disconnect() {
        document.removeEventListener('keydown', this._onKeydown);
        document.body.style.overflow = '';
    }
}
