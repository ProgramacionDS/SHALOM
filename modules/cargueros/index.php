<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
$pageTitle = 'Cargueros';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>
<main class="main-content container-fluid">
<div class="page-header"><h1><i class="bi bi-truck text-success"></i> Control de cargueros</h1></div>
<div class="row g-3 justify-content-center">
<div class="col-6 col-md-5"><a href="<?= url('modules/cargueros/manana.php') ?>" class="menu-card card-manana"><i class="bi bi-sunrise"></i><h4>TURNO MAÑANA</h4></a></div>
<div class="col-6 col-md-5"><a href="<?= url('modules/cargueros/tarde.php') ?>" class="menu-card card-tarde"><i class="bi bi-sunset"></i><h4>TURNO TARDE</h4></a></div>
</div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
