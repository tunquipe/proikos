<?php
/**
 * Cron Job: Desuscribir usuarios desaprobados de sus sesiones
 *
 * Este script se ejecuta diariamente a las 00:00 horas
 * Procesa usuarios desaprobados de los últimos 7 días
 *
 * Ubicación sugerida: /plugin/proikos/cron/cron_unsubscribe_disapproved.php
 */

// Configuración para ejecución CLI
if (php_sapi_name() !== 'cli') {
    die('Este script solo puede ejecutarse desde la línea de comandos.');
}

// Cargar el entorno de Chamilo
$cidReset = true;
require_once __DIR__ . '/../../../main/inc/global.inc.php';

// Cargar el plugin
$plugin = ProikosPlugin::create();

// Configurar log
$logFile = __DIR__ . '/../logs/cron_unsubscribe_' . date('Y-m-d') . '.log';
$logDir = dirname($logFile);

// Crear directorio de logs si no existe
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

/**
 * Función para escribir en el log
 */
function writeLog($message, $logFile) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage; // También mostrar en consola
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

// Inicio del proceso
writeLog("=== INICIO DEL CRON: Desuscripción de usuarios desaprobados ===", $logFile);

try {
    // Calcular rango de fechas (últimos 7 días)
    $endDate = date('Y-m-d'); // Hoy
    $startDate = date('Y-m-d', strtotime('-7 days')); // Hace 7 días

    writeLog("Buscando desaprobados desde: $startDate hasta: $endDate", $logFile);

    // Categorías de sesión a procesar (ajusta según tu configuración)
    $sessionCategoryIds = [2, 3];

    // Obtener usuarios desaprobados
    $disapprovedUsers = $plugin->getDisapprovedUsersByDateRange(
        $startDate,
        $endDate,
        $sessionCategoryIds
    );

    $totalUsers = count($disapprovedUsers);
    writeLog("Total de usuarios desaprobados encontrados: $totalUsers", $logFile);

    if ($totalUsers === 0) {
        writeLog("No hay usuarios para procesar. Finalizando.", $logFile);
        exit(0);
    }

    $processedCount = 0;
    $skippedCount = 0;
    $errorCount = 0;

    // Procesar cada usuario desaprobado
    foreach ($disapprovedUsers as $user) {
        $userId = $user['user_id'];
        $sessionId = $user['session_id'];
        $studentName = $user['student'];
        $username = $user['username'];

        writeLog("Verificando usuario: $studentName (ID: $userId, DNI: $username) - Sesión: $sessionId", $logFile);

        // Verificar si el usuario está inscrito en la sesión
        if (!isUserSubscribedToSession($userId, $sessionId)) {
            writeLog("  ⊘ Usuario NO está inscrito en la sesión. Saltando...", $logFile);
            $skippedCount++;
            continue;
        }

        writeLog("  → Usuario inscrito. Procediendo a procesar...", $logFile);

        try {
            // 1. Obtener ejercicios y LPs de la sesión
            $exercises = $plugin->getExercisesSessionAndCourse($sessionId);
            $lps = $plugin->getLPSession($sessionId);

            // 2. Eliminar progreso de LPs (lecciones)
            $lpCount = 0;
            foreach ($lps as $lp) {
                $course = ['real_id' => $lp['course_id']];
                Event::delete_student_lp_events(
                    $userId,
                    $lp['lp_id'],
                    $course,
                    $lp['session_id']
                );
                $lpCount++;
            }
            writeLog("  - LPs eliminados: $lpCount", $logFile);

            // 3. Eliminar progreso de ejercicios
            $exerciseCount = 0;
            foreach ($exercises as $exercise) {
                $plugin->deleteTrackExercise($exercise, $userId, $sessionId);
                $exerciseCount++;
            }
            writeLog("  - Ejercicios eliminados: $exerciseCount", $logFile);

            // 4. Desuscribir usuario de la sesión
            SessionManager::unsubscribe_user_from_session($sessionId, $userId);
            writeLog("  - Usuario desuscrito de la sesión $sessionId", $logFile);

            $processedCount++;
            writeLog("  ✓ Usuario procesado correctamente", $logFile);

        } catch (Exception $e) {
            $errorCount++;
            writeLog("  ✗ ERROR procesando usuario $userId: " . $e->getMessage(), $logFile);
        }
    }

    // Resumen final
    writeLog("=== RESUMEN ===", $logFile);
    writeLog("Total encontrados: $totalUsers", $logFile);
    writeLog("Procesados correctamente: $processedCount", $logFile);
    writeLog("Saltados (no inscritos): $skippedCount", $logFile);
    writeLog("Con errores: $errorCount", $logFile);
    writeLog("=== FIN DEL CRON ===", $logFile);

} catch (Exception $e) {
    writeLog("ERROR CRÍTICO: " . $e->getMessage(), $logFile);
    exit(1);
}

exit(0);
