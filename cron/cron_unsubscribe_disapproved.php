<?php
/**
 * Cron Job: Unsubscribe disapproved users from their sessions
 *
 * This script runs daily at 00:00 hours
 * Processes disapproved users from the last 7 days
 *
 * Suggested location: /plugin/proikos/cron/cron_unsubscribe_disapproved.php
 *
 * Usage: php cron_unsubscribe_disapproved.php
 */

// Suppress Chamilo warnings that don't affect the cron
error_reporting(E_ERROR | E_PARSE);

// Configuration for CLI execution
if (php_sapi_name() !== 'cli') {
    die('This script can only be executed from the command line.');
}

// Load Chamilo environment
$cidReset = true;
require_once __DIR__ . '/../../../main/inc/global.inc.php';

// Load the plugin
$plugin = ProikosPlugin::create();
$logFile = '';

// Configure log
$logFile = __DIR__ . '/../logs/cron_unsubscribe_' . date('Y-m-d') . '.log';
$logDir = dirname($logFile);

// Create logs directory if it doesn't exist
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

/**
 * Function to write to the log
 */
function writeLog($message, $logFile) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage; // Also display in console
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
 * Checks if a user is enrolled in a session
 *
 * @param int $userId User ID
 * @param int $sessionId Session ID
 * @return bool
 */
function isUserSubscribedToSession($userId, $sessionId): bool
{
    $userId = intval($userId);
    $sessionId = intval($sessionId);

    // Method 1: Using SessionManager (recommended)
    $isSubscribed = SessionManager::isUserSubscribedAsStudent($sessionId, $userId);

    if ($isSubscribed) {
        return true;
    }

    // Method 2: Direct query as backup
    $tableSessionUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);
    $sql = "SELECT COUNT(*) as count
            FROM $tableSessionUser
            WHERE session_id = $sessionId
            AND user_id = $userId
            AND relation_type = 0"; // 0 = student

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
            // Get registration date
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
                'student' => $usuario['student'],
                'username' => $usuario['username'],
                'excedido' => $excedido,
                'estado' => $excedido ? 'EXPIRADO' : 'ACTIVO',
                'promedio' => $promedioPonderado,
                'puntaje_total' => $puntaje_total,
            ];

        } catch (Exception $e) {
            // If there's an error processing a date, mark as error
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

// Start of process
writeLog("=== START OF CRON: Unsubscription of disapproved users ===", $logFile);

try {
    $endDate = date('Y-m-d'); // Today
    $startDate = date('Y-m-d', strtotime('-30 days')); // 30 days ago
    writeLog("Searching for disapproved users from: $startDate to: $endDate", $logFile);

    $sessionCategoryIds = [2, 3];

    $usersSessions = getSessionStudents();
    $allowedUsers = procesarDiasUsuarios($usersSessions, 5, $plugin);

    $disapprovedUsers = $plugin->getDisapprovedUsersByDateRange(
        $startDate,
        $endDate,
        $sessionCategoryIds
    );

    $totalUsers = count($allowedUsers);

    writeLog("Total users found: $totalUsers", $logFile);

    if ($totalUsers === 0) {
        writeLog("No users to process. Finishing.", $logFile);
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
            writeLog("Checking user: $studentName (ID: $userId, DNI: $username) - Session: $sessionId", $logFile);
            writeLog("  → User $status. Proceeding to delete...", $logFile);

            StartProcessingDeleteUser($userId, $sessionId, $plugin);
        }
    }

    foreach ($disapprovedUsers as $user) {
        $userId = $user['user_id'];
        $sessionId = $user['session_id'];
        $studentName = $user['student'];
        $username = $user['username'];
        $status = $user['status'];

        // Check if user is enrolled in the session
        if (!isUserSubscribedToSession($userId, $sessionId)) {
            writeLog("  ⊘ User is NOT enrolled in the session. Skipping...", $logFile);
            $skippedCount++;
            continue;
        }

        if($status == 'Desaprobado') {
            writeLog("Checking user: $studentName (ID: $userId, DNI: $username) - Session: $sessionId", $logFile);
            writeLog("  → User found, proceeding to delete...", $logFile);
            StartProcessingDeleteUser($userId, $sessionId, $plugin);
        }
    }

    writeLog("=== END OF CRON ===", $logFile);
    writeLog("Summary: Expired users processed: $expiredUsersCount, Skipped: $skippedCount", $logFile);

} catch (Exception $e) {
    writeLog("CRITICAL ERROR: " . $e->getMessage(), $logFile);
    writeLog("Stack trace: " . $e->getTraceAsString(), $logFile);
    exit(1);
}

exit(0);
