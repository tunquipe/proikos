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
     * Configuración de ponderación
     */
    protected $ponderacion = array(
        'examen_de_entrada' => 0.10,  // 10%
        'examen_de_salida' => 0.30,   // 30%
        'taller' => 0.60              // 60%
    );

    /**
     * Nota mínima requerida (porcentaje)
     */
    protected $nota_minima = 70.5;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Puede ser usado para inicializar valores personalizados
    }

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
        $tableSustenance = Database::get_main_table(self::TABLE_PROIKOS_SUSTENANCE);

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

    /**
     * Asignar sustentos automáticamente basado en notas
     *
     * @param array $notas Array con claves: 'examen_de_entrada', 'examen_de_salida', 'taller'
     * @param int $user_id ID del usuario
     * @param int $course_id ID del curso
     * @param int $session_id ID de la sesión
     * @param string $comment Comentario adicional (opcional)
     * @param bool $actualizar_si_existe Si es true, actualiza el registro existente; si es false, no hace nada si ya existe
     * @return array Array con ['success' => bool, 'sustenance_codes' => [], 'puntaje_total' => float, 'message' => string]
     */
    public function asignarSustentosPorNotas($notas, $user_id, $course_id, $session_id, $comment = '', $actualizar_si_existe = false)
    {
        // Validar que las notas tengan las claves requeridas
        $notasRequeridas = ['examen_de_entrada', 'examen_de_salida', 'taller'];
        foreach ($notasRequeridas as $nota) {
            if (!isset($notas[$nota])) {
                return [
                    'success' => false,
                    'message' => "Falta la nota de: $nota",
                    'sustenance_codes' => [],
                    'puntaje_total' => 0,
                    'action' => 'error'
                ];
            }
        }

        // Obtener valores de notas
        $entrada = floatval($notas['examen_de_entrada']);
        $salida = floatval($notas['examen_de_salida']);
        $taller = floatval($notas['taller']);

        // Calcular puntaje total ponderado
        $puntaje_total = $this->calcularPuntajePonderado($entrada, $salida, $taller);

        // Determinar sustentos
        $sustenance_codes = $this->determinarSustentos($entrada, $salida, $taller, $puntaje_total);

        // Verificar si ya existe un registro
        $existente = self::getSustenance($user_id, $course_id, $session_id);

        if (!empty($existente)) {
            // Ya existe un registro
            if ($actualizar_si_existe) {
                // Actualizar el registro existente
                $updated = self::updateSustenance(
                    $existente['id'],
                    $sustenance_codes,
                    $comment
                );

                return [
                    'success' => $updated,
                    'sustenance_codes' => $sustenance_codes,
                    'puntaje_total' => $puntaje_total,
                    'message' => $this->generarMensaje($sustenance_codes, $puntaje_total) . ' (Actualizado)',
                    'entrada' => $entrada,
                    'salida' => $salida,
                    'taller' => $taller,
                    'action' => 'updated',
                    'record_id' => $existente['id']
                ];
            } else {
                // No actualizar, solo retornar que ya existe
                return [
                    'success' => true,
                    'sustenance_codes' => explode(',', $existente['sustenance_codes']),
                    'puntaje_total' => $puntaje_total,
                    'message' => 'El registro ya existe. No se realizó ninguna acción.',
                    'entrada' => $entrada,
                    'salida' => $salida,
                    'taller' => $taller,
                    'action' => 'skipped',
                    'record_id' => $existente['id']
                ];
            }
        }

        // No existe registro, crear uno nuevo
        // Solo guardar si hay sustentos (no guardar "Sin observaciones" si no hay problemas)
        if (!empty($sustenance_codes) && !($sustenance_codes === [0])) {
            $id = self::saveSustenance($user_id, $course_id, $session_id, $sustenance_codes, $comment);

            return [
                'success' => (bool)$id,
                'sustenance_codes' => $sustenance_codes,
                'puntaje_total' => $puntaje_total,
                'message' => $this->generarMensaje($sustenance_codes, $puntaje_total) . ' (Nuevo)',
                'entrada' => $entrada,
                'salida' => $salida,
                'taller' => $taller,
                'action' => 'created',
                'record_id' => $id
            ];
        }

        // No hay sustentos que guardar (todo está bien)
        return [
            'success' => true,
            'sustenance_codes' => $sustenance_codes,
            'puntaje_total' => $puntaje_total,
            'message' => $this->generarMensaje($sustenance_codes, $puntaje_total),
            'entrada' => $entrada,
            'salida' => $salida,
            'taller' => $taller,
            'action' => 'no_sustenance_needed'
        ];
    }

    /**
     * Calcular puntaje total ponderado
     *
     * @param float $entrada Nota examen de entrada
     * @param float $salida Nota examen de salida
     * @param float $taller Nota taller
     * @return float Puntaje ponderado (0-100)
     */
    protected function calcularPuntajePonderado($entrada, $salida, $taller)
    {
        $puntaje = (($entrada * $this->ponderacion['examen_de_entrada']) +
                ($salida * $this->ponderacion['examen_de_salida']) +
                ($taller * $this->ponderacion['taller'])) / 20 * 100;

        return round($puntaje, 2);
    }

    /**
     * Determinar qué sustentos aplican
     *
     * @param float $entrada Nota examen de entrada
     * @param float $salida Nota examen de salida
     * @param float $taller Nota taller
     * @param float $puntaje_total Puntaje ponderado total
     * @return array Codes de sustentos a asignar
     */
    protected function determinarSustentos($entrada, $salida, $taller, $puntaje_total)
    {
        $sustenance_codes = array();

        // Verificar si todos los exámenes están en 0 (no ingresó)
        if ($entrada == 0 && $salida == 0 && $taller == 0) {
            $sustenance_codes[] = 4; // No ingreso al curso
            return $sustenance_codes;
        }

        // Verificar examen de entrada
        if ($entrada == 0) {
            $sustenance_codes[] = 1; // Falta examen entrada
        }

        // Verificar examen de salida
        if ($salida == 0) {
            $sustenance_codes[] = 2; // Falta examen salida
        }

        // Verificar taller
        if ($taller == 0) {
            $sustenance_codes[] = 3; // Falta taller
        }

        // Verificar puntaje mínimo (solo si no tiene faltas de exámenes)
        if (empty($sustenance_codes) && $puntaje_total <= $this->nota_minima) {
            $sustenance_codes[] = 5; // No alcanzo nota mínima
        }

        // Si no hay sustentos, significa que todo está bien
        if (empty($sustenance_codes)) {
            $sustenance_codes[] = 0; // Sin observaciones
        }

        return $sustenance_codes;
    }

    /**
     * Generar mensaje descriptivo
     *
     * @param array $sustenance_codes Códigos de sustento
     * @param float $puntaje_total Puntaje ponderado
     * @return string Mensaje
     */
    protected function generarMensaje($sustenance_codes, $puntaje_total)
    {
        $mensajes = array();

        foreach ($sustenance_codes as $code) {
            if (isset(self::$options[$code])) {
                $mensajes[] = self::$options[$code];
            }
        }

        $mensaje = 'Sustentos: ' . implode(', ', $mensajes);
        $mensaje .= ' | Puntaje ponderado: ' . $puntaje_total . '%';

        if ($puntaje_total <= $this->nota_minima && !in_array(4, $sustenance_codes)) {
            $mensaje .= ' (Por debajo del mínimo: ' . $this->nota_minima . '%)';
        }

        return $mensaje;
    }

    /**
     * Configurar nota mínima personalizada
     *
     * @param float $nota_minima Nota mínima (porcentaje)
     */
    public function setNotaMinima($nota_minima)
    {
        $this->nota_minima = floatval($nota_minima);
    }

    /**
     * Configurar ponderación personalizada
     *
     * @param array $ponderacion Array con keys: 'examen_de_entrada', 'examen_de_salida', 'taller'
     */
    public function setPonderacion($ponderacion)
    {
        if (is_array($ponderacion)) {
            $this->ponderacion = array_merge($this->ponderacion, $ponderacion);
        }
    }

    /**
     * Obtener puntaje ponderado de un usuario
     *
     * @param array $notas Array con las notas
     * @return float Puntaje ponderado
     */
    public function getPuntajePonderado($notas)
    {
        $entrada = floatval($notas['examen_de_entrada'] ?? 0);
        $salida = floatval($notas['examen_de_salida'] ?? 0);
        $taller = floatval($notas['taller'] ?? 0);

        return $this->calcularPuntajePonderado($entrada, $salida, $taller);
    }
}
