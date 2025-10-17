<?php

namespace src;

use Database;

/**
 * Módulo para gestionar bloqueo de ejercicios (quiz) en Chamilo
 * Utiliza la tabla plugin_proikos_quiz_block
 */
class QuizBlockManager
{
    const TABLE_QUIZ_BLOCK = 'plugin_proikos_quiz_block';

    /**
     * Guardar bloqueos de quiz para un usuario
     *
     * @param int $user_id ID del alumno
     * @param int $course_id ID del curso
     * @param int $session_id ID de la sesión
     * @param array $quiz_ids Array con los IDs de los quiz a bloquear
     * @return bool|int ID del registro insertado o false
     */
    public static function saveQuizBlock($user_id, $course_id, $session_id, $quiz_ids)
    {
        $tableQuizBlock = Database::get_main_table(self::TABLE_QUIZ_BLOCK);

        if (!is_array($quiz_ids) || empty($quiz_ids)) {
            return false;
        }

        // Convertir array a string separado por comas
        $quiz_string = implode(',', array_map('intval', $quiz_ids));

        $params = array(
            'user_id' => intval($user_id),
            'course_id' => intval($course_id),
            'session_id' => intval($session_id),
            'exam_ids' => $quiz_string,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        );

        $id = Database::insert($tableQuizBlock, $params);

        return $id ? Database::insert_id() : false;
    }

    /**
     * Actualizar bloqueos existentes
     *
     * @param int $block_id ID del registro de bloqueo
     * @param array $quiz_ids Array con los IDs de quiz
     * @return bool
     */
    public static function updateQuizBlock($block_id, $quiz_ids)
    {
        $tableQuizBlock = Database::get_main_table(self::TABLE_QUIZ_BLOCK);

        $quiz_string = implode(',', array_map('intval', $quiz_ids));

        $params = array(
            'exam_ids' => $quiz_string,
            'updated_at' => date('Y-m-d H:i:s')
        );

        return Database::update(
            $tableQuizBlock,
            $params,
            [
                'id = ?' => [$block_id],
            ]
        );
    }

    /**
     * Obtener bloqueos de un alumno en un curso
     *
     * @param int $user_id ID del alumno
     * @param int $course_id ID del curso
     * @param int $session_id ID de la sesión
     * @return array
     */
    public static function getQuizBlocks($user_id, $course_id, $session_id): array
    {
        $tableQuizBlock = Database::get_main_table(self::TABLE_QUIZ_BLOCK);

        $sql = "SELECT * FROM $tableQuizBlock
                WHERE user_id = $user_id
                AND course_id = $course_id
                AND session_id = $session_id";

        $result = Database::query($sql);
        $data = [];

        if (Database::num_rows($result) > 0) {
            $data = Database::fetch_array($result);
        }

        return $data;
    }

    /**
     * Verificar si un quiz está bloqueado para un usuario
     *
     * @param int $user_id ID del alumno
     * @param int $course_id ID del curso
     * @param int $session_id ID de la sesión
     * @param int $quiz_id ID del quiz
     * @return bool
     */
    public static function isQuizBlocked($user_id, $course_id, $session_id, $quiz_id): bool
    {
        $blocks = self::getQuizBlocks($user_id, $course_id, $session_id);

        if (empty($blocks)) {
            return false;
        }

        $blocked_quizzes = explode(',', $blocks['exam_ids']);
        return in_array($quiz_id, $blocked_quizzes);
    }

    /**
     * Obtener todos los ejercicios de un curso/sesión
     *
     * @param int $course_id ID del curso
     * @param int $session_id ID de la sesión (0 para curso base)
     * @return array Lista de ejercicios
     */
    public static function getCourseQuizzes($course_id): array
    {
        $tableQuiz = Database::get_course_table('quiz');
        if (!$course_id) {
            return [];
        }

        $sql = "SELECT iid as id, title, description, active
                FROM $tableQuiz
                WHERE c_id = $course_id
                AND active >= 0
                ORDER BY title ASC";

        $result = Database::query($sql);
        $quizzes = [];

        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_assoc($result)) {
                $quizzes[] = $row;
            }
        }

        return $quizzes;
    }

    /**
     * Eliminar bloqueos de un usuario
     *
     * @param int $user_id ID del alumno
     * @param int $course_id ID del curso
     * @param int $session_id ID de la sesión
     * @return bool
     */
    public static function deleteQuizBlock($user_id, $course_id, $session_id): bool
    {
        $tableQuizBlock = Database::get_main_table(self::TABLE_QUIZ_BLOCK);

        $sql = "DELETE FROM $tableQuizBlock
                WHERE user_id = $user_id
                AND session_id = $session_id";

        $result = Database::query($sql);
        return $result !== false;
    }

    /**
     * Obtener información de quiz bloqueados con nombres
     *
     * @param int $user_id ID del alumno
     * @param int $course_id ID del curso
     * @param int $session_id ID de la sesión
     * @return array
     */
    public static function getBlockedQuizzesWithNames($user_id, $course_id, $session_id): array
    {
        $blocks = self::getQuizBlocks($user_id, $course_id, $session_id);

        if (empty($blocks)) {
            return [];
        }

        $blocked_ids = explode(',', $blocks['exam_ids']);
        $quizzes = self::getCourseQuizzes($course_id, $session_id);

        $blocked_quizzes = [];
        foreach ($quizzes as $quiz) {
            if (in_array($quiz['id'], $blocked_ids)) {
                $blocked_quizzes[] = $quiz;
            }
        }

        return $blocked_quizzes;
    }
}
