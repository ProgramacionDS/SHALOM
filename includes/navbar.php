<?php
$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

$navItems = [
    ['url' => 'index.php', 'icon' => 'bi-house-door', 'label' => 'Inicio', 'match' => 'index'],
    ['url' => 'modules/perfil.php', 'icon' => 'bi-person', 'label' => 'Perfil', 'match' => 'perfil'],
    ['url' => 'modules/cargueros/index.php', 'icon' => 'bi-truck', 'label' => 'Cargueros', 'match' => 'cargueros'],
    ['url' => 'modules/mantenimiento.php', 'icon' => 'bi-tools', 'label' => 'Mantenimiento', 'match' => 'mantenimiento'],
    ['url' => 'modules/personal/index.php', 'icon' => 'bi-people', 'label' => 'Mi personal', 'match' => 'personal'],
];

function navActive(string $match, string $page, string $dir): string
{
    return ($dir === $match || $page === $match) ? ' active' : '';
}
?>
<nav class="navbar navbar-dark app-navbar sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('index.php') ?>">
            <i class="bi bi-clipboard-data fs-5"></i>
            <span class="d-none d-sm-inline"><?= e(APP_NAME) ?></span>
        </a>

        <div class="d-flex align-items-center gap-2 ms-auto">
            <?php if ($user): ?>
                <span class="text-white-50 small d-none d-md-inline text-truncate" style="max-width:140px">
                    <?= e($user['nombre']) ?>
                </span>
            <?php endif; ?>
            <button type="button"
                    class="btn-hamburger"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#menuLateral"
                    aria-label="Abrir navegación">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
</nav>

<div class="offcanvas offcanvas-end app-offcanvas" tabindex="-1" id="menuLateral">
    <div class="offcanvas-header border-bottom">
        <div class="d-flex align-items-center gap-2">
            <?php if ($user): ?>
                <div class="avatar-circle avatar-sm"><?= strtoupper(substr($user['nombre'], 0, 1)) ?></div>
                <div>
                    <strong class="d-block"><?= e($user['nombre']) ?></strong>
                    <small class="text-muted"><?= e($user['cargo']) ?></small>
                </div>
            <?php endif; ?>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body p-0">
        <nav class="nav flex-column app-side-nav">
            <?php foreach ($navItems as $item): ?>
                <a class="nav-link<?= navActive($item['match'], $currentPage, $currentDir) ?>"
                   href="<?= url($item['url']) ?>">
                    <i class="bi <?= e($item['icon']) ?>"></i>
                    <?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
            <div class="border-top mt-2 pt-2">
                <a class="nav-link text-danger" href="<?= url('auth/logout.php') ?>">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            </div>
        </nav>
    </div>
</div>

<?php $flash = getFlash(); if ($flash): ?>
<div class="container-fluid pt-3 px-3">
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show shadow-sm mb-0">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>
