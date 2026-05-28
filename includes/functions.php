<?php
function appBasePath(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $projectRoot = realpath(__DIR__ . '/..');
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    if ($projectRoot && $docRoot && str_starts_with($projectRoot, $docRoot)) {
        $rel = trim(str_replace('\\', '/', substr($projectRoot, strlen($docRoot))), '/');
        $base = $rel === '' ? '' : '/' . $rel;
    } else {
        $base = rtrim(BASE_URL, '/');
    }
    if ($base === '') {
        $base = rtrim(BASE_URL, '/');
    }
    return $base;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function url(string $path = ''): string
{
    $base = appBasePath();
    $path = ltrim($path, '/');
    return $path === '' ? ($base ?: '/') : $base . '/' . $path;
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function isApiRequest(): bool
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (str_contains($uri, '/api/')) {
        return true;
    }
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (str_contains($accept, 'application/json')) {
        return true;
    }
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}

function estadoCargueroLabel(string $estado): string
{
    return match ($estado) {
        'pendiente'   => 'PENDIENTE',
        'instalacion' => 'EN INSTALACIÓN',
        'finalizado'  => 'FINALIZADO',
        default       => strtoupper($estado),
    };
}

function estadoCargueroClass(string $estado): string
{
    return match ($estado) {
        'instalacion' => 'instalacion',
        'finalizado'  => 'finalizado',
        default       => 'pendiente',
    };
}

function calcularEstadoCarguero(?string $entrada, ?string $salida): string
{
    if (empty($entrada)) {
        return 'pendiente';
    }
    if (!empty($salida)) {
        return 'finalizado';
    }
    return 'instalacion';
}

function jsonResponse(array $data, int $code = 200): void
{
    if (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
