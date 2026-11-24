<?php
/* For licensing terms, see /license.txt */
/**
 * Corrección única del historial plugin_proikos_data_log.
 *
 * Antes, el reporte marcaba "Desaprobado" a cualquier alumno con un 0 en algún
 * examen (entrada, taller o salida) aunque su promedio ponderado alcanzara el
 * mínimo. Ahora el estado depende solo del promedio. Este script pasa a
 * "Aprobado" los registros ya guardados como Desaprobado cuyo promedio
 * ponderado (entrada 10%, taller 60%, salida 30%) es >= 70%.
 *
 * Uso (desde la raíz de Chamilo o con ruta absoluta):
 *   php plugin/proikos/cron/fix_status_by_average.php            # simulación, no modifica nada
 *   php plugin/proikos/cron/fix_status_by_average.php --apply    # aplica los cambios
 *
 * Con --apply se genera antes un respaldo SQL de las filas afectadas
 * (sentencias UPDATE para revertir) en el mismo directorio que el script.
 *
 * @package chamilo.plugin.proikos
 */

require_once __DIR__ . '/../../../main/inc/global.inc.php';

if (php_sapi_name() !== 'cli') {
    exit; // no ejecutar desde el navegador
}

$apply = in_array('--apply', $argv, true);
$minScore = 70; // mismo umbral que ProikosPlugin::getDataReport()

$table = Database::get_main_table('plugin_proikos_data_log');
$avgExpr = '(entrance_exam * 0.10 + workshop * 0.60 + exit_exam * 0.30) / 20 * 100';

$sql = "SELECT id, dni, first_name, last_name, course_code, session_id,
               entrance_exam, workshop, exit_exam, score, status, status_id,
               ROUND($avgExpr, 2) AS calc_score
        FROM $table
        WHERE status_id = 0
          AND $avgExpr >= $minScore
        ORDER BY id ASC";
$result = Database::query($sql);

$rows = [];
while ($row = Database::fetch_assoc($result)) {
    $rows[] = $row;
}

echo PHP_EOL . ($apply ? '[APLICAR]' : '[SIMULACIÓN]') . ' Registros Desaprobado con promedio >= '
    . $minScore . '%: ' . count($rows) . PHP_EOL . PHP_EOL;

foreach ($rows as $row) {
    printf(
        "#%-6d DNI %-12s %-40s %-8s ses %-4d E:%5s T:%5s S:%5s => %6s%%%s",
        $row['id'],
        $row['dni'],
        mb_substr(trim($row['last_name'] . ' ' . $row['first_name']), 0, 40),
        $row['course_code'],
        $row['session_id'],
        $row['entrance_exam'],
        $row['workshop'],
        $row['exit_exam'],
        $row['calc_score'],
        PHP_EOL
    );
}

if (empty($rows)) {
    echo 'Nada que corregir.' . PHP_EOL;
    exit(0);
}

if (!$apply) {
    echo PHP_EOL . 'No se modificó nada. Ejecuta con --apply para aplicar los cambios.' . PHP_EOL;
    exit(0);
}

// Respaldo para revertir: deja cada fila con su status/status_id/score original.
$backupFile = __DIR__ . '/fix_status_by_average_backup_' . date('Ymd_His') . '.sql';
$backup = "-- Revertir fix_status_by_average.php (" . date('Y-m-d H:i:s') . ")\n";
foreach ($rows as $row) {
    $backup .= sprintf(
        "UPDATE %s SET status = '%s', status_id = %d, score = %s WHERE id = %d;\n",
        $table,
        Database::escape_string($row['status']),
        (int) $row['status_id'],
        $row['score'] === null ? 'NULL' : (float) $row['score'],
        (int) $row['id']
    );
}
if (file_put_contents($backupFile, $backup) === false) {
    echo 'ERROR: no se pudo escribir el respaldo en ' . $backupFile . '. No se aplicó nada.' . PHP_EOL;
    exit(1);
}
echo PHP_EOL . 'Respaldo guardado en: ' . $backupFile . PHP_EOL;

$ids = implode(',', array_map('intval', array_column($rows, 'id')));
Database::query(
    "UPDATE $table
     SET status = 'Aprobado',
         status_id = 2,
         score = ROUND($avgExpr, 2),
         updated_at = NOW()
     WHERE id IN ($ids)"
);

echo 'Registros actualizados a Aprobado: ' . count($rows) . PHP_EOL;
