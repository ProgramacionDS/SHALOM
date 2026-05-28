<?php
require_once __DIR__ . '/../includes/auth.php';
if (isLoggedIn()) redirect('index.php');
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $cargo = trim($_POST['cargo'] ?? 'Operador');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password_confirm'] ?? '';
    if (strlen($nombre) < 3) $errors[] = 'Nombre mínimo 3 caracteres.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Correo inválido.';
    if (strlen($password) < 6) $errors[] = 'Contraseña mínimo 6 caracteres.';
    if ($password !== $password2) $errors[] = 'Las contraseñas no coinciden.';
    if (empty($errors)) {
        try {
            $pdo = getConnection();
            $chk = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $errors[] = 'Correo ya registrado.';
            } else {
                $pdo->prepare('INSERT INTO usuarios (nombre,email,password,telefono,cargo) VALUES (?,?,?,?,?)')
                    ->execute([$nombre, $email, password_hash($password, PASSWORD_DEFAULT), $telefono ?: null, $cargo]);
                flash('success', 'Registro exitoso.');
                redirect('auth/login.php');
            }
        } catch (PDOException $e) {
            $errors[] = 'Ejecute install.php primero.';
        }
    }
}
$pageTitle = 'Registro';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrapper"><div class="auth-card" style="max-width:480px">
<h2 class="text-center mb-4">Crear cuenta</h2>
<?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= e($e) ?></div><?php endforeach; ?>
<form method="post">
<div class="mb-3"><label class="form-label">Nombre</label><input type="text" name="nombre" class="form-control" required minlength="3" value="<?= e($_POST['nombre'] ?? '') ?>"></div>
<div class="mb-3"><label class="form-label">Correo</label><input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>"></div>
<div class="row"><div class="col-6 mb-3"><label class="form-label">Teléfono</label><input type="tel" name="telefono" class="form-control" value="<?= e($_POST['telefono'] ?? '') ?>"></div>
<div class="col-6 mb-3"><label class="form-label">Cargo</label><input type="text" name="cargo" class="form-control" value="<?= e($_POST['cargo'] ?? 'Operador') ?>"></div></div>
<div class="mb-3"><label class="form-label">Contraseña</label><input type="password" name="password" class="form-control" required minlength="6"></div>
<div class="mb-4"><label class="form-label">Confirmar</label><input type="password" name="password_confirm" class="form-control" required minlength="6"></div>
<button type="submit" class="btn btn-primary w-100">Registrarse</button>
</form>
<p class="text-center mt-3"><a href="<?= url('auth/login.php') ?>">Volver al login</a></p>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
