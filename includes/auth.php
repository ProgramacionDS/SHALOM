<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    $cookiePath = appBasePath() . '/';
    if ($cookiePath === '//') {
        $cookiePath = '/';
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => $cookiePath,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['usuario_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        if (isApiRequest()) {
            jsonResponse(['ok' => false, 'message' => 'Sesión expirada. Vuelva a iniciar sesión.'], 401);
        }
        flash('warning', 'Debe iniciar sesión para continuar.');
        redirect('auth/login.php');
    }
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    static $user = null;
    if ($user !== null) {
        return $user;
    }
    $pdo = getConnection();
    $stmt = $pdo->prepare('SELECT id, nombre, email, telefono, cargo, rol FROM usuarios WHERE id = ? AND activo = 1');
    $stmt->execute([$_SESSION['usuario_id']]);
    $user = $stmt->fetch() ?: null;
    if (!$user) {
        logout();
    }
    return $user;
}

function loginUser(array $usuario): void
{
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nombre'] = $usuario['nombre'];
    $_SESSION['usuario_rol'] = $usuario['rol'];
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    redirect('auth/login.php');
}
