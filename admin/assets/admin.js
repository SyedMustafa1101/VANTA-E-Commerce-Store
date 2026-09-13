(() => {
    const body = document.body;
    const sidebar = document.querySelector('[data-sidebar]');
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const close = () => { body.classList.remove('sidebar-open'); toggle?.setAttribute('aria-expanded', 'false'); };
    toggle?.addEventListener('click', () => {
        const open = body.classList.toggle('sidebar-open');
        toggle.setAttribute('aria-expanded', String(open));
    });
    document.querySelector('[data-sidebar-close]')?.addEventListener('click', close);
    window.addEventListener('resize', () => { if (innerWidth > 1024) close(); });

    document.querySelectorAll('[data-toast-close]').forEach((button) => button.addEventListener('click', () => button.closest('[data-toast]')?.remove()));
    document.querySelectorAll('form[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => {
        const message = form.dataset.confirm || 'Confirm this action?';
        if (!window.confirm(message)) event.preventDefault();
    }));
    document.querySelectorAll('[data-confirm]:not(form), button[value="archive"]').forEach((control) => control.addEventListener('click', (event) => {
        if (!window.confirm(control.dataset.confirm || 'Confirm this action?')) event.preventDefault();
    }));

    const dialog = document.querySelector('[data-dialog]');
    document.querySelectorAll('[data-dialog-open]').forEach((button) => button.addEventListener('click', () => {
        if (!dialog) return;
        dialog.querySelector('[name="variant_id"]')?.setAttribute('value', button.dataset.variantId || '');
        const label = dialog.querySelector('[data-dialog-label]');
        if (label) label.textContent = button.dataset.variantLabel || 'Stock adjustment';
        dialog.showModal();
    }));
    dialog?.querySelectorAll('[data-dialog-close]').forEach((button) => button.addEventListener('click', () => dialog.close()));

    document.querySelectorAll('[data-slug-source]').forEach((source) => {
        const target = document.querySelector(source.dataset.slugSource);
        if (!target) return;
        let touched = target.value !== '';
        target.addEventListener('input', () => { touched = target.value !== ''; });
        source.addEventListener('input', () => {
            if (touched) return;
            target.value = source.value.toLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        });
    });

    const preview = document.querySelector('[data-upload-preview]');
    document.querySelector('[data-image-input]')?.addEventListener('change', (event) => {
        if (!preview) return;
        preview.replaceChildren();
        [...event.target.files].slice(0, 10).forEach((file) => {
            const img = new Image(); img.alt = ''; img.src = URL.createObjectURL(file); img.onload = () => URL.revokeObjectURL(img.src); preview.append(img);
        });
    });

    const css = getComputedStyle(document.documentElement);
    document.querySelectorAll('canvas[data-chart-source]').forEach((canvas) => {
        const source = document.getElementById(canvas.dataset.chartSource);
        if (!source) return;
        let rows = []; try { rows = JSON.parse(source.textContent); } catch { return; }
        if (!Array.isArray(rows) || rows.length === 0) return;
        const ratio = Math.max(1, devicePixelRatio || 1); const width = canvas.clientWidth; const height = canvas.clientHeight;
        canvas.width = width * ratio; canvas.height = height * ratio; const ctx = canvas.getContext('2d'); ctx.scale(ratio, ratio);
        const values = rows.map((row) => Number(row.value || row.revenue || row.count || 0)); const max = Math.max(...values, 1);
        const pad = { top: 18, right: 12, bottom: 32, left: 12 }; const innerW = width - pad.left - pad.right; const innerH = height - pad.top - pad.bottom;
        ctx.strokeStyle = 'rgba(245,245,245,.12)'; ctx.lineWidth = 1;
        for (let i = 0; i <= 3; i++) { const y = pad.top + innerH * i / 3; ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(width - pad.right, y); ctx.stroke(); }
        ctx.strokeStyle = css.getPropertyValue('--lime').trim() || '#b7ff2a'; ctx.lineWidth = 2; ctx.beginPath();
        values.forEach((value, index) => { const x = pad.left + innerW * (values.length === 1 ? .5 : index / (values.length - 1)); const y = pad.top + innerH - (value / max) * innerH; index ? ctx.lineTo(x, y) : ctx.moveTo(x, y); }); ctx.stroke();
        ctx.fillStyle = '#b7ff2a'; values.forEach((value, index) => { const x = pad.left + innerW * (values.length === 1 ? .5 : index / (values.length - 1)); const y = pad.top + innerH - (value / max) * innerH; ctx.beginPath(); ctx.arc(x, y, 2.5, 0, Math.PI * 2); ctx.fill(); });
        ctx.fillStyle = 'rgba(245,245,245,.56)'; ctx.font = '11px Manrope, sans-serif'; ctx.textAlign = 'center';
        const labels = rows.map((row) => String(row.label || row.status || '')); const points = labels.length > 6 ? [0, Math.floor((labels.length - 1) / 2), labels.length - 1] : labels.map((_, i) => i);
        points.forEach((i) => { const x = pad.left + innerW * (labels.length === 1 ? .5 : i / (labels.length - 1)); ctx.fillText(labels[i].slice(0, 12), x, height - 9); });
    });
})();
