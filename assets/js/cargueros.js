(function () {
    const cfg = window.CARGUEROS_CONFIG || {};
    const API = cfg.apiUrl;
    const turno = cfg.turno;
    let fecha = cfg.fecha || new Date().toISOString().split('T')[0];

    const tbody = document.getElementById('tablaBody');
    const fechaInput = document.getElementById('fechaSelector');
    const formNuevo = document.getElementById('formNuevoCarguero');
    const badgeTotal = document.getElementById('badgeTotal');

    function syncFecha() {
        if (fechaInput) fechaInput.value = fecha;
        const d = document.getElementById('fechaDisplay');
        if (d) {
            const p = fecha.split('-');
            d.textContent = 'Fecha: ' + (p[2] ? p[2] + '/' + p[1] + '/' + p[0] : fecha);
        }
    }
    syncFecha();

    if (fechaInput) {
        fechaInput.addEventListener('change', () => {
            window.location.href = window.location.pathname + '?fecha=' + fechaInput.value;
        });
    }

    function toast(msg, type) {
        type = type || 'success';
        const el = document.getElementById('toastMsg');
        if (!el) return;
        el.className = 'alert alert-' + type + ' shadow-sm';
        el.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> ' + msg;
        el.classList.remove('d-none');
        setTimeout(() => el.classList.add('d-none'), 4000);
    }

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s ?? '';
        return d.innerHTML;
    }

    async function apiCall(action, body) {
        const url = API + (action ? '?action=' + action : '');
        const opts = {
            method: body ? 'POST' : 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        };
        if (body) {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(Object.assign({ fecha, turno }, body));
        }
        const res = await fetch(url, opts);
        const text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('API response:', text.slice(0, 400));
            throw new Error('Error de servidor. Verifique MySQL y sesión activa.');
        }
        if (!data.ok && res.status === 401) {
            throw new Error(data.message || 'Sesión expirada.');
        }
        return data;
    }

    function leerFila(tr) {
        return {
            id: parseInt(tr.dataset.id, 10) || 0,
            destino: tr.querySelector('.inp-destino')?.value.trim() || '',
            flota: tr.querySelector('.inp-flota')?.value.trim() || '',
            hora_entrada: tr.querySelector('.inp-entrada')?.value || '',
            hora_salida: tr.querySelector('.inp-salida')?.value || '',
            observaciones: tr.querySelector('.inp-obs')?.value.trim() || '',
        };
    }

    function actualizarEstadoFila(tr, data) {
        if (!data) return;
        tr.dataset.id = data.id;
        const badge = tr.querySelector('.estado-badge');
        if (badge) {
            badge.className = 'estado-badge ' + (data.estado_class || 'pendiente');
            badge.textContent = data.estado_label || 'PENDIENTE';
        }
        if (data.hora_entrada !== undefined) {
            const inp = tr.querySelector('.inp-entrada');
            if (inp) inp.value = data.hora_entrada || '';
        }
        if (data.hora_salida !== undefined) {
            const inp = tr.querySelector('.inp-salida');
            if (inp) inp.value = data.hora_salida || '';
        }
        const salidaBtn = tr.querySelector('.btn-salida');
        const salidaInp = tr.querySelector('.inp-salida');
        const tieneEntrada = !!(tr.querySelector('.inp-entrada')?.value);
        if (salidaBtn) salidaBtn.disabled = !tieneEntrada;
        if (salidaInp) salidaInp.disabled = !tieneEntrada;
    }

    function filaHtml(d) {
        const tieneEntrada = !!d.hora_entrada;
        return `<tr data-id="${d.id || ''}">
            <td><input type="text" class="form-control form-control-sm inp-destino" value="${esc(d.destino)}"></td>
            <td><input type="text" class="form-control form-control-sm inp-flota" value="${esc(d.flota)}"></td>
            <td><div class="d-flex gap-1"><input type="time" class="form-control form-control-sm inp-entrada" value="${d.hora_entrada || ''}">
            <button type="button" class="btn btn-success btn-sm btn-llegada"><i class="bi bi-box-arrow-in-right"></i></button></div></td>
            <td><div class="d-flex gap-1"><input type="time" class="form-control form-control-sm inp-salida" value="${d.hora_salida || ''}" ${tieneEntrada ? '' : 'disabled'}>
            <button type="button" class="btn btn-primary btn-sm btn-salida" ${tieneEntrada ? '' : 'disabled'}><i class="bi bi-box-arrow-right"></i></button></div></td>
            <td><span class="estado-badge ${d.estado_class || 'pendiente'}">${d.estado_label || 'PENDIENTE'}</span></td>
            <td><input type="text" class="form-control form-control-sm inp-obs" value="${esc(d.observaciones || '')}"></td>
            <td class="text-end text-nowrap">
            <button type="button" class="btn btn-outline-secondary btn-sm btn-guardar"><i class="bi bi-save"></i></button>
            <button type="button" class="btn btn-outline-danger btn-sm btn-eliminar"><i class="bi bi-trash"></i></button></td></tr>`;
    }

    function bindFila(tr) {
        tr.querySelector('.btn-llegada')?.addEventListener('click', () => marcarLlegada(tr, true));
        tr.querySelector('.btn-salida')?.addEventListener('click', () => marcarSalida(tr, true));
        tr.querySelector('.btn-guardar')?.addEventListener('click', () => guardarFila(tr));
        tr.querySelector('.btn-eliminar')?.addEventListener('click', () => eliminarFila(tr));
    }

    function quitarFilaVacia() {
        document.getElementById('filaVacia')?.remove();
    }

    function setTotal(n) {
        if (badgeTotal) badgeTotal.textContent = n + ' registro(s)';
    }

    function renderLista(lista) {
        if (!tbody) return;
        tbody.innerHTML = '';
        if (!lista || !lista.length) {
            tbody.innerHTML = '<tr id="filaVacia"><td colspan="7" class="text-center text-muted py-4">Sin registros. Agregue un carguero arriba.</td></tr>';
            setTotal(0);
            return;
        }
        lista.forEach((d) => {
            tbody.insertAdjacentHTML('beforeend', filaHtml(d));
            bindFila(tbody.lastElementChild);
        });
        setTotal(lista.length);
    }

    async function cargarLista() {
        try {
            const res = await apiCall('', null);
            const url = API + '?fecha=' + encodeURIComponent(fecha) + '&turno=' + encodeURIComponent(turno);
            const r2 = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const data = JSON.parse(await r2.text());
            if (data.ok) {
                renderLista(data.data);
            } else {
                toast(data.message || 'Error al cargar', 'danger');
            }
        } catch (err) {
            toast(err.message, 'danger');
        }
    }

    async function agregarNuevo(e) {
        e.preventDefault();
        const fd = new FormData(formNuevo);
        try {
            const res = await apiCall('crear', {
                destino: fd.get('destino')?.toString().trim(),
                flota: fd.get('flota')?.toString().trim(),
                observaciones: fd.get('observaciones')?.toString().trim(),
            });
            if (res.ok) {
                quitarFilaVacia();
                tbody.insertAdjacentHTML('beforeend', filaHtml(res.data));
                bindFila(tbody.lastElementChild);
                setTotal(tbody.querySelectorAll('tr[data-id]').length);
                formNuevo.reset();
                toast(res.message || 'Carguero agregado a la lista.');
            } else {
                toast(res.message, 'danger');
            }
        } catch (err) {
            toast(err.message, 'danger');
        }
    }

    async function marcarLlegada(tr, ahora) {
        const d = leerFila(tr);
        if (!d.destino || !d.flota) {
            toast('Complete destino y flota.', 'warning');
            return;
        }
        if (ahora) {
            const t = new Date();
            d.hora_entrada = String(t.getHours()).padStart(2, '0') + ':' + String(t.getMinutes()).padStart(2, '0');
            tr.querySelector('.inp-entrada').value = d.hora_entrada;
        }
        if (!d.hora_entrada) {
            toast('Indique hora de llegada.', 'warning');
            return;
        }
        try {
            const res = await apiCall('marcar_entrada', Object.assign({}, d, { hora_entrada: d.hora_entrada }));
            if (res.ok) {
                actualizarEstadoFila(tr, res.data);
                toast(res.message || 'Llegada registrada.');
            } else {
                toast(res.message, 'danger');
            }
        } catch (err) {
            toast(err.message, 'danger');
        }
    }

    async function marcarSalida(tr, ahora) {
        const d = leerFila(tr);
        if (ahora) {
            const t = new Date();
            d.hora_salida = String(t.getHours()).padStart(2, '0') + ':' + String(t.getMinutes()).padStart(2, '0');
            tr.querySelector('.inp-salida').value = d.hora_salida;
        }
        if (!d.hora_salida) {
            toast('Indique hora de salida.', 'warning');
            return;
        }
        try {
            const res = await apiCall('marcar_salida', { id: d.id, hora_salida: d.hora_salida, observaciones: d.observaciones });
            if (res.ok) {
                actualizarEstadoFila(tr, res.data);
                toast(res.message || 'Salida registrada.');
            } else {
                toast(res.message, 'danger');
            }
        } catch (err) {
            toast(err.message, 'danger');
        }
    }

    async function guardarFila(tr) {
        const d = leerFila(tr);
        if (!d.id) {
            toast('Guarde primero el carguero (agregar a la lista).', 'warning');
            return;
        }
        if (!d.destino || !d.flota) {
            toast('Complete destino y flota.', 'warning');
            return;
        }
        try {
            const res = await apiCall('actualizar', d);
            if (res.ok) {
                actualizarEstadoFila(tr, res.data);
                toast('Cambios guardados.');
            } else {
                toast(res.message, 'danger');
            }
        } catch (err) {
            toast(err.message, 'danger');
        }
    }

    async function eliminarFila(tr) {
        const id = parseInt(tr.dataset.id, 10);
        if (id && !confirm('¿Eliminar este registro?')) return;
        if (id) {
            try {
                const res = await fetch(API + '?id=' + id, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = JSON.parse(await res.text());
                if (!data.ok) {
                    toast(data.message, 'danger');
                    return;
                }
            } catch (err) {
                toast(err.message, 'danger');
                return;
            }
        }
        tr.remove();
        const n = tbody.querySelectorAll('tr[data-id]').length;
        setTotal(n);
        if (n === 0) {
            tbody.innerHTML = '<tr id="filaVacia"><td colspan="7" class="text-center text-muted py-4">Sin registros.</td></tr>';
        }
        toast('Eliminado.', 'info');
    }

    formNuevo?.addEventListener('submit', agregarNuevo);
    tbody?.querySelectorAll('tr[data-id]').forEach(bindFila);

    document.getElementById('btnExportar')?.addEventListener('click', () => {
        window.location.href = cfg.exportUrl + '?fecha=' + fecha + '&turno=' + turno;
    });

    document.getElementById('btnNuevoDia')?.addEventListener('click', async () => {
        if (!confirm('¿Eliminar todos los registros del día?')) return;
        try {
            const res = await apiCall('limpiar_dia', {});
            if (res.ok) {
                renderLista([]);
                toast('Día limpiado.');
            }
        } catch (err) {
            toast(err.message, 'danger');
        }
    });

    if (cfg.inicial && cfg.inicial.length && tbody) {
        setTotal(cfg.inicial.length);
    }
})();
