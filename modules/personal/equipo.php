<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/personal_helpers.php';
requireLogin();

$pdo = getConnection();
$roles = rolesPersonal();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $rol = $_POST['rol'] ?? 'estiba';
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? 'guardar';

    if ($action === 'eliminar' && $id > 0) {
        $pdo->prepare('UPDATE personal SET activo = 0 WHERE id = ?')->execute([$id]);
        flash('info', 'Trabajador dado de baja.');
        redirect('modules/personal/equipo.php');
    }

    if (strlen($nombre) >= 3 && isset($roles[$rol])) {
        if ($id > 0) {
            $pdo->prepare('UPDATE personal SET nombre = ?, rol = ? WHERE id = ?')->execute([$nombre, $rol, $id]);
            flash('success', 'Trabajador actualizado.');
        } else {
            $pdo->prepare('INSERT INTO personal (nombre, rol) VALUES (?, ?)')->execute([$nombre, $rol]);
            flash('success', 'Trabajador agregado.');
        }
        redirect('modules/personal/equipo.php');
    }
    flash('danger', 'El nombre debe tener al menos 3 caracteres.');
}

$lista = $pdo->query('SELECT * FROM personal WHERE activo = 1 ORDER BY nombre')->fetchAll();
$editar = null;
if (!empty($_GET['editar'])) {
    $s = $pdo->prepare('SELECT * FROM personal WHERE id = ? AND activo = 1');
    $s->execute([(int) $_GET['editar']]);
    $editar = $s->fetch();
}

$pageTitle = 'Equipo';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="main-content container-fluid personal-module">
    <header class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <div>
            <h1 class="h4 mb-0">Equipo de trabajo</h1>
            <p class="text-muted small mb-0"><?= count($lista) ?> trabajador(es) activos</p>
        </div>
    </header>

    <?= personalSubnav('equipo') ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card-panel">
                <h6 class="mb-3"><i class="bi bi-person-plus"></i> <?= $editar ? 'Editar trabajador' : 'Nuevo trabajador' ?></h6>
                <form method="post">
                    <input type="hidden" name="action" value="guardar">
                    <?php if ($editar): ?>
                        <input type="hidden" name="id" value="<?= (int) $editar['id'] ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Nombre completo</label>
                        <input type="text" name="nombre" class="form-control" required minlength="3"
                               placeholder="Ej: Juan Pérez" value="<?= e($editar['nombre'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol / área</label>
                        <select name="rol" class="form-select">
                            <?php foreach ($roles as $k => $r): ?>
                                <option value="<?= e($k) ?>" <?= ($editar['rol'] ?? 'estiba') === $k ? 'selected' : '' ?>>
                                    <?= e($r['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-save"></i> Guardar
                        </button>
                        <?php if ($editar): ?>
                            <a href="<?= url('modules/personal/equipo.php') ?>" class="btn btn-outline-secondary">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card-panel p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-app table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Rol</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lista)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-5">
                                        No hay trabajadores. Agregue el primero en el formulario.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($lista as $t): ?>
                                <tr>
                                    <td><strong><?= e($t['nombre']) ?></strong></td>
                                    <td><?= rolBadge($t['rol']) ?></td>
                                    <td class="text-end text-nowrap">
                                        <a href="?editar=<?= (int) $t['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Dar de baja a este trabajador?')">
                                            <input type="hidden" name="action" value="eliminar">
                                            <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
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
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
