<?php
require_once __DIR__ . '/../includes/auth.php';
if (isLoggedIn()) redirect('index.php');
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email === '' || $password === '') {
        $errors[] = 'Ingrese correo y contraseña.';
    } else {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare('SELECT id, nombre, email, password, rol FROM usuarios WHERE email = ? AND activo = 1');
            $stmt->execute([$email]);
            $u = $stmt->fetch();
            if ($u && password_verify($password, $u['password'])) {
                loginUser($u);
                flash('success', 'Bienvenido, ' . $u['nombre']);
                redirect('index.php');
            } else {
                $errors[] = 'Credenciales incorrectas.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Error de conexión. Ejecute install.php';
        }
    }
}
$pageTitle = 'Iniciar sesión';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrapper"><div class="auth-card">
<div class="brand-icon"><i class="bi bi-clipboard-data"></i></div>
<h2 class="text-center"><?= e(APP_NAME) ?></h2>
<?php foreach ($errors as $e): ?><div class="alert alert-danger py-2"><?= e($e) ?></div><?php endforeach; ?>
<form method="post">
<div class="mb-3"><label class="form-label">Correo</label>
<input type="email" class="form-control form-control-lg" name="email" value="<?= e($_POST['email'] ?? '') ?>" required></div>
<div class="mb-4"><label class="form-label">Contraseña</label>
<input type="password" class="form-control form-control-lg" name="password" required></div>
<button type="submit" class="btn btn-primary btn-lg w-100">Ingresar</button>
</form>
<p class="text-center mt-3"><a href="<?= url('auth/register.php') ?>">Registrarse</a></p>
<p class="text-center small text-muted">Demo: admin@shalom.local / admin123</p>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
