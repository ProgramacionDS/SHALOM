<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/personal_helpers.php';
requireLogin();

$pdo = getConnection();
$hoy = date('Y-m-d');
$resumen = resumenAsistenciaDia($pdo, $hoy);

$pageTitle = 'Mi personal';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="main-content container-fluid personal-module">
    <header class="mb-2">
        <h1 class="h3 mb-1"><i class="bi bi-people-fill text-primary"></i> Mi personal</h1>
        <p class="text-muted mb-0">Control de equipo, asistencia y descansos — <?= e(date('d/m/Y')) ?></p>
    </header>

    <?= personalSubnav('index') ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-box stat-box-primary">
                <div class="stat-num"><?= $resumen['total'] ?></div>
                <div class="stat-lbl">En equipo</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-box stat-box-success">
                <div class="stat-num" data-resumen="presente"><?= $resumen['presente'] ?></div>
                <div class="stat-lbl">Presentes</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-box stat-box-warning">
                <div class="stat-num" data-resumen="tardanza"><?= $resumen['tardanza'] ?></div>
                <div class="stat-lbl">Tarde</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-box stat-box-danger">
                <div class="stat-num" data-resumen="ausente"><?= $resumen['ausente'] ?></div>
                <div class="stat-lbl">Faltaron</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-box stat-box-orange">
                <div class="stat-num" data-resumen="no_regreso_almuerzo"><?= $resumen['no_regreso_almuerzo'] ?></div>
                <div class="stat-lbl">Sin regreso</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-box stat-box-muted">
                <div class="stat-num" data-resumen="descanso"><?= $resumen['descanso'] ?></div>
                <div class="stat-lbl">Descansan hoy</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <a href="<?= url('modules/personal/equipo.php') ?>" class="action-card">
                <i class="bi bi-person-plus"></i>
                <h5>Gestionar equipo</h5>
                <p>Agregar, editar o dar de baja trabajadores</p>
            </a>
        </div>
        <div class="col-md-4">
            <a href="<?= url('modules/personal/asistencia.php') ?>" class="action-card action-card-primary">
                <i class="bi bi-clipboard-check"></i>
                <h5>Asistencia del día</h5>
                <p>Marcar presente, tarde, falta o sin regreso</p>
            </a>
        </div>
        <div class="col-md-4">
            <a href="<?= url('modules/personal/descansos.php') ?>" class="action-card action-card-secondary">
                <i class="bi bi-calendar3"></i>
                <h5>Calendario de descansos</h5>
                <p>Programar quién descansa cada día</p>
            </a>
        </div>
    </div>

    <?php if ($resumen['sin_marcar'] > 0): ?>
    <div class="alert alert-info mt-4 mb-0">
        <i class="bi bi-info-circle"></i>
        Hay <strong><?= $resumen['sin_marcar'] ?></strong> trabajador(es) sin marcar asistencia hoy.
        <a href="<?= url('modules/personal/asistencia.php') ?>" class="alert-link">Ir a asistencia</a>
    </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
