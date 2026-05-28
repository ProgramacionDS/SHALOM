<?php
ob_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/personal_helpers.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$pdo = getConnection();
$userId = (int) $_SESSION['usuario_id'];
$method = $_SERVER['REQUEST_METHOD'];

function leerJson(): array
{
    $raw = file_get_contents('php://input');
    if ($raw) {
        $d = json_decode($raw, true);
        if (is_array($d)) {
            return $d;
        }
    }
    return $_POST;
}

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'asistencia';
        $fecha = $_GET['fecha'] ?? date('Y-m-d');

        if ($action === 'asistencia') {
            jsonResponse(['ok' => true, 'data' => obtenerAsistenciaDia($pdo, $fecha)]);
        }

        if ($action === 'descansos') {
            $inicio = $_GET['semana'] ?? date('Y-m-d', strtotime('monday this week'));
            $inicio = date('Y-m-d', strtotime('monday this week', strtotime($inicio)));
            jsonResponse(['ok' => true, 'data' => obtenerCalendarioDescansos($pdo, $inicio)]);
        }

        jsonResponse(['ok' => false, 'message' => 'Acción no válida'], 400);
    }

    if ($method !== 'POST') {
        jsonResponse(['ok' => false, 'message' => 'Método no permitido'], 405);
    }

    $in = leerJson();
    $action = $in['action'] ?? '';

    if ($action === 'marcar_asistencia') {
        $pid = (int) ($in['personal_id'] ?? 0);
        $fecha = $in['fecha'] ?? date('Y-m-d');
        $estado = $in['estado'] ?? 'presente';
        $estados = estadosAsistencia();

        if ($pid <= 0 || !isset($estados[$estado])) {
            jsonResponse(['ok' => false, 'message' => 'Datos inválidos'], 400);
        }

        $nom = $pdo->prepare('SELECT nombre FROM personal WHERE id = ? AND activo = 1');
        $nom->execute([$pid]);
        $nombre = $nom->fetchColumn();
        if (!$nombre) {
            jsonResponse(['ok' => false, 'message' => 'Trabajador no encontrado'], 404);
        }

        $entrada = $in['hora_entrada'] ?? date('H:i');
        if ($estado === 'ausente') {
            $entrada = null;
        }
        $obs = trim($in['observaciones'] ?? '') ?: null;

        $ex = $pdo->prepare('SELECT id FROM asistencias WHERE personal_id = ? AND fecha = ?');
        $ex->execute([$pid, $fecha]);
        if ($row = $ex->fetch()) {
            $pdo->prepare(
                'UPDATE asistencias SET empleado=?, hora_entrada=?, observaciones=?, estado=? WHERE id=?'
            )->execute([$nombre, $entrada, $obs, $estado, $row['id']]);
        } else {
            $pdo->prepare(
                'INSERT INTO asistencias (usuario_id, personal_id, empleado, fecha, hora_entrada, observaciones, estado)
                 VALUES (?,?,?,?,?,?,?)'
            )->execute([$userId, $pid, $nombre, $fecha, $entrada, $obs, $estado]);
        }

        jsonResponse([
            'ok' => true,
            'message' => 'Asistencia guardada.',
            'resumen' => resumenAsistenciaDia($pdo, $fecha),
        ]);
    }

    if ($action === 'toggle_descanso') {
        $pid = (int) ($in['personal_id'] ?? 0);
        $fecha = $in['fecha'] ?? date('Y-m-d');
        $descansa = !empty($in['descansa']);

        if ($pid <= 0) {
            jsonResponse(['ok' => false, 'message' => 'Trabajador requerido'], 400);
        }

        $tipo = $descansa ? 'descanso' : 'trabajo';
        $ex = $pdo->prepare('SELECT id FROM programacion WHERE fecha = ? AND personal_id = ?');
        $ex->execute([$fecha, $pid]);
        if ($row = $ex->fetch()) {
            $pdo->prepare('UPDATE programacion SET tipo = ?, usuario_id = ? WHERE id = ?')
                ->execute([$tipo, $userId, $row['id']]);
        } else {
            $pdo->prepare('INSERT INTO programacion (fecha, personal_id, tipo, usuario_id) VALUES (?,?,?,?)')
                ->execute([$fecha, $pid, $tipo, $userId]);
        }

        jsonResponse(['ok' => true, 'message' => $descansa ? 'Descanso marcado.' : 'Día laboral.']);
    }

    jsonResponse(['ok' => false, 'message' => 'Acción no válida'], 400);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
}
