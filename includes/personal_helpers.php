<?php
/**
 * Helpers - Módulo Personal
 */

function rolesPersonal(): array
{
    return [
        'estiba'      => ['label' => 'Estiba', 'class' => 'bg-primary', 'icon' => 'bi-box-seam'],
        'chofer'      => ['label' => 'Chofer', 'class' => 'bg-success', 'icon' => 'bi-truck'],
        'coordinador' => ['label' => 'Coordinador', 'class' => 'bg-dark', 'icon' => 'bi-person-badge'],
    ];
}

function estadosAsistencia(): array
{
    return [
        'presente'            => ['label' => 'Presente', 'class' => 'estado-presente', 'badge' => 'bg-success', 'icon' => 'bi-check-circle'],
        'tardanza'            => ['label' => 'Tarde', 'class' => 'estado-tarde', 'badge' => 'bg-warning text-dark', 'icon' => 'bi-clock'],
        'ausente'             => ['label' => 'Faltó', 'class' => 'estado-ausente', 'badge' => 'bg-danger', 'icon' => 'bi-x-circle'],
        'no_regreso_almuerzo' => ['label' => 'Sin regreso', 'class' => 'estado-almuerzo', 'badge' => 'bg-orange', 'icon' => 'bi-cup-hot'],
        'justificado'         => ['label' => 'Justificado', 'class' => 'estado-justificado', 'badge' => 'bg-info text-dark', 'icon' => 'bi-file-text'],
    ];
}

function rolLabel(string $rol): string
{
    return rolesPersonal()[$rol]['label'] ?? ucfirst($rol);
}

function rolBadge(string $rol): string
{
    $r = rolesPersonal()[$rol] ?? ['label' => $rol, 'class' => 'bg-secondary'];
    return '<span class="badge ' . $r['class'] . '">' . htmlspecialchars($r['label']) . '</span>';
}

function estadoAsistenciaBadge(string $estado): string
{
    $e = estadosAsistencia()[$estado] ?? ['label' => $estado, 'badge' => 'bg-secondary'];
    return '<span class="badge badge-estado ' . $e['badge'] . '">' . htmlspecialchars($e['label']) . '</span>';
}

function listarPersonalActivo(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM personal WHERE activo = 1 ORDER BY nombre ASC')->fetchAll();
}

function resumenAsistenciaDia(PDO $pdo, string $fecha): array
{
    $resumen = ['total' => 0, 'presente' => 0, 'tardanza' => 0, 'ausente' => 0, 'no_regreso_almuerzo' => 0, 'justificado' => 0, 'sin_marcar' => 0, 'descanso' => 0];
    $resumen['total'] = (int) $pdo->query('SELECT COUNT(*) FROM personal WHERE activo = 1')->fetchColumn();

    $stmt = $pdo->prepare('SELECT estado, COUNT(*) AS c FROM asistencias WHERE fecha = ? GROUP BY estado');
    $stmt->execute([$fecha]);
    foreach ($stmt->fetchAll() as $r) {
        $k = $r['estado'];
        if (isset($resumen[$k])) {
            $resumen[$k] = (int) $r['c'];
        }
    }

    $marcados = $resumen['presente'] + $resumen['tardanza'] + $resumen['ausente']
        + $resumen['no_regreso_almuerzo'] + $resumen['justificado'];
    $resumen['sin_marcar'] = max(0, $resumen['total'] - $marcados);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM programacion WHERE fecha = ? AND tipo = 'descanso'");
    $stmt->execute([$fecha]);
    $resumen['descanso'] = (int) $stmt->fetchColumn();

    return $resumen;
}

function obtenerAsistenciaDia(PDO $pdo, string $fecha): array
{
    $trabajadores = listarPersonalActivo($pdo);
    $map = [];
    $stmt = $pdo->prepare('SELECT * FROM asistencias WHERE fecha = ?');
    $stmt->execute([$fecha]);
    foreach ($stmt->fetchAll() as $a) {
        if ($a['personal_id']) {
            $map[(int) $a['personal_id']] = $a;
        }
    }

    $descansos = [];
    $stmt = $pdo->prepare("SELECT personal_id FROM programacion WHERE fecha = ? AND tipo = 'descanso'");
    $stmt->execute([$fecha]);
    foreach ($stmt->fetchAll() as $d) {
        $descansos[(int) $d['personal_id']] = true;
    }

    $filas = [];
    foreach ($trabajadores as $t) {
        $a = $map[(int) $t['id']] ?? null;
        $filas[] = [
            'id' => (int) $t['id'],
            'nombre' => $t['nombre'],
            'rol' => $t['rol'],
            'rol_label' => rolLabel($t['rol']),
            'en_descanso' => !empty($descansos[(int) $t['id']]),
            'asistencia' => $a ? [
                'estado' => $a['estado'],
                'estado_label' => estadosAsistencia()[$a['estado']]['label'] ?? $a['estado'],
                'hora_entrada' => $a['hora_entrada'] ? substr($a['hora_entrada'], 0, 5) : '',
                'observaciones' => $a['observaciones'] ?? '',
            ] : null,
        ];
    }

    return [
        'fecha' => $fecha,
        'filas' => $filas,
        'resumen' => resumenAsistenciaDia($pdo, $fecha),
    ];
}

function obtenerCalendarioDescansos(PDO $pdo, string $lunes): array
{
    $dias = [];
    for ($i = 0; $i < 7; $i++) {
        $dias[] = date('Y-m-d', strtotime($lunes . " +{$i} days"));
    }

    $prog = [];
    $stmt = $pdo->prepare('SELECT personal_id, fecha, tipo FROM programacion WHERE fecha BETWEEN ? AND ?');
    $stmt->execute([$dias[0], $dias[6]]);
    foreach ($stmt->fetchAll() as $p) {
        $prog[$p['personal_id'] . '_' . $p['fecha']] = $p['tipo'];
    }

    $trabajadores = listarPersonalActivo($pdo);
    $grid = [];
    foreach ($trabajadores as $t) {
        $celdas = [];
        foreach ($dias as $f) {
            $celdas[] = [
                'fecha' => $f,
                'descanso' => ($prog[$t['id'] . '_' . $f] ?? 'trabajo') === 'descanso',
            ];
        }
        $grid[] = ['id' => (int) $t['id'], 'nombre' => $t['nombre'], 'rol' => $t['rol'], 'celdas' => $celdas];
    }

    return ['lunes' => $lunes, 'dias' => $dias, 'grid' => $grid];
}

function personalSubnav(string $activo): string
{
    $items = [
        'index'      => ['url' => 'modules/personal/index.php', 'label' => 'Inicio', 'icon' => 'bi-grid'],
        'equipo'     => ['url' => 'modules/personal/equipo.php', 'label' => 'Equipo', 'icon' => 'bi-people'],
        'asistencia' => ['url' => 'modules/personal/asistencia.php', 'label' => 'Asistencia', 'icon' => 'bi-calendar-check'],
        'descansos'  => ['url' => 'modules/personal/descansos.php', 'label' => 'Descansos', 'icon' => 'bi-calendar-week'],
    ];
    $html = '<nav class="personal-subnav mb-4"><div class="nav nav-pills flex-wrap gap-1">';
    foreach ($items as $key => $item) {
        $active = $key === $activo ? ' active' : '';
        $html .= '<a class="nav-link' . $active . '" href="' . htmlspecialchars(url($item['url'])) . '">';
        $html .= '<i class="bi ' . $item['icon'] . '"></i> ' . htmlspecialchars($item['label']) . '</a>';
    }
    $html .= '</div></nav>';
    return $html;
}
