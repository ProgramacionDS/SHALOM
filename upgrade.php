<?php
require_once __DIR__ . '/config/database.php';
$messages = [];
$success = false;

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $s = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?');
    $s->execute([DB_NAME, $table, $column]);
    return (int)$s->fetchColumn() > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' || PHP_SAPI === 'cli') {
    try {
        $pdo = getConnection();
        $pdo->exec("CREATE TABLE IF NOT EXISTS personal (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(120) NOT NULL,
            rol ENUM('estiba','chofer','coordinador') NOT NULL DEFAULT 'estiba',
            activo TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
        $pdo->exec("CREATE TABLE IF NOT EXISTS programacion (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, fecha DATE NOT NULL,
            personal_id INT UNSIGNED NOT NULL,
            tipo ENUM('trabajo','descanso','domingo','especial') NOT NULL DEFAULT 'trabajo',
            observaciones VARCHAR(255) DEFAULT NULL, usuario_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_fecha_personal (fecha, personal_id),
            FOREIGN KEY (personal_id) REFERENCES personal(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");
        if (!columnExists($pdo, 'registros_cargueros', 'observaciones')) {
            $pdo->exec('ALTER TABLE registros_cargueros ADD COLUMN observaciones VARCHAR(255) DEFAULT NULL AFTER hora_salida');
            $messages[] = 'Columna observaciones en cargueros.';
        }
        if (!columnExists($pdo, 'asistencias', 'personal_id')) {
            $pdo->exec('ALTER TABLE asistencias ADD COLUMN personal_id INT UNSIGNED NULL AFTER usuario_id');
        }
        try {
            $pdo->exec("ALTER TABLE asistencias MODIFY estado ENUM('presente','tardanza','ausente','no_regreso_almuerzo','justificado') NOT NULL DEFAULT 'presente'");
        } catch (PDOException $e) {}
        if ((int)$pdo->query('SELECT COUNT(*) FROM personal')->fetchColumn() === 0) {
            $demo = [['Juan Pérez','chofer'],['Carlos López','estiba'],['Miguel Torres','estiba'],['Luis Ramírez','chofer'],
                ['Pedro Sánchez','estiba'],['Andrés Vega','coordinador'],['Roberto Díaz','chofer'],['Fernando Ruiz','estiba'],
                ['Diego Morales','estiba'],['Sergio Castro','chofer']];
            $st = $pdo->prepare('INSERT INTO personal (nombre, rol) VALUES (?,?)');
            foreach ($demo as $d) $st->execute($d);
            $messages[] = '10 trabajadores de ejemplo agregados.';
        }
        $messages[] = 'Actualización completada.';
        $success = true;
    } catch (Exception $e) {
        $messages[] = 'Error: ' . $e->getMessage();
    }
}
if (PHP_SAPI === 'cli') { foreach ($messages as $m) echo $m . PHP_EOL; exit($success ? 0 : 1); }
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><title>Actualizar BD</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><div class="container py-5"><div class="col-md-6 mx-auto card p-4 shadow">
<h2>Actualización Personal</h2>
<?php foreach ($messages as $m): ?><div class="alert alert-<?= $success?'success':'danger' ?>"><?= htmlspecialchars($m) ?></div><?php endforeach; ?>
<?php if ($success): ?><a href="<?= htmlspecialchars(BASE_URL) ?>/modules/personal/index.php" class="btn btn-primary">Ir a Personal</a>
<?php else: ?><form method="post"><button class="btn btn-primary w-100">Ejecutar</button></form><?php endif; ?>
</div></div></body></html>
