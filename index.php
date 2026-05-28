<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$user = currentUser();
$stats = ['cargueros_hoy'=>0,'finalizados'=>0,'mantenimientos'=>0,'asistencias_hoy'=>0,'total_personal'=>0];
try {
    $pdo = getConnection();
    $hoy = date('Y-m-d');
    $s = $pdo->prepare('SELECT COUNT(*) FROM registros_cargueros WHERE fecha=?'); $s->execute([$hoy]); $stats['cargueros_hoy'] = (int)$s->fetchColumn();
    $s = $pdo->prepare("SELECT COUNT(*) FROM registros_cargueros WHERE fecha=? AND estado='finalizado'"); $s->execute([$hoy]); $stats['finalizados'] = (int)$s->fetchColumn();
    $stats['mantenimientos'] = (int)$pdo->query("SELECT COUNT(*) FROM mantenimientos WHERE estado IN ('programado','en_proceso')")->fetchColumn();
    $s = $pdo->prepare('SELECT COUNT(*) FROM asistencias WHERE fecha=?'); $s->execute([$hoy]); $stats['asistencias_hoy'] = (int)$s->fetchColumn();
    $stats['total_personal'] = (int)$pdo->query('SELECT COUNT(*) FROM personal WHERE activo=1')->fetchColumn();
} catch (PDOException $e) {}
$pageTitle = 'Inicio';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>
<main class="main-content container-fluid">
<div class="page-header"><h1><i class="bi bi-speedometer2 text-primary"></i> Panel principal</h1>
<p class="text-muted">Bienvenido, <?= e($user['nombre']) ?></p></div>
<div class="row g-3 mb-4">
<div class="col-6 col-lg-2"><div class="stat-card stat-primary"><div class="stat-value"><?= $stats['cargueros_hoy'] ?></div><div class="stat-label">Cargueros hoy</div></div></div>
<div class="col-6 col-lg-2"><div class="stat-card stat-success"><div class="stat-value"><?= $stats['finalizados'] ?></div><div class="stat-label">Finalizados</div></div></div>
<div class="col-6 col-lg-2"><div class="stat-card stat-warning"><div class="stat-value"><?= $stats['mantenimientos'] ?></div><div class="stat-label">Mant. pend.</div></div></div>
<div class="col-6 col-lg-2"><div class="stat-card stat-info"><div class="stat-value"><?= $stats['asistencias_hoy'] ?></div><div class="stat-label">Asistencias</div></div></div>
<div class="col-6 col-lg-2"><div class="stat-card" style="background:linear-gradient(135deg,#7e57c2,#5e35b1);color:#fff"><div class="stat-value"><?= $stats['total_personal'] ?></div><div class="stat-label">Personal</div></div></div>
</div>
<div class="row g-3">
<div class="col-6 col-lg-3"><a href="<?= url('modules/perfil.php') ?>" class="menu-card card-perfil"><i class="bi bi-person-fill"></i><h4>PERFIL</h4></a></div>
<div class="col-6 col-lg-3"><a href="<?= url('modules/cargueros/index.php') ?>" class="menu-card card-cargueros"><i class="bi bi-truck"></i><h4>CARGUEROS</h4></a></div>
<div class="col-6 col-lg-3"><a href="<?= url('modules/mantenimiento.php') ?>" class="menu-card card-mantenimiento"><i class="bi bi-clipboard-check"></i><h4>MANTENIMIENTOS</h4></a></div>
<div class="col-6 col-lg-3"><a href="<?= url('modules/personal/index.php') ?>" class="menu-card card-personal"><i class="bi bi-people-fill"></i><h4>PERSONAL</h4></a></div>
</div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
