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

require_once __DIR__ . '/../config.php';
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
    $errorCount = 0;

    // Procesar cada usuario desaprobado
    foreach ($disapprovedUsers as $user) {
        $userId = $user['user_id'];
        $sessionId = $user['session_id'];
        $studentName = $user['student'];
        $username = $user['username'];

        writeLog("Procesando usuario: $studentName (ID: $userId, DNI: $username) - Sesión: $sessionId", $logFile);

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
    writeLog("Total procesados correctamente: $processedCount", $logFile);
    writeLog("Total con errores: $errorCount", $logFile);
    writeLog("=== FIN DEL CRON ===", $logFile);

} catch (Exception $e) {
    writeLog("ERROR CRÍTICO: " . $e->getMessage(), $logFile);
    exit(1);
}

exit(0);
