<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/personal_helpers.php';
requireLogin();

$pdo = getConnection();
$fecha = $_GET['fecha'] ?? date('Y-m-d');
$filtroEstado = $_GET['estado'] ?? '';
$buscar = trim($_GET['q'] ?? '');

$datos = obtenerAsistenciaDia($pdo, $fecha);
$resumen = $datos['resumen'];
$estados = estadosAsistencia();

$filas = $datos['filas'];
if ($buscar !== '') {
    $filas = array_values(array_filter($filas, fn($f) => stripos($f['nombre'], $buscar) !== false));
}
if ($filtroEstado === '_sin') {
    $filas = array_values(array_filter($filas, fn($f) => empty($f['asistencia'])));
} elseif ($filtroEstado !== '') {
    $filas = array_values(array_filter($filas, fn($f) => ($f['asistencia']['estado'] ?? '') === $filtroEstado));
}

$pageTitle = 'Asistencia';
$extraJs = [asset('js/personal.js?v=1')];
$inlineJs = 'window.PERSONAL_CONFIG=' . json_encode([
    'apiUrl' => url('api/personal.php'),
    'fecha'  => $fecha,
    'estados' => $estados,
], JSON_UNESCAPED_UNICODE) . ';';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="main-content container-fluid personal-module">
    <header class="mb-2">
        <h1 class="h4 mb-0">Asistencia del día</h1>
        <p class="text-muted small mb-0"><?= e(date('l d/m/Y', strtotime($fecha))) ?></p>
    </header>

    <?= personalSubnav('asistencia') ?>

    <div id="personalToast" class="alert alert-success d-none shadow-sm" role="alert"></div>

    <div class="row g-2 mb-3">
        <div class="col-4 col-md-2"><span class="pill pill-ok"><span data-resumen="presente"><?= $resumen['presente'] ?></span> presentes</span></div>
        <div class="col-4 col-md-2"><span class="pill pill-warn"><span data-resumen="tardanza"><?= $resumen['tardanza'] ?></span> tarde</span></div>
        <div class="col-4 col-md-2"><span class="pill pill-bad"><span data-resumen="ausente"><?= $resumen['ausente'] ?></span> faltaron</span></div>
        <div class="col-4 col-md-2"><span class="pill" style="background:#fff3e0;color:#e65100"><span data-resumen="no_regreso_almuerzo"><?= $resumen['no_regreso_almuerzo'] ?></span> sin regreso</span></div>
        <div class="col-4 col-md-2"><span class="pill pill-total"><span data-resumen="sin_marcar"><?= $resumen['sin_marcar'] ?></span> sin marcar</span></div>
        <div class="col-4 col-md-2"><span class="pill pill-rest"><span data-resumen="descanso"><?= $resumen['descanso'] ?></span> en descanso</span></div>
    </div>

    <div class="card-panel mb-3">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label small">Fecha</label>
                <input type="date" name="fecha" class="form-control" value="<?= e($fecha) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small">Filtrar estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($estados as $k => $info): ?>
                        <option value="<?= e($k) ?>" <?= $filtroEstado === $k ? 'selected' : '' ?>><?= e($info['label']) ?></option>
                    <?php endforeach; ?>
                    <option value="_sin" <?= $filtroEstado === '_sin' ? 'selected' : '' ?>>Sin marcar</option>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small">Buscar</label>
                <input type="search" name="q" class="form-control" placeholder="Nombre del trabajador..." value="<?= e($buscar) ?>">
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Filtrar</button>
            </div>
        </form>
    </div>

    <?php if (empty($datos['filas']) && $resumen['total'] === 0): ?>
        <div class="alert alert-warning">
            No hay trabajadores. <a href="<?= url('modules/personal/equipo.php') ?>">Agregue su equipo</a> primero.
        </div>
    <?php else: ?>

    <div class="card-panel p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-app table-hover mb-0" id="tablaAsistencia">
                <thead>
                    <tr>
                        <th>Trabajador</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Hora</th>
                        <th>Observaciones</th>
                        <th>Marcar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filas)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Sin resultados para este filtro.</td></tr>
                    <?php else: ?>
                        <?php foreach ($filas as $f):
                            $a = $f['asistencia'];
                            $est = $a['estado'] ?? '';
                            $rowClass = $est ? 'row-' . $est : ($f['en_descanso'] ? 'row-descanso' : 'row-sin');
                        ?>
                        <tr data-id="<?= (int) $f['id'] ?>" class="<?= e($rowClass) ?>">
                            <td>
                                <strong><?= e($f['nombre']) ?></strong>
                                <?php if ($f['en_descanso']): ?>
                                    <span class="badge bg-secondary ms-1">Descanso</span>
                                <?php endif; ?>
                            </td>
                            <td><?= rolBadge($f['rol']) ?></td>
                            <td>
                                <?php if ($est): ?>
                                    <?= estadoAsistenciaBadge($est) ?>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border badge-estado">Sin marcar</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap"><?= $a && $a['hora_entrada'] ? e($a['hora_entrada']) : '—' ?></td>
                            <td>
                                <input type="text" class="form-control form-control-sm inp-obs"
                                       placeholder="Nota opcional"
                                       value="<?= e($a['observaciones'] ?? '') ?>">
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm flex-wrap">
                                    <?php foreach (['presente' => 'btn-success', 'tardanza' => 'btn-warning', 'ausente' => 'btn-danger', 'no_regreso_almuerzo' => 'btn-orange'] as $key => $cls): ?>
                                    <button type="button" class="btn <?= $cls ?> btn-estado <?= $est === $key ? 'active' : '' ?>"
                                            data-estado="<?= e($key) ?>" title="<?= e($estados[$key]['label']) ?>">
                                        <i class="bi <?= e($estados[$key]['icon']) ?>"></i>
                                    </button>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <p class="small text-muted mt-2 mb-0">
        <i class="bi bi-lightning-charge"></i> Los cambios se guardan al instante sin recargar la página.
    </p>

    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
