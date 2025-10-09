<?php
/**
 * Clase para gestionar intentos de cursos en Chamilo
 * Usa la API de Database de Chamilo y la tabla plugin_proikos_data_log
 */
class CourseAttemptsManager {

    const TABLE_PROIKOS_COURSE_ATTEMPTS = 'plugin_proikos_course_attempts';
    const TABLE_PROIKOS_DATA_LOG = 'plugin_proikos_data_log';

    /**
     * Verificar si el usuario puede inscribirse en el curso
     */
    public static function canEnroll($userId, $courseId, $sessionId = null) {
        $attemptData = self::getAttemptStatus($userId, $courseId, $sessionId);

        // Si no existe registro, crear uno
        if (empty($attemptData)) {
            self::createAttemptRecord($userId, $courseId, $sessionId);
            return true;
        }

        // Si está bloqueado, no puede inscribirse
        if ($attemptData['is_blocked']) {
            return false;
        }

        // Si tiene menos de 3 intentos, puede inscribirse
        if ($attemptData['attempts_count'] < 3) {
            return true;
        }

        return false;
    }

    /**
     * Registrar intento desde la tabla plugin_proikos_data_log
     * Se ejecuta después de que el usuario completa el curso
     */
    public static function processAttemptFromLog($userId, $courseId, $sessionId = null) {
        // Obtener el último registro del log de este usuario para este curso
        $table_log = Database::get_main_table(self::TABLE_PROIKOS_DATA_LOG);

        $query = "SELECT score, status FROM $table_log
                  WHERE user_id = ? AND course_id = ?
                  ORDER BY created_at DESC LIMIT 1";

        $result = Database::query($query, [$userId, $courseId]);
        $logData = [];

        if (Database::num_rows($result) > 0) {
            $logData = Database::fetch_array($result);
        }

        if (empty($logData)) {
            return array('error' => 'No log data found');
        }

        $score = $logData['score'];
        $status = $logData['status']; // 'aprobado' o 'desaprobado'

        // Obtener datos actuales del usuario
        $attemptData = self::getAttemptStatus($userId, $courseId, $sessionId);

        // Crear registro si no existe
        if (empty($attemptData)) {
            self::createAttemptRecord($userId, $courseId, $sessionId);
            $attempts_count = 0;
        } else {
            $attempts_count = $attemptData['attempts_count'];
        }

        // Incrementar intentos
        self::incrementAttempts($userId, $courseId, $sessionId);
        $currentAttempt = $attempts_count + 1;

        // Si aprobó, resetear intentos
        if ($status === 'aprobado') {
            self::resetAttempts($userId, $courseId, $sessionId);
            return array(
                'result' => 'aprobado',
                'score' => $score,
                'attempt_number' => $currentAttempt,
                'attempts_reset' => 0,
                'mensaje' => '¡Felicidades! Aprobaste el curso.'
            );
        }

        // Si desaprobó y llegó al intento 3, bloquear usuario
        if ($currentAttempt >= 3 && $status === 'desaprobado') {
            self::blockUser($userId, $courseId, $sessionId);
            return array(
                'result' => 'desaprobado',
                'score' => $score,
                'attempt_number' => $currentAttempt,
                'bloqueado' => true,
                'mensaje' => 'Has agotado tus 3 intentos. Acceso bloqueado a este curso.'
            );
        }

        // Si desaprobó pero tiene intentos restantes
        $intentosRestantes = 3 - $currentAttempt;
        return array(
            'result' => 'desaprobado',
            'score' => $score,
            'attempt_number' => $currentAttempt,
            'intentos_restantes' => $intentosRestantes,
            'mensaje' => "Desaprobado (Nota: $score). Te quedan $intentosRestantes intentos."
        );
    }

