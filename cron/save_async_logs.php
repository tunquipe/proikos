<?php
/* For licensing terms, see /license.txt */
/**
 * Cron del plugin Proikos: guarda en el historial de logs
 * (plugin_proikos_data_log) las notas de los alumnos de las sesiones
 * ASINCRÓNICAS (session_mode = 1).
 *
 * Pensado para ejecutarse cada noche desde el crontab del VPS:
 *   php /ruta/chamilo/plugin/proikos/cron/save_async_logs.php
 *
 * Solo inserta registros nuevos: ProikosPlugin::registerData() ya verifica
 * con checkRegisterLogData() que el alumno no exista para ese curso/sesión,
 * por lo que es seguro re-ejecutarlo (no duplica).
 *
 * @author Alex Aragon <alex.aragon@tunqui.pe>
 *
 * @package chamilo.plugin.proikos
 */

require_once __DIR__ . '/../../../main/inc/global.inc.php';
require_once __DIR__ . '/../ProikosPlugin.php';

if (php_sapi_name() !== 'cli') {
    exit; // no ejecutar desde el navegador
}

$plugin = ProikosPlugin::create();

$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

// Pares (curso, sesión) que pertenecen a sesiones asíncronas y tienen alumnos
// inscritos como estudiantes (srcu.status = 0).
$sql = "
    SELECT DISTINCT srcu.c_id, srcu.session_id
    FROM $tbl_session_course_user srcu
    INNER JOIN $tbl_session s ON s.id = srcu.session_id
    WHERE srcu.status = 0
      AND s.session_mode = " . ProikosPlugin::CATEGORY_ASINCRONO . "
    ORDER BY srcu.session_id ASC, srcu.c_id ASC";

$result = Database::query($sql);
$pairs = [];
while ($row = Database::fetch_assoc($result)) {
    $pairs[] = $row;
}

$totalInserted = 0;
$totalSkipped = 0;
$totalRegistered = 0; // status_id = 1 (sin nota): se omiten a propósito
$pairsProcessed = 0;

echo '[' . date('Y-m-d H:i:s') . "] Inicio cron logs asíncronos. Pares curso/sesión: " . count($pairs) . PHP_EOL;

foreach ($pairs as $pair) {
    $courseId = (int) $pair['c_id'];
    $sessionId = (int) $pair['session_id'];

    // isExport = true => sin LIMIT, trae todos los inscritos del par.
    $rawData = $plugin->getDataReport(null, $courseId, $sessionId, 0, 1, 10, true, 'ASC');

    if (empty($rawData['users'])) {
        continue;
    }

    $pairsProcessed++;

    foreach ($rawData['users'] as $studentRow) {
        // status_id = 1 => "Registrado" (puntaje 0): el alumno no rindió,
        // no se guarda en el log (mismo criterio que el cron original).
        if ($studentRow['status_id'] == 1) {
            $totalRegistered++;
            continue;
        }

        // registerData omite duplicados internamente (checkRegisterLogData).
        $insertedId = $plugin->registerData($studentRow);
        if ($insertedId > 0) {
            $totalInserted++;
        } else {
            $totalSkipped++;
        }
    }
}

echo '[' . date('Y-m-d H:i:s') . "] Fin. Pares con alumnos: $pairsProcessed | "
    . "Insertados: $totalInserted | Ya existían: $totalSkipped | Sin nota (omitidos): $totalRegistered" . PHP_EOL;
