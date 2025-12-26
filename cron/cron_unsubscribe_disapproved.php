<?php
/**
 * Cron Job: Desuscribir usuarios desaprobados de sus sesiones
 *
 * Este script se ejecuta diariamente a las 00:00 horas
 * Procesa usuarios desaprobados de los últimos 7 días
 *
 * Ubicación sugerida: /plugin/proikos/cron/cron_unsubscribe_disapproved.php
 */

// Suprimir warnings de Chamilo que no afectan el cron
error_reporting(E_ERROR | E_PARSE);

// Configuración para ejecución CLI
/*if (php_sapi_name() !== 'cli') {
    die('Este script solo puede ejecutarse desde la línea de comandos.');
}*/

// Cargar el entorno de Chamilo
$cidReset = true;
require_once __DIR__ . '/../../../main/inc/global.inc.php';

// Cargar el plugin
$plugin = ProikosPlugin::create();
$logFile = '';
// Configurar log
/*$logFile = __DIR__ . '/../logs/cron_unsubscribe_' . date('Y-m-d') . '.log';
$logDir = dirname($logFile);

// Crear directorio de logs si no existe
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}*/

/**
 * Función para escribir en el log
 */
function writeLog($message, $logFile) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    //file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage . '<br><br>'; // También mostrar en consola
}

function getSessionStudents(): array
{
    $modeSession = 1;
    $tableSession = Database::get_main_table(TABLE_MAIN_SESSION);
    $tableSessionUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);
    $tableSessionUserCourse = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
    $tableUser = Database::get_main_table(TABLE_MAIN_USER);

    $sql = "SELECT DISTINCT s.id as session_id, su.user_id, scu.c_id, u.username,
            CONCAT(u.firstname, ' ', u.lastname) AS student, su.registered_at
            FROM $tableSession s
            INNER JOIN $tableSessionUser su ON su.session_id = s.id
            INNER JOIN $tableSessionUserCourse scu ON scu.session_id = su.session_id AND scu.user_id = su.user_id
            INNER JOIN $tableUser u ON u.id = su.user_id
            WHERE s.session_mode = $modeSession;";
    $result = Database::query($sql);
    $list = [];
    if (Database::num_rows($result) > 0) {
        while ($row = Database::fetch_assoc($result)) {
            $list[] = $row;
        }
    }

    return $list;
}
/**
 * Verifica si un usuario está inscrito en una sesión
 *
 * @param int $userId ID del usuario
 * @param int $sessionId ID de la sesión
 * @return bool
 */
function isUserSubscribedToSession($userId, $sessionId): bool
{
    $userId = intval($userId);
    $sessionId = intval($sessionId);

    // Método 1: Usando SessionManager (recomendado)
    $isSubscribed = SessionManager::isUserSubscribedAsStudent($sessionId, $userId);

    if ($isSubscribed) {
        return true;
    }

    // Método 2: Consulta directa como respaldo
    $tableSessionUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);
    $sql = "SELECT COUNT(*) as count
            FROM $tableSessionUser
            WHERE session_id = $sessionId
            AND user_id = $userId
            AND relation_type = 0"; // 0 = estudiante

    $result = Database::query($sql);
    $row = Database::fetch_assoc($result);

    return intval($row['count']) > 0;
}

function StartProcessingDeleteUser($userId, $sessionId, $obj)
{
        $exercises = $obj->getExercisesSessionAndCourse($sessionId);
        $lps = $obj->getLPSession($sessionId);
        foreach ($lps as $lp) {
            $course = ['real_id' => $lp['course_id']];
            Event::delete_student_lp_events(
                $userId,
                $lp['lp_id'],
                $course,
                $lp['session_id']
            );
        }
        foreach ($exercises as $exercise) {
            $obj->deleteTrackExercise($exercise, $userId, $sessionId);
        }
        SessionManager::unsubscribe_user_from_session($sessionId, $userId);
}

