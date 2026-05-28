<?php
/**
 * Helpers - Registros de cargueros
 */

function listarCargueros(PDO $pdo, string $fecha, string $turno): array
{
    $stmt = $pdo->prepare(
        'SELECT id, destino, flota, hora_entrada, hora_salida, estado, observaciones
         FROM registros_cargueros WHERE fecha = ? AND turno = ? ORDER BY id ASC'
    );
    $stmt->execute([$fecha, $turno]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['estado_label'] = estadoCargueroLabel($r['estado']);
        $r['estado_class'] = estadoCargueroClass($r['estado']);
        if ($r['hora_entrada']) {
            $r['hora_entrada'] = substr($r['hora_entrada'], 0, 5);
        }
        if ($r['hora_salida']) {
            $r['hora_salida'] = substr($r['hora_salida'], 0, 5);
        }
    }
    return $rows;
}

function formatearRegistroCarguero(array $r): array
{
    $r['estado_label'] = estadoCargueroLabel($r['estado']);
    $r['estado_class'] = estadoCargueroClass($r['estado']);
    if (!empty($r['hora_entrada'])) {
        $r['hora_entrada'] = substr((string) $r['hora_entrada'], 0, 5);
    }
    if (!empty($r['hora_salida'])) {
        $r['hora_salida'] = substr((string) $r['hora_salida'], 0, 5);
    }
    return $r;
}
