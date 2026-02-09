document.addEventListener('DOMContentLoaded', function () {
    var container = document.getElementById('onboarding-template-list');
    if (!container) return;

    var toggleUrlTemplate = container.dataset.toggleUrl;
    var deleteUrlTemplate = container.dataset.deleteUrl;

    document.addEventListener('click', function (e) {
        var toggleBtn = e.target.closest('[data-action="toggle-template"]');
        if (toggleBtn) {
            var id = toggleBtn.dataset.templateId;
            var name = toggleBtn.dataset.templateName;
            var isActive = toggleBtn.dataset.templateActive === 'true';
            var token = toggleBtn.dataset.token;
            var action = isActive ? 'désactiver' : 'activer';

            if (!confirm('Voulez-vous ' + action + ' le template "' + name + '" ?')) {
                return;
            }

            var form = document.getElementById('toggle-form');
            form.action = toggleUrlTemplate.replace('__ID__', id);
            document.getElementById('toggle-csrf').value = token;
            form.submit();
            return;
        }

        var deleteBtn = e.target.closest('[data-action="delete-template"]');
        if (deleteBtn) {
            var id = deleteBtn.dataset.templateId;
            var name = deleteBtn.dataset.templateName;
            var token = deleteBtn.dataset.token;

            if (!confirm('Voulez-vous vraiment supprimer le template "' + name + '" ?\n\nCette action est irréversible.')) {
                return;
            }

            var form = document.getElementById('delete-form');
            form.action = deleteUrlTemplate.replace('__ID__', id);
            document.getElementById('delete-csrf').value = token;
            form.submit();
        }
    });
});
