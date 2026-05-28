<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo = getConnection();
$user = currentUser();

$estadosMant = [
    'programado'  => ['label' => 'Programado', 'class' => 'bg-secondary'],
    'en_proceso'  => ['label' => 'En proceso', 'class' => 'bg-primary'],
    'completado'  => ['label' => 'Completado', 'class' => 'bg-success'],
    'cancelado'   => ['label' => 'Cancelado', 'class' => 'bg-danger'],
];

$tiposMant = ['Preventivo', 'Correctivo', 'Revisión técnica', 'Cambio de piezas', 'Otro'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM mantenimientos WHERE id = ? AND usuario_id = ?')
                ->execute([$id, $user['id']]);
            flash('info', 'Registro eliminado.');
        }
        redirect('modules/mantenimiento.php');
    }

    if (in_array($action, ['crear', 'editar'], true)) {
        $flota = trim($_POST['flota'] ?? '');
        $tipo = trim($_POST['tipo'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');
        $fecha = $_POST['fecha'] ?? date('Y-m-d');
        $costo = (float) ($_POST['costo'] ?? 0);
        $estado = $_POST['estado'] ?? 'programado';

        if ($flota === '' || $tipo === '') {
            $errors[] = 'Flota y tipo son obligatorios.';
        } elseif (!isset($estadosMant[$estado])) {
            $errors[] = 'Estado no válido.';
        } else {
            $id = (int) ($_POST['id'] ?? 0);
            if ($action === 'editar' && $id > 0) {
                $pdo->prepare(
                    'UPDATE mantenimientos SET fecha=?, flota=?, tipo=?, descripcion=?, costo=?, estado=? WHERE id=? AND usuario_id=?'
                )->execute([$fecha, $flota, $tipo, $observaciones ?: null, $costo, $estado, $id, $user['id']]);
                flash('success', 'Mantenimiento actualizado.');
            } else {
                $pdo->prepare(
                    'INSERT INTO mantenimientos (usuario_id, fecha, flota, tipo, descripcion, costo, estado) VALUES (?,?,?,?,?,?,?)'
                )->execute([$user['id'], $fecha, $flota, $tipo, $observaciones ?: null, $costo, $estado]);
                flash('success', 'Mantenimiento registrado.');
            }
            redirect('modules/mantenimiento.php');
        }
    }
}

$filtro = $_GET['estado'] ?? '';
$buscar = trim($_GET['q'] ?? '');

$sql = 'SELECT m.*, u.nombre AS registrado_por FROM mantenimientos m
        JOIN usuarios u ON u.id = m.usuario_id WHERE m.usuario_id = ?';
$params = [$user['id']];

if ($filtro && isset($estadosMant[$filtro])) {
    $sql .= ' AND m.estado = ?';
    $params[] = $filtro;
}
if ($buscar !== '') {
    $sql .= ' AND (m.flota LIKE ? OR m.tipo LIKE ? OR m.descripcion LIKE ?)';
    $like = '%' . $buscar . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
$sql .= ' ORDER BY m.fecha DESC, m.id DESC LIMIT 200';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll();

$editar = null;
if (!empty($_GET['editar'])) {
    $s = $pdo->prepare('SELECT * FROM mantenimientos WHERE id = ? AND usuario_id = ?');
    $s->execute([(int) $_GET['editar'], $user['id']]);
    $editar = $s->fetch();
}

$pageTitle = 'Mantenimiento';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="main-content container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="bi bi-tools text-warning"></i> Mantenimiento de flota</h1>
            <p class="text-muted mb-0">Registro de trabajos, costos y observaciones</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMant" id="btnNuevoMant">
            <i class="bi bi-plus-lg"></i> Nuevo registro
        </button>
    </div>

    <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger"><?= e($err) ?></div>
    <?php endforeach; ?>

    <div class="card-panel mb-3">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($estadosMant as $k => $v): ?>
                        <option value="<?= e($k) ?>" <?= $filtro === $k ? 'selected' : '' ?>><?= e($v['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Buscar (flota, tipo, observaciones)</label>
                <input type="search" name="q" class="form-control" value="<?= e($buscar) ?>" placeholder="Ej: F-102, preventivo...">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Buscar</button>
            </div>
        </form>
    </div>

    <div class="card-panel p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-app table-hover mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Flota</th>
                        <th>Tipo</th>
                        <th>Observaciones</th>
                        <th>Costo</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registros)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                No hay registros. Pulse «Nuevo registro» para agregar uno.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($registros as $r): ?>
                        <tr>
                            <td class="text-nowrap"><?= e(date('d/m/Y', strtotime($r['fecha']))) ?></td>
                            <td><strong><?= e($r['flota']) ?></strong></td>
                            <td><?= e($r['tipo']) ?></td>
                            <td class="obs-cell">
                                <?php if (!empty($r['descripcion'])): ?>
                                    <span class="obs-text" title="<?= e($r['descripcion']) ?>"><?= e($r['descripcion']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap">S/ <?= number_format((float) $r['costo'], 2) ?></td>
                            <td>
                                <span class="badge <?= e($estadosMant[$r['estado']]['class'] ?? 'bg-secondary') ?>">
                                    <?= e($estadosMant[$r['estado']]['label'] ?? $r['estado']) ?>
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary btn-editar-mant"
                                        data-id="<?= (int) $r['id'] ?>"
                                        data-fecha="<?= e($r['fecha']) ?>"
                                        data-flota="<?= e($r['flota']) ?>"
                                        data-tipo="<?= e($r['tipo']) ?>"
                                        data-observaciones="<?= e($r['descripcion'] ?? '') ?>"
                                        data-costo="<?= e($r['costo']) ?>"
                                        data-estado="<?= e($r['estado']) ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar este registro?')">
                                    <input type="hidden" name="action" value="eliminar">
                                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div class="modal fade" id="modalMant" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" id="formMant">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMantTitle">Nuevo mantenimiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="mantAction" value="crear">
                    <input type="hidden" name="id" id="mantId" value="">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha" id="mantFecha" class="form-control" required
                                   value="<?= e($editar['fecha'] ?? date('Y-m-d')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Flota *</label>
                            <input type="text" name="flota" id="mantFlota" class="form-control" required
                                   placeholder="Ej: F-102" value="<?= e($editar['flota'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipo *</label>
                            <select name="tipo" id="mantTipo" class="form-select" required>
                                <?php foreach ($tiposMant as $t): ?>
                                    <option value="<?= e($t) ?>" <?= ($editar['tipo'] ?? '') === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" id="mantObs" class="form-control" rows="4"
                                      placeholder="Detalle del trabajo, repuestos, fallas encontradas..."><?= e($editar['descripcion'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Costo (S/)</label>
                            <input type="number" step="0.01" min="0" name="costo" id="mantCosto" class="form-control"
                                   value="<?= e($editar['costo'] ?? '0') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select name="estado" id="mantEstado" class="form-select">
                                <?php foreach ($estadosMant as $k => $v): ?>
                                    <option value="<?= e($k) ?>" <?= ($editar['estado'] ?? 'programado') === $k ? 'selected' : '' ?>>
                                        <?= e($v['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalMant');
    const form = document.getElementById('formMant');
    const title = document.getElementById('modalMantTitle');

    function limpiarForm() {
        document.getElementById('mantAction').value = 'crear';
        document.getElementById('mantId').value = '';
        document.getElementById('mantFecha').value = new Date().toISOString().split('T')[0];
        document.getElementById('mantFlota').value = '';
        document.getElementById('mantTipo').selectedIndex = 0;
        document.getElementById('mantObs').value = '';
        document.getElementById('mantCosto').value = '0';
        document.getElementById('mantEstado').value = 'programado';
        title.textContent = 'Nuevo mantenimiento';
    }

    document.getElementById('btnNuevoMant')?.addEventListener('click', limpiarForm);

    document.querySelectorAll('.btn-editar-mant').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('mantAction').value = 'editar';
            document.getElementById('mantId').value = btn.dataset.id;
            document.getElementById('mantFecha').value = btn.dataset.fecha;
            document.getElementById('mantFlota').value = btn.dataset.flota;
            document.getElementById('mantTipo').value = btn.dataset.tipo;
            document.getElementById('mantObs').value = btn.dataset.observaciones || '';
            document.getElementById('mantCosto').value = btn.dataset.costo || '0';
            document.getElementById('mantEstado').value = btn.dataset.estado;
            title.textContent = 'Editar mantenimiento';
            bootstrap.Modal.getOrCreateInstance(modal).show();
        });
    });

    <?php if ($editar): ?>
    document.getElementById('mantAction').value = 'editar';
    document.getElementById('mantId').value = '<?= (int) $editar['id'] ?>';
    title.textContent = 'Editar mantenimiento';
    bootstrap.Modal.getOrCreateInstance(modal).show();
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
