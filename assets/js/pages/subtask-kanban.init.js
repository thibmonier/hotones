/*
Custom subtask kanban init
*/
(function() {
  const containers = [
    document.getElementById('upcoming-task'),
    document.getElementById('inprogress-task'),
    document.getElementById('complete-task')
  ].filter(Boolean);

  if (containers.length === 0 || typeof dragula === 'undefined') return;

  const drake = dragula(containers);

  function computePositions(container) {
    const map = {};
    Array.from(container.querySelectorAll('.kanban-card')).forEach((el, idx) => {
      map[el.dataset.id] = idx + 1;
    });
    return map;
  }

  drake.on('drop', function(el, target, source, sibling) {
    const id = el.dataset.id;
    const status = target.getAttribute('data-status');

    // Build positions map for target list
    const positions = computePositions(target);

    fetch(`/project/subtasks/${id}/move`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status, positions })
    }).catch(() => {
      // noop; could display toast
    });
  });

  // RAF inline update
  document.addEventListener('click', function(e) {
    const a = e.target.closest('.edit-raf');
    if (a) {
      e.preventDefault();
      const id = a.dataset.stId;
      const card = a.closest('.kanban-card');
      const current = card.querySelector('.raf');
      const curValText = current.textContent.replace('j','').trim();
      const curHours = parseFloat(curValText) * 8; // back to hours
      const input = prompt('Nouveau RAF (en jours):', (curHours/8).toFixed(1));
      if (input === null) return;
      const newHours = parseFloat(input) * 8;
      if (isNaN(newHours) || newHours < 0) return;

      fetch(`/project/subtasks/${id}/update-raf`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ remainingHours: newHours.toFixed(2) })
      }).then(r => r.json()).then(data => {
        if (data && data.ok) {
          current.textContent = (newHours/8).toFixed(1) + 'j';
          const bar = card.querySelector('.progress-bar');
          const pct = card.querySelector('.d-flex small');
          if (bar) bar.style.width = data.progress + '%';
          if (pct) pct.textContent = data.progress + '%';
        }
      });
      return;
    }

    const editBtn = e.target.closest('.edit-subtask');
    if (editBtn) {
      e.preventDefault();
      const id = editBtn.dataset.stId;
      fetch(`/project/subtasks/${id}/edit`, { method: 'GET' })
        .then(r => r.text())
        .then(html => {
          const container = document.getElementById('subtaskModalContainer');
          container.innerHTML = html;
          const modalEl = container.querySelector('.modal');
          // eslint-disable-next-line no-undef
          const modal = new bootstrap.Modal(modalEl);
          modal.show();

          // Save handler
          container.querySelector('[data-action="save"]').addEventListener('click', function() {
            const form = container.querySelector('form.subtask-edit-form');
            const formData = new FormData(form);
            fetch(form.getAttribute('action'), {
              method: 'POST',
              body: formData
            }).then(async r => {
              const contentType = r.headers.get('content-type') || '';
              if (contentType.includes('application/json')) {
                const data = await r.json();
                if (r.ok && data.ok) {
                  // Replace card HTML
                  const card = document.querySelector(`.kanban-card[data-id="${id}"]`);
                  if (card) {
                    const temp = document.createElement('div');
                    temp.innerHTML = data.html.trim();
                    const newCard = temp.firstElementChild;
                    card.replaceWith(newCard);
                  }
                  modal.hide();
                } else {
                  // Re-render modal with errors
                  container.innerHTML = data.html;
                }
              } else {
                // Fallback: replace modal
                container.innerHTML = await r.text();
              }
            });
          });
        });
    }
  });
})();
