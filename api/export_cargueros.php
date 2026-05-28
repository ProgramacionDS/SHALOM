<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$fecha = $_GET['fecha'] ?? date('Y-m-d');
$turno = $_GET['turno'] ?? 'manana';
$pdo = getConnection();
$stmt = $pdo->prepare('SELECT fecha,turno,destino,flota,hora_entrada,hora_salida,estado,observaciones FROM registros_cargueros WHERE fecha=? AND turno=? ORDER BY id');
$stmt->execute([$fecha, $turno]);
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="TURNO_'.$turno.'_'.$fecha.'.csv"');
$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
fputcsv($out, ['Fecha','Turno','Destino','Flota','Entrada','Salida','Estado','Observaciones']);
foreach ($stmt->fetchAll() as $r) {
    fputcsv($out, [$r['fecha'],$r['turno'],$r['destino'],$r['flota'],$r['hora_entrada']??'',$r['hora_salida']??'',estadoCargueroLabel($r['estado']),$r['observaciones']??'']);
}
fclose($out);
exit;
