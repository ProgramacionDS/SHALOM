<?php
if (!isset($turno)) {
    die('Turno requerido');
}

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/cargueros_helpers.php';
requireLogin();

$pdo = getConnection();
$fechaSel = $_GET['fecha'] ?? date('Y-m-d');
$registros = listarCargueros($pdo, $fechaSel, $turno);
$totalReg = count($registros);

$pageTitle = 'Cargueros - ' . $turnoLabel;
$extraJs = [asset('js/cargueros.js?v=6')];
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

$apiRel = url('api/cargueros.php');
$inlineJs = 'window.CARGUEROS_CONFIG=' . json_encode([
    'apiUrl'    => $apiRel,
    'exportUrl' => url('api/export_cargueros.php'),
    'turno'     => $turno,
    'fecha'     => $fechaSel,
    'inicial'   => $registros,
], JSON_UNESCAPED_UNICODE) . ';';
?>

<main class="main-content container-fluid">
    <div class="card-panel">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h4 mb-1"><i class="bi bi-truck"></i> <?= e($turnoLabel) ?></h1>
                <span class="text-muted small" id="fechaDisplay">Fecha: <?= e(date('d/m/Y', strtotime($fechaSel))) ?></span>
                <span class="badge bg-primary ms-2" id="badgeTotal"><?= $totalReg ?> registro(s)</span>
            </div>
            <a href="<?= url('modules/cargueros/index.php') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>

        <div class="row g-2 mb-3 align-items-end">
            <div class="col-auto">
                <label class="form-label small mb-0">Fecha</label>
                <input type="date" class="form-control form-control-sm" id="fechaSelector" value="<?= e($fechaSel) ?>">
            </div>
        </div>

        <div id="toastMsg" class="alert d-none shadow-sm" role="alert"></div>

        <div class="registro-box mb-4">
            <h6 class="mb-3"><i class="bi bi-plus-circle text-primary"></i> Agregar carguero (destino / flota)</h6>
            <form id="formNuevoCarguero" class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small">Destino / Nombre *</label>
                    <input type="text" name="destino" class="form-control" required placeholder="Ej: Lima Norte">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small">Flota *</label>
                    <input type="text" name="flota" class="form-control" required placeholder="F-102">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small">Observaciones</label>
                    <input type="text" name="observaciones" class="form-control" placeholder="Opcional">
                </div>
                <div class="col-12 col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-lg"></i> Agregar a la lista
                    </button>
                </div>
            </form>
            <p class="small text-muted mb-0 mt-2">
                <i class="bi bi-info-circle"></i> Después use <strong>Llegada</strong> y <strong>Salida</strong> en cada fila (actualiza el mismo registro).
            </p>
        </div>

        <h6 class="mb-2"><i class="bi bi-list-ul"></i> Lista del día</h6>
        <div class="table-responsive tabla-cargueros-wrap">
            <table class="table table-hover table-cargueros align-middle mb-0">
                <thead class="<?= e($encabezadoClass) ?>">
                    <tr>
                        <th>Destino / Nombre</th>
                        <th>Flota</th>
                        <th>Llegada</th>
                        <th>Salida</th>
                        <th>Estado</th>
                        <th>Observaciones</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaBody">
                    <?php if (empty($registros)): ?>
                        <tr id="filaVacia"><td colspan="7" class="text-center text-muted py-4">Sin registros. Agregue un carguero arriba.</td></tr>
                    <?php else: ?>
                        <?php foreach ($registros as $r): ?>
                        <tr data-id="<?= (int) $r['id'] ?>">
                            <td><input type="text" class="form-control form-control-sm inp-destino" value="<?= e($r['destino']) ?>"></td>
                            <td><input type="text" class="form-control form-control-sm inp-flota" value="<?= e($r['flota']) ?>"></td>
                            <td>
                                <div class="d-flex gap-1 align-items-center">
                                    <input type="time" class="form-control form-control-sm inp-entrada" value="<?= e($r['hora_entrada'] ?? '') ?>">
                                    <button type="button" class="btn btn-success btn-sm btn-llegada" title="Marcar llegada ahora"><i class="bi bi-box-arrow-in-right"></i></button>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex gap-1 align-items-center">
                                    <input type="time" class="form-control form-control-sm inp-salida" value="<?= e($r['hora_salida'] ?? '') ?>" <?= empty($r['hora_entrada']) ? 'disabled' : '' ?>>
                                    <button type="button" class="btn btn-primary btn-sm btn-salida" title="Marcar salida ahora" <?= empty($r['hora_entrada']) ? 'disabled' : '' ?>><i class="bi bi-box-arrow-right"></i></button>
                                </div>
                            </td>
                            <td><span class="estado-badge <?= e($r['estado_class']) ?>"><?= e($r['estado_label']) ?></span></td>
                            <td><input type="text" class="form-control form-control-sm inp-obs" value="<?= e($r['observaciones'] ?? '') ?>" placeholder="—"></td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-outline-secondary btn-sm btn-guardar" title="Guardar cambios"><i class="bi bi-save"></i></button>
                                <button type="button" class="btn btn-outline-danger btn-sm btn-eliminar"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-3 d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-success btn-sm" id="btnExportar"><i class="bi bi-download"></i> Exportar CSV</button>
            <button type="button" class="btn btn-outline-danger btn-sm" id="btnNuevoDia"><i class="bi bi-trash"></i> Limpiar día</button>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
