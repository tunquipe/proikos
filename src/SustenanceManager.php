<?php

namespace src;
use Database;

/**
 * Módulo para gestionar sustentos de calificaciones en Chamilo
 * Utiliza la tabla plugin_proikos_sustenance
 */
class SustenanceManager
{
    const TABLE_PROIKOS_SUSTENANCE = 'plugin_proikos_sustenance';

    /**
     * Opciones disponibles para los sustentos
     */

    public static $options = array(
        0 => 'Sin observaciones',
        1 => 'Falta examen entrada',
        2 => 'Falta examen salida',
        3 => 'Falta taller',
        4 => 'No ingreso al curso',
        5 => 'No alcanzo nota minima',
        6 => 'Copio',
        7 => 'Conducta inapropiada',
        8 => 'No respondio al llamado',
        9 => 'Realizo otra actividad',
        10 => 'Suplantación',
        11 => 'Otros'
    );

    /**
     * @param int $user_id ID del alumno
     * @param int $course_id ID del curso
     * @param int $session_id ID de la sesión de formación
     * @param array $sustenance_codes Array con los códigos de sustento seleccionados
     * @param string $comment Comentario adicional
     * @return bool|int ID del registro insertado o false
     */
    public static function saveSustenance($user_id, $course_id, $session_id, $sustenance_codes, $comment = '')
    {

        $tableSustenance = Database::get_main_table(self::TABLE_PROIKOS_SUSTENANCE);

        if (!is_array($sustenance_codes) || empty($sustenance_codes)) {
            return false;
        }

        $sustenance_string = implode(',', $sustenance_codes);

        $params = array(
            'user_id' => intval($user_id),
            'course_id' => intval($course_id),
            'session_id' => intval($session_id),
            'sustenance_codes' => $sustenance_string,
            'comment' => $comment,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        );

        $id = Database::insert($tableSustenance, $params);

        return $id ? Database::insert_id() : false;
    }

    /**
     * Actualizar sustentos existentes
     *
     * @param int $sustenance_id ID del registro de sustento
     * @param array $sustenance_codes Array con los códigos de sustento
     * @param string $comment Comentario adicional
     * @return bool
     */
    public static function updateSustenance($sustenance_id, $sustenance_codes, $comment = '')
    {
        $tableSustenance = Database::get_main_table(self::TABLE_PROIKOS_SUSTENANCE);

        $sustenance_string = implode(',', $sustenance_codes);

        $params = array(
            'sustenance_codes' => $sustenance_string,
            'comment' => $comment,
            'updated_at' => date('Y-m-d H:i:s'),
            'id' => intval($sustenance_id)
        );

        return Database::update(
            $tableSustenance,
            $params,
            [
                'id = ? ' => [$sustenance_id],
            ]
        );
    }

    /**
     * Obtener sustentos de un alumno en un curso
     *
     * @param int $user_id ID del alumno
     * @param int $course_id ID del curso
     * @param int $session_id ID de la sesión (opcional)
     * @return array
     */
    public static function getSustenance($user_id, $course_id, $session_id = null): array
    {

        $tableSustenance = Database::get_main_table('plugin_proikos_sustenance');

        $sql = "SELECT * FROM $tableSustenance
                WHERE user_id = $user_id
                AND course_id = $course_id ";

        if ($session_id !== null) {
            $sql .= " AND session_id = $session_id ";
        }
        $result = Database::query($sql);
        $list = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $list = $row;
            }
        }
        return $list;
    }



    /**
     * Convertir códigos a etiquetas legibles
     *
     * @param string $sustenance_string Códigos separados por comas
     * @return string Etiquetas legibles separadas por comas
     */
    public static function formatSustenance($sustenance_string)
    {
        if (empty($sustenance_string)) {
            return 'Sin sustentos registrados';
        }

        $codes = explode(',', $sustenance_string);
        $labels = array();

        foreach ($codes as $code) {
            $code = trim($code);
            if (isset(self::$options[$code])) {
                $labels[] = self::$options[$code];
            }
        }

        return implode(', ', $labels);
    }
}
