<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/personal_helpers.php';
requireLogin();

$pdo = getConnection();
$user = currentUser();

$fechaRef = $_GET['semana'] ?? date('Y-m-d');
$lunes = date('Y-m-d', strtotime('monday this week', strtotime($fechaRef)));
$cal = obtenerCalendarioDescansos($pdo, $lunes);
$dias = $cal['dias'];
$grid = $cal['grid'];

$filtroPersona = (int) ($_GET['personal_id'] ?? 0);
if ($filtroPersona > 0) {
    $grid = array_values(array_filter($grid, fn($g) => $g['id'] === $filtroPersona));
}

$trabajadores = listarPersonalActivo($pdo);
$prevSemana = date('Y-m-d', strtotime($lunes . ' -7 days'));
$nextSemana = date('Y-m-d', strtotime($lunes . ' +7 days'));
$diasNombre = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

$diaVer = $_GET['dia'] ?? date('Y-m-d');
if ($diaVer < $dias[0] || $diaVer > $dias[6]) {
    $diaVer = in_array(date('Y-m-d'), $dias, true) ? date('Y-m-d') : $dias[0];
}
$descansanHoy = [];
foreach ($cal['grid'] as $g) {
    foreach ($g['celdas'] as $c) {
        if ($c['fecha'] === $diaVer && $c['descanso']) {
            $descansanHoy[] = $g['nombre'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $pid = (int) ($_POST['personal_id'] ?? 0);
    if ($pid > 0) {
        $tipo = 'descanso';
        $ex = $pdo->prepare('SELECT id FROM programacion WHERE fecha = ? AND personal_id = ?');
        $ex->execute([$fecha, $pid]);
        if ($row = $ex->fetch()) {
            $pdo->prepare('UPDATE programacion SET tipo = ?, usuario_id = ? WHERE id = ?')
                ->execute([$tipo, $user['id'], $row['id']]);
        } else {
            $pdo->prepare('INSERT INTO programacion (fecha, personal_id, tipo, usuario_id) VALUES (?,?,?,?)')
                ->execute([$fecha, $pid, $tipo, $user['id']]);
        }
        flash('success', 'Descanso programado.');
    }
    redirect('modules/personal/descansos.php?semana=' . urlencode($lunes));
}

$pageTitle = 'Descansos';
$extraJs = [asset('js/personal.js?v=1')];
$inlineJs = 'window.PERSONAL_CONFIG=' . json_encode(['apiUrl' => url('api/personal.php')], JSON_UNESCAPED_UNICODE) . ';';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="main-content container-fluid personal-module">
    <header class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <div>
            <h1 class="h4 mb-0">Calendario de descansos</h1>
            <p class="text-muted small mb-0">
                Semana <?= e(date('d/m', strtotime($dias[0]))) ?> — <?= e(date('d/m/Y', strtotime($dias[6]))) ?>
            </p>
        </div>
        <div class="btn-group">
            <a href="?semana=<?= e($prevSemana) ?><?= $filtroPersona ? '&personal_id=' . $filtroPersona : '' ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-chevron-left"></i></a>
            <a href="?semana=<?= e(date('Y-m-d')) ?>" class="btn btn-sm btn-outline-secondary">Hoy</a>
            <a href="?semana=<?= e($nextSemana) ?><?= $filtroPersona ? '&personal_id=' . $filtroPersona : '' ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-chevron-right"></i></a>
        </div>
    </header>

    <?= personalSubnav('descansos') ?>

    <div id="personalToast" class="alert alert-success d-none shadow-sm" role="alert"></div>

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card-panel">
                <form method="get" class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small">Ir a semana (cualquier día)</label>
                        <input type="date" name="semana" class="form-control" value="<?= e($lunes) ?>">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small">Solo trabajador</label>
                        <select name="personal_id" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($trabajadores as $t): ?>
                                <option value="<?= (int) $t['id'] ?>" <?= $filtroPersona === (int) $t['id'] ? 'selected' : '' ?>>
                                    <?= e($t['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Ver</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-panel h-100">
                <h6 class="mb-2"><i class="bi bi-moon"></i> Descansan el <?= e(date('d/m', strtotime($diaVer))) ?></h6>
                <form method="get" class="mb-2">
                    <input type="hidden" name="semana" value="<?= e($lunes) ?>">
                    <?php if ($filtroPersona): ?><input type="hidden" name="personal_id" value="<?= $filtroPersona ?>"><?php endif; ?>
                    <input type="date" name="dia" class="form-control form-control-sm" value="<?= e($diaVer) ?>"
                           min="<?= e($dias[0]) ?>" max="<?= e($dias[6]) ?>" onchange="this.form.submit()">
                </form>
                <?php if (empty($descansanHoy)): ?>
                    <p class="text-muted small mb-0">Nadie en descanso ese día.</p>
                <?php else: ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($descansanHoy as $nom): ?>
                            <li class="mb-1"><span class="badge bg-secondary"><?= e($nom) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (empty($trabajadores)): ?>
        <div class="alert alert-warning">Agregue trabajadores en <a href="<?= url('modules/personal/equipo.php') ?>">Equipo</a>.</div>
    <?php else: ?>

    <div class="card-panel overflow-auto">
        <p class="small text-muted">
            Clic en celda: <span class="cal-cell descanso d-inline-flex px-2">D</span> descansa ·
            <span class="cal-cell trabaja d-inline-flex px-2">·</span> trabaja
        </p>
        <div class="cal-grid" id="calDescansos" style="min-width:720px">
            <div class="cal-head">Trabajador</div>
            <?php foreach ($dias as $i => $f): ?>
                <div class="cal-head"><?= $diasNombre[$i] ?><br><small><?= date('d/m', strtotime($f)) ?></small></div>
            <?php endforeach; ?>

            <?php foreach ($grid as $g): ?>
                <div class="cal-name"><?= e($g['nombre']) ?></div>
                <?php foreach ($g['celdas'] as $c): ?>
                <div class="cal-cell <?= $c['descanso'] ? 'descanso' : 'trabaja' ?>">
                    <button type="button"
                            class="btn btn-link btn-sm p-0 btn-toggle-descanso text-decoration-none <?= $c['descanso'] ? 'text-secondary fw-bold' : 'text-success' ?>"
                            data-personal-id="<?= (int) $g['id'] ?>"
                            data-fecha="<?= e($c['fecha']) ?>"
                            data-descanso="<?= $c['descanso'] ? '1' : '0' ?>"
                            title="<?= $c['descanso'] ? 'Quitar descanso' : 'Marcar descanso' ?>">
                        <?= $c['descanso'] ? 'D' : '·' ?>
                    </button>
                </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card-panel mt-3">
        <h6 class="mb-3"><i class="bi bi-plus-circle"></i> Programar descanso rápido</h6>
        <form method="post" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Fecha</label>
                <input type="date" name="fecha" class="form-control" required value="<?= e(date('Y-m-d')) ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label small">Trabajador</label>
                <select name="personal_id" class="form-select" required>
                    <?php foreach ($trabajadores as $t): ?>
                        <option value="<?= (int) $t['id'] ?>"><?= e($t['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-secondary w-100">Marcar descanso</button>
            </div>
        </form>
    </div>

    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