    /**
     * Incrementar el contador de intentos
     */
    public static function incrementAttempts($userId, $courseId, $sessionId = null) {
        $table = Database::get_main_table(self::TABLE_PROIKOS_COURSE_ATTEMPTS);

        $query = "UPDATE $table
                  SET attempts_count = attempts_count + 1
                  WHERE user_id = ? AND course_id = ? AND session_id = ?";

        Database::query($query, [$userId, $courseId, $sessionId]);
        return true;
    }

    /**
     * Bloquear usuario para el curso
     */
    public static function blockUser($userId, $courseId, $sessionId = null) {
        $table = Database::get_main_table(self::TABLE_PROIKOS_COURSE_ATTEMPTS);

        $params = [
            'is_blocked' => 1
        ];

        $where = "user_id = ? AND course_id = ? AND session_id = ?";

        Database::update(
            $table,
            $params,
            ["user_id = ? AND course_id = ? AND session_id = ?" => [$userId, $courseId, $sessionId]]
        );

        return true;
    }

    /**
     * Resetear intentos después de aprobar
     */
    public static function resetAttempts($userId, $courseId, $sessionId = null) {
        $table = Database::get_main_table(self::TABLE_PROIKOS_COURSE_ATTEMPTS);

        $params = [
            'attempts_count' => 0,
            'is_blocked' => 0
        ];

        Database::update(
            $table,
            $params,
            ["user_id = ? AND course_id = ? AND session_id = ?" => [$userId, $courseId, $sessionId]]
        );

        return true;
    }

    /**
     * Crear registro inicial de intentos
     */
    public static function createAttemptRecord($userId, $courseId, $sessionId = null, $category = 'general') {
        $table = Database::get_main_table(self::TABLE_PROIKOS_COURSE_ATTEMPTS);

        $params = [
            "user_id" => $userId,
            "course_id" => $courseId,
            "session_id" => $sessionId,
            "attempts_count" => 0,
            "is_blocked" => 0,
            "category" => $category
        ];

        $id = Database::insert($table, $params);
        if ($id > 0) {
            return $id;
        }
        return 0;
    }

    /**
     * Obtener estado actual del usuario en un curso
     */
    public static function getAttemptStatus($userId, $courseId, $sessionId = null) {
        $table = Database::get_main_table(self::TABLE_PROIKOS_COURSE_ATTEMPTS);

        $query = "SELECT attempts_count, is_blocked, id
                  FROM $table
                  WHERE user_id = ? AND course_id = ? AND session_id = ?";

        $result = Database::query($query, [$userId, $courseId, $sessionId]);
        $item = [];

        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $item = $row;
            }
        }

        return $item;
    }

    /**
     * Obtener historial de intentos desde la tabla plugin_proikos_data_log
     */
    public static function getAttemptHistory($userId, $courseId, $sessionId = null) {
        $table_log = Database::get_main_table(self::TABLE_PROIKOS_DATA_LOG);

        $query = "SELECT score, status, created_at
                  FROM $table_log
                  WHERE user_id = ? AND course_id = ?
                  ORDER BY created_at ASC";

        $result = Database::query($query, [$userId, $courseId]);
        $attempts = array();
        $attemptNumber = 1;

        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $row['attempt_number'] = $attemptNumber;
                $attempts[] = $row;
                $attemptNumber++;
            }
        }

        return $attempts;
    }

    /**
     * Obtener información del usuario en un curso específico
     */
    public static function getUserCourseInfo($userId, $courseId, $sessionId = null) {
        $attemptStatus = self::getAttemptStatus($userId, $courseId, $sessionId);
        $history = self::getAttemptHistory($userId, $courseId, $sessionId);

        return [
            'attempts_count' => $attemptStatus['attempts_count'] ?? 0,
            'is_blocked' => $attemptStatus['is_blocked'] ?? 0,
            'can_enroll' => self::canEnroll($userId, $courseId, $sessionId),
            'history' => $history,
            'intentos_restantes' => 3 - ($attemptStatus['attempts_count'] ?? 0)
        ];
    }
}
