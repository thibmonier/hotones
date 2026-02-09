/**
 * Notifications JavaScript Module
 * Gère les notifications temps réel et le dropdown dans le header
 */

document.addEventListener('DOMContentLoaded', function() {
    const POLL_INTERVAL = 30000; // 30 secondes
    let pollTimer = null;

    /**
     * Récupère les notifications non lues
     */
    async function fetchUnreadNotifications() {
        try {
            const response = await fetch('/notifications/api/unread');
            const data = await response.json();

            updateNotificationBadge(data.count);
            updateNotificationDropdown(data.notifications);

            return data;
        } catch (error) {
            console.error('Erreur lors de la récupération des notifications:', error);
            return null;
        }
    }

    /**
     * Met à jour le badge de compteur de notifications
     */
    function updateNotificationBadge(count) {
        const badge = document.querySelector('.notification-badge');
        if (!badge) return;

        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }
    }

    /**
     * Met à jour le contenu du dropdown de notifications
     */
    function updateNotificationDropdown(notifications) {
        const dropdown = document.getElementById('notificationDropdown');
        if (!dropdown) return;

        dropdown.innerHTML = '';

        if (notifications.length === 0) {
            dropdown.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="bx bx-bell font-size-24 mb-2"></i>
                    <div>Aucune nouvelle notification</div>
                </div>
            `;
            return;
        }

        notifications.forEach(notification => {
            const item = createNotificationItem(notification);
            dropdown.appendChild(item);
        });

        // Ajouter le lien "Voir tout"
        const viewAll = document.createElement('div');
        viewAll.className = 'text-center border-top pt-2 mt-2';
        viewAll.innerHTML = `
            <a href="/notifications" class="btn btn-sm btn-link">
                Voir toutes les notifications
            </a>
        `;
        dropdown.appendChild(viewAll);
    }

    /**
     * Crée un élément de notification pour le dropdown
     */
    function createNotificationItem(notification) {
        const item = document.createElement('a');
        item.href = notification.url || '/notifications';
        item.className = 'dropdown-item d-flex align-items-start py-2';
        item.dataset.notificationId = notification.id;

        item.innerHTML = `
            <div class="flex-shrink-0 me-3">
                <div class="avatar-xs">
                    <span class="avatar-title bg-${notification.color} rounded-circle font-size-16">
                        <i class="bx ${notification.icon}"></i>
                    </span>
                </div>
            </div>
            <div class="flex-grow-1">
                <h6 class="mb-1 font-size-14">${escapeHtml(notification.title)}</h6>
                <div class="font-size-12 text-muted">
                    <p class="mb-1">${escapeHtml(notification.message)}</p>
                    <p class="mb-0">
                        <i class="bx bx-time-five align-middle"></i> ${notification.created_at_human}
                    </p>
                </div>
            </div>
            <div class="flex-shrink-0">
                <button type="button" class="btn btn-sm btn-link text-muted mark-as-read-btn" data-action="mark-read" data-notification-id="${notification.id}">
                    <i class="bx bx-check"></i>
                </button>
            </div>
        `;

        return item;
    }

    /**
     * Marque une notification comme lue
     */
    window.markNotificationAsRead = async function(notificationId) {
        try {
            const response = await fetch(`/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            if (response.ok) {
                // Supprimer la notification du dropdown
                const item = document.querySelector(`[data-notification-id="${notificationId}"]`);
                if (item) {
                    item.remove();
                }

                // Rafraîchir les notifications
                await fetchUnreadNotifications();
            }
        } catch (error) {
            console.error('Erreur lors du marquage de la notification:', error);
        }
    };

    /**
     * Marque toutes les notifications comme lues
     */
    window.markAllNotificationsAsRead = async function() {
        if (!confirm('Marquer toutes les notifications comme lues ?')) {
            return;
        }

        try {
            const response = await fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            if (response.ok) {
                await fetchUnreadNotifications();

                // Si on est sur la page des notifications, recharger
                if (window.location.pathname === '/notifications') {
                    window.location.reload();
                }
            }
        } catch (error) {
            console.error('Erreur lors du marquage de toutes les notifications:', error);
        }
    };

    /**
     * Échappe les caractères HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Démarre le polling des notifications
     */
    function startNotificationPolling() {
        // Récupération initiale
        fetchUnreadNotifications();

        // Polling toutes les 30 secondes
        pollTimer = setInterval(fetchUnreadNotifications, POLL_INTERVAL);
    }

    /**
     * Arrête le polling des notifications
     */
    function stopNotificationPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    // Gestion de la visibilité de la page pour optimiser le polling
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopNotificationPolling();
        } else {
            startNotificationPolling();
        }
    });

    // Démarrer le polling
    startNotificationPolling();

    // Page notifications : gestion des boutons
    const markAllReadBtn = document.getElementById('mark-all-read');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', markAllNotificationsAsRead);
    }

    // Page notifications : marquer comme lu
    document.querySelectorAll('.mark-as-read').forEach(button => {
        button.addEventListener('click', function() {
            const notificationId = this.dataset.notificationId;
            markNotificationAsRead(notificationId);

            // Retirer le bouton et le style
            const listItem = document.querySelector(`.list-group-item[data-notification-id="${notificationId}"]`);
            if (listItem) {
                listItem.classList.remove('bg-light');
                this.remove();
            }
        });
    });

    // Delegated event listeners for data-action attributes (CSP-compliant)
    document.addEventListener('click', function(e) {
        const markAllBtn = e.target.closest('[data-action="mark-all-read"]');
        if (markAllBtn) {
            e.preventDefault();
            markAllNotificationsAsRead();
            return;
        }

        const markReadBtn = e.target.closest('[data-action="mark-read"]');
        if (markReadBtn) {
            e.preventDefault();
            e.stopPropagation();
            var notificationId = markReadBtn.dataset.notificationId;
            markNotificationAsRead(notificationId);
        }
    });
});
