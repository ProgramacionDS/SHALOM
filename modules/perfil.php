<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$user = currentUser();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'perfil';
    if ($action === 'perfil') {
        $nombre = trim($_POST['nombre'] ?? '');
        if (strlen($nombre) < 3) $errors[] = 'Nombre mínimo 3 caracteres.';
        else {
            getConnection()->prepare('UPDATE usuarios SET nombre=?, telefono=?, cargo=? WHERE id=?')
                ->execute([$nombre, trim($_POST['telefono']??'')?:null, trim($_POST['cargo']??''), $user['id']]);
            flash('success', 'Perfil actualizado.');
            redirect('modules/perfil.php');
        }
    }
    if ($action === 'password') {
        $pdo = getConnection();
        $stmt = $pdo->prepare('SELECT password FROM usuarios WHERE id=?');
        $stmt->execute([$user['id']]);
        $hash = $stmt->fetchColumn();
        if (!password_verify($_POST['password_actual']??'', $hash)) $errors[] = 'Contraseña actual incorrecta.';
        elseif (($_POST['password_nueva']??'') !== ($_POST['password_confirm']??'')) $errors[] = 'No coinciden.';
        elseif (strlen($_POST['password_nueva']??'') < 6) $errors[] = 'Mínimo 6 caracteres.';
        else {
            $pdo->prepare('UPDATE usuarios SET password=? WHERE id=?')->execute([password_hash($_POST['password_nueva'], PASSWORD_DEFAULT), $user['id']]);
            flash('success', 'Contraseña actualizada.');
            redirect('modules/perfil.php');
        }
    }
}
$user = currentUser();
$pageTitle = 'Mi perfil';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="main-content container-fluid">
<h1><i class="bi bi-person-circle"></i> Mi perfil</h1>
<?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= e($e) ?></div><?php endforeach; ?>
<div class="row g-4">
<div class="col-lg-6"><div class="card-panel">
<h5>Datos personales</h5>
<form method="post"><input type="hidden" name="action" value="perfil">
<div class="mb-3"><label class="form-label">Nombre</label><input type="text" name="nombre" class="form-control" required value="<?= e($user['nombre']) ?>"></div>
<div class="mb-3"><label class="form-label">Correo</label><input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled></div>
<div class="mb-3"><label class="form-label">Teléfono</label><input type="tel" name="telefono" class="form-control" value="<?= e($user['telefono']??'') ?>"></div>
<div class="mb-3"><label class="form-label">Cargo</label><input type="text" name="cargo" class="form-control" value="<?= e($user['cargo']??'') ?>"></div>
<button class="btn btn-primary">Guardar</button></form></div></div>
<div class="col-lg-6"><div class="card-panel">
<h5>Cambiar contraseña</h5>
<form method="post"><input type="hidden" name="action" value="password">
<div class="mb-3"><label class="form-label">Actual</label><input type="password" name="password_actual" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Nueva</label><input type="password" name="password_nueva" class="form-control" required minlength="6"></div>
<div class="mb-3"><label class="form-label">Confirmar</label><input type="password" name="password_confirm" class="form-control" required minlength="6"></div>
<button class="btn btn-warning">Actualizar contraseña</button></form></div></div>
</div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
