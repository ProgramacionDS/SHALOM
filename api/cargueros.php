<?php
ob_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cargueros_helpers.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$pdo = getConnection();
$userId = (int) $_SESSION['usuario_id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

function leerInput(): array
{
    $raw = file_get_contents('php://input');
    if ($raw !== false && $raw !== '') {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            return $json;
        }
    }
    return $_POST;
}

function validarBase(array $data): array
{
    $destino = trim($data['destino'] ?? '');
    $flota = trim($data['flota'] ?? '');
    $fecha = $data['fecha'] ?? date('Y-m-d');
    $turno = $data['turno'] ?? 'manana';

    if ($destino === '') {
        return ['ok' => false, 'message' => 'El destino es obligatorio.'];
    }
    if ($flota === '') {
        return ['ok' => false, 'message' => 'La flota es obligatoria.'];
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return ['ok' => false, 'message' => 'Fecha inválida.'];
    }
    if (!in_array($turno, ['manana', 'tarde'], true)) {
        return ['ok' => false, 'message' => 'Turno inválido.'];
    }

    return [
        'ok' => true,
        'destino' => $destino,
        'flota' => $flota,
        'fecha' => $fecha,
        'turno' => $turno,
        'observaciones' => trim($data['observaciones'] ?? '') ?: null,
    ];
}

function respuestaRegistro(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare(
        'SELECT id, destino, flota, hora_entrada, hora_salida, estado, observaciones FROM registros_cargueros WHERE id = ?'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        jsonResponse(['ok' => false, 'message' => 'Registro no encontrado'], 404);
    }
    $row = formatearRegistroCarguero($row);
    jsonResponse(['ok' => true, 'message' => 'Guardado correctamente.', 'data' => $row]);
}

try {
    if ($method === 'GET') {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $turno = $_GET['turno'] ?? 'manana';
        $lista = listarCargueros($pdo, $fecha, $turno);
        jsonResponse([
            'ok' => true,
            'data' => $lista,
            'fecha' => $fecha,
            'turno' => $turno,
            'total' => count($lista),
        ]);
    }

    if ($method === 'DELETE') {
        $in = leerInput();
        $id = (int) ($in['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['ok' => false, 'message' => 'ID inválido'], 400);
        }
        $pdo->prepare('DELETE FROM registros_cargueros WHERE id = ?')->execute([$id]);
        jsonResponse(['ok' => true, 'message' => 'Registro eliminado.']);
    }

    if ($method !== 'POST') {
        jsonResponse(['ok' => false, 'message' => 'Método no permitido'], 405);
    }

    $input = leerInput();
    $action = $action ?: ($input['action'] ?? 'crear');

    if ($action === 'limpiar_dia') {
        $pdo->prepare('DELETE FROM registros_cargueros WHERE fecha = ? AND turno = ?')
            ->execute([$input['fecha'] ?? date('Y-m-d'), $input['turno'] ?? 'manana']);
        jsonResponse(['ok' => true, 'message' => 'Día limpiado.', 'data' => []]);
    }

    if ($action === 'crear') {
        $v = validarBase($input);
        if (!$v['ok']) {
            jsonResponse($v, 400);
        }
        $pdo->prepare(
            'INSERT INTO registros_cargueros (usuario_id, fecha, turno, destino, flota, observaciones, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([$userId, $v['fecha'], $v['turno'], $v['destino'], $v['flota'], $v['observaciones'], 'pendiente']);
        respuestaRegistro($pdo, (int) $pdo->lastInsertId());
    }

    $id = (int) ($input['id'] ?? 0);
    if ($id <= 0 && in_array($action, ['marcar_entrada', 'marcar_salida', 'actualizar'], true)) {
        jsonResponse(['ok' => false, 'message' => 'ID de registro requerido.'], 400);
    }

    if ($action === 'marcar_entrada') {
        $hora = $input['hora_entrada'] ?? date('H:i');
        if ($hora === '') {
            $hora = date('H:i');
        }
        $v = validarBase($input);
        if (!$v['ok']) {
            jsonResponse($v, 400);
        }

        if ($id > 0) {
            $pdo->prepare(
                'UPDATE registros_cargueros SET destino=?, flota=?, hora_entrada=?, observaciones=?, estado=? WHERE id=?'
            )->execute([$v['destino'], $v['flota'], $hora, $v['observaciones'], 'instalacion', $id]);
        } else {
            $pdo->prepare(
                'INSERT INTO registros_cargueros (usuario_id, fecha, turno, destino, flota, hora_entrada, observaciones, estado)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([$userId, $v['fecha'], $v['turno'], $v['destino'], $v['flota'], $hora, $v['observaciones'], 'instalacion']);
            $id = (int) $pdo->lastInsertId();
        }
        respuestaRegistro($pdo, $id);
    }

    if ($action === 'marcar_salida') {
        $horaSalida = $input['hora_salida'] ?? date('H:i');
        $stmt = $pdo->prepare('SELECT hora_entrada FROM registros_cargueros WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row || empty($row['hora_entrada'])) {
            jsonResponse(['ok' => false, 'message' => 'Primero registre la hora de llegada.'], 400);
        }
        $obs = trim($input['observaciones'] ?? '') ?: null;
        $pdo->prepare(
            'UPDATE registros_cargueros SET hora_salida=?, observaciones=COALESCE(?, observaciones), estado=? WHERE id=?'
        )->execute([$horaSalida, $obs, 'finalizado', $id]);
        respuestaRegistro($pdo, $id);
    }

    if ($action === 'actualizar') {
        $v = validarBase($input);
        if (!$v['ok']) {
            jsonResponse($v, 400);
        }
        $entrada = !empty($input['hora_entrada']) ? $input['hora_entrada'] : null;
        $salida = !empty($input['hora_salida']) ? $input['hora_salida'] : null;
        $estado = calcularEstadoCarguero($entrada, $salida);
        $pdo->prepare(
            'UPDATE registros_cargueros SET destino=?, flota=?, hora_entrada=?, hora_salida=?, observaciones=?, estado=? WHERE id=?'
        )->execute([$v['destino'], $v['flota'], $entrada, $salida, $v['observaciones'], $estado, $id]);
        respuestaRegistro($pdo, $id);
    }

    jsonResponse(['ok' => false, 'message' => 'Acción no válida'], 400);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
}