function procesarDiasUsuarios($usuarios, $diasPermitidos = 5, $plugin) {
    $resultado = [];
    $fechaActual = new DateTime();


    foreach ($usuarios as $index => $usuario) {
        try {
            // Obtener la fecha de registro
            $fechaRegistro = new DateTime($usuario['registered_at']);
            $diferencia = $fechaActual->diff($fechaRegistro);
            $diasTranscurridos = $diferencia->days;

            $excedido = $diasTranscurridos > $diasPermitidos;

            $userScore = $plugin->getResultExerciseStudent($usuario['user_id'], $usuario['c_id'], $usuario['session_id']);

            $ponderacion_entrada = 0.10;  // 10%
            $ponderacion_salida = 0.30;   // 30%
            $ponderacion_taller = 0.60;   // 60%

            $entrance = floatval($userScore['examen_de_entrada']);
            $workshop = floatval($userScore['taller']);
            $exit = floatval($userScore['examen_de_salida']);
            $promedioPonderado = ($entrance * $ponderacion_entrada) + ($workshop * $ponderacion_taller) + ($exit * $ponderacion_salida);

            $puntaje_total = (($entrance * $ponderacion_entrada) +
                    ($exit * $ponderacion_salida) +
                    ($workshop * $ponderacion_taller)) / 20 * 100;

            $resultado[] = [
                'index' => $index,
                'session_id' => $usuario['session_id'],
                'user_id' => $usuario['user_id'],
                'registered_at' => $usuario['registered_at'],
                /*'dias_transcurridos' => $diasTranscurridos,
                'dias_permitidos' => $diasPermitidos,
                'dias_restantes' => max(0, $diasPermitidos - $diasTranscurridos),*/
                'student' => $usuario['student'],
                'username' => $usuario['username'],
                'excedido' => $excedido,
                'estado' => $excedido ? 'EXPIRADO' : 'ACTIVO',
                'promedio' => $promedioPonderado,
                'puntaje_total' => $puntaje_total,
            ];

        } catch (Exception $e) {
            // Si hay error al procesar una fecha, marcar como error
            $resultado[] = [
                'index' => $index,
                'session_id' => $usuario['session_id'],
                'user_id' => $usuario['user_id'],
                'registered_at' => $usuario['registered_at'],
                'excedido' => false,
                'estado' => 'ERROR',
                'error' => $e->getMessage()
            ];
        }
    }

    return $resultado;
}

// Inicio del proceso
writeLog("=== INICIO DEL CRON: Desuscripción de usuarios desaprobados ===", $logFile);

try {
    $endDate = date('Y-m-d'); // Hoy
    $startDate = date('Y-m-d', strtotime('-30 days')); // Hace 7 días
    print_r("Buscando desaprobados desde: $startDate hasta: $endDate", $logFile . '<br>');

    $sessionCategoryIds = [2, 3];

    $usersSessions = getSessionStudents();
    $allowedUsers = procesarDiasUsuarios($usersSessions,5, $plugin);

    $disapprovedUsers = $plugin->getDisapprovedUsersByDateRange(
        $startDate,
        $endDate,
        $sessionCategoryIds
    );

    $totalUsers = count($allowedUsers);

    writeLog("Total de usuarios encontrados: $totalUsers", $logFile);

    if ($totalUsers === 0) {
        writeLog("No hay usuarios para procesar. Finalizando.", $logFile);
        exit(0);
    }

    $processedCount = 0;
    $skippedCount = 0;
    $errorCount = 0;
    $expiredUsersCount = 0;

    foreach ($allowedUsers as $user) {
        $userId = $user['user_id'];
        $sessionId = $user['session_id'];
        $studentName = $user['student'];
        $username = $user['username'];
        $expired = $user['excedido'];
        $status = $user['estado'];
        $average = $user['promedio'];

        if($status == 'EXPIRADO' && $average == 0) {
            $expiredUsersCount++;
            writeLog("Verificando usuario: $studentName (ID: $userId, DNI: $username) - Sesión: $sessionId", $logFile);
            writeLog("  → Usuario $status. Procediendo a eliminarlo...", $logFile);

            StartProcessingDeleteUser($userId, $sessionId, $plugin);

        }

    }

    foreach ($disapprovedUsers as $user) {
        $userId = $user['user_id'];
        $sessionId = $user['session_id'];
        $studentName = $user['student'];
        $username = $user['username'];
        $status = $user['status'];

        // Verificar si el usuario está inscrito en la sesión
        if (!isUserSubscribedToSession($userId, $sessionId)) {
            writeLog("  ⊘ Usuario NO está inscrito en la sesión. Saltando...", $logFile);
            $skippedCount++;
            continue;
        }

        if($status == 'Desaprobado') {
            writeLog("Verificando usuario: $studentName (ID: $userId, DNI: $username) - Sesión: $sessionId", $logFile);
            writeLog("  → Usuario encontrado procediendo a eliminarlo...", $logFile);
            StartProcessingDeleteUser($userId, $sessionId, $plugin);
        }

    }

    writeLog("=== FIN DEL CRON ===", $logFile);

} catch (Exception $e) {
    writeLog("ERROR CRÍTICO: " . $e->getMessage(), $logFile);
    exit(1);
}

exit(0);
