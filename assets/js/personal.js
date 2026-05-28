(function () {
    const cfg = window.PERSONAL_CONFIG || {};
    const API = cfg.apiUrl;
    if (!API) return;

    const toastEl = document.getElementById('personalToast');

    function toast(msg, type) {
        if (!toastEl) return;
        toastEl.className = 'alert alert-' + (type || 'success') + ' shadow-sm';
        toastEl.textContent = msg;
        toastEl.classList.remove('d-none');
        setTimeout(() => toastEl.classList.add('d-none'), 3500);
    }

    async function apiPost(action, body) {
        const res = await fetch(API, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(Object.assign({ action }, body)),
        });
        const text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error('Error de servidor. Verifique sesión y base de datos.');
        }
        if (!data.ok) {
            throw new Error(data.message || 'Error');
        }
        return data;
    }

    function actualizarResumen(resumen) {
        if (!resumen) return;
        document.querySelectorAll('[data-resumen]').forEach((el) => {
            const k = el.dataset.resumen;
            if (resumen[k] !== undefined) {
                el.textContent = resumen[k];
            }
        });
    }

    function actualizarFila(tr, estado) {
        tr.classList.remove('row-sin', 'row-presente', 'row-tardanza', 'row-ausente', 'row-no_regreso_almuerzo', 'row-justificado');
        tr.classList.add('row-' + estado);
        tr.querySelectorAll('.btn-estado').forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.estado === estado);
        });
        const badge = tr.querySelector('.badge-estado');
        const labels = cfg.estados || {};
        if (badge && labels[estado]) {
            badge.className = 'badge badge-estado ' + labels[estado].badge;
            badge.textContent = labels[estado].label;
        }
    }

    document.getElementById('tablaAsistencia')?.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-estado');
        if (!btn) return;
        const tr = btn.closest('tr');
        const pid = tr?.dataset.id;
        const fecha = cfg.fecha;
        const estado = btn.dataset.estado;
        const obs = tr.querySelector('.inp-obs')?.value?.trim() || '';

        try {
            btn.disabled = true;
            const res = await apiPost('marcar_asistencia', {
                personal_id: pid,
                fecha,
                estado,
                observaciones: obs,
            });
            actualizarFila(tr, estado);
            actualizarResumen(res.resumen);
            toast(res.message || 'Guardado');
        } catch (err) {
            toast(err.message, 'danger');
        } finally {
            btn.disabled = false;
        }
    });

    document.getElementById('calDescansos')?.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-toggle-descanso');
        if (!btn) return;
        const celda = btn.closest('.cal-cell');
        const pid = btn.dataset.personalId;
        const fecha = btn.dataset.fecha;
        const descansa = btn.dataset.descanso !== '1';

        try {
            btn.disabled = true;
            await apiPost('toggle_descanso', { personal_id: pid, fecha, descansa: descansa ? 1 : 0 });
            const esDescanso = descansa;
            celda.classList.toggle('descanso', esDescanso);
            celda.classList.toggle('trabaja', !esDescanso);
            btn.dataset.descanso = esDescanso ? '1' : '0';
            btn.textContent = esDescanso ? 'D' : '·';
            btn.title = esDescanso ? 'Quitar descanso' : 'Marcar descanso';
            toast(esDescanso ? 'Descanso marcado' : 'Día laboral', 'info');
        } catch (err) {
            toast(err.message, 'danger');
        } finally {
            btn.disabled = false;
        }
    });
})();
