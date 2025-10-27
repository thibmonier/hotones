(function(){
  const CELL_WIDTH = 40; // must match CSS

  function qs(sel, root=document){ return root.querySelector(sel); }
  function qsa(sel, root=document){ return Array.from(root.querySelectorAll(sel)); }

  function dateToYMD(d){ return d.toISOString().slice(0,10); }

  function initDragDrop(){
    let dragging = null;

    qsa('.plan-block').forEach(block => {
      block.addEventListener('dragstart', (e) => {
        dragging = block;
        e.dataTransfer.setData('text/plain', block.dataset.id);
        e.dataTransfer.effectAllowed = 'move';
      });
    });

    qsa('.drop-target').forEach(cell => {
      cell.addEventListener('dragover', (e) => { e.preventDefault(); e.dataTransfer.dropEffect = 'move'; });
      cell.addEventListener('drop', async (e) => {
        e.preventDefault();
        if (!dragging) return;
        const id = dragging.dataset.id;
        const token = dragging.dataset.tokenMove;
        const targetDate = cell.dataset.date;
        try {
          const res = await fetch(window.PLANNING_CONFIG.moveUrl.replace('{id}', id), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ _token: token, targetDate })
          });
          const data = await res.json();
          if (!res.ok) throw new Error(data.error || 'Move failed');
          // Update attributes
          dragging.dataset.start = data.startDate;
          dragging.dataset.end = data.endDate;
          // Reposition visually
          const leftDays = Math.floor((new Date(targetDate) - new Date(qs('[data-date]')?.dataset.date)) / 86400000);
          dragging.style.left = (leftDays * CELL_WIDTH) + 'px';
        } catch (err) {
          alert(err.message);
        } finally {
          dragging = null;
        }
      });
    });
  }

  function initEditModal(){
    const modalEl = qs('#planEditModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    let currentBlock = null;

    qsa('.plan-block').forEach(block => {
      block.addEventListener('click', () => {
        currentBlock = block;
        const form = qs('#planEditForm');
        form.startDate.value = block.dataset.start;
        form.endDate.value = block.dataset.end;
        form.dailyHours.value = block.querySelector('span:nth-child(2)')?.textContent.replace('h/j','') || 8;
        form.status.value = block.dataset.status;
        form.notes.value = '';
        modal.show();
      });
    });

    qs('#planEditSave')?.addEventListener('click', async () => {
      if (!currentBlock) return;
      const id = currentBlock.dataset.id;
      const token = currentBlock.dataset.tokenEdit;
      const form = qs('#planEditForm');
      const payload = {
        _token: token,
        startDate: form.startDate.value,
        endDate: form.endDate.value,
        dailyHours: form.dailyHours.value,
        status: form.status.value,
        notes: form.notes.value
      };
      try {
        const res = await fetch(window.PLANNING_CONFIG.updateUrl.replace('{id}', id), {
          method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Update failed');
        // Update block UI
        currentBlock.dataset.start = payload.startDate;
        currentBlock.dataset.end = payload.endDate;
        currentBlock.dataset.status = payload.status;
        const dh = parseFloat(payload.dailyHours).toFixed(2);
        currentBlock.querySelector('span:nth-child(2)').textContent = dh + 'h/j';
        // Adjust left/width
        const tlStartCell = qs('.drop-target');
        if (tlStartCell) {
          const tlStart = new Date(tlStartCell.dataset.date);
          const leftDays = Math.floor((new Date(payload.startDate) - tlStart) / 86400000);
          const totalDays = Math.floor((new Date(payload.endDate) - new Date(payload.startDate)) / 86400000) + 1;
          currentBlock.style.left = (leftDays * CELL_WIDTH) + 'px';
          currentBlock.style.width = (totalDays * CELL_WIDTH) + 'px';
        }
        modal.hide();
      } catch (err) {
        alert(err.message);
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function(){
    initDragDrop();
    initEditModal();
  });
})();