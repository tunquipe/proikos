<?php

namespace src;

/**
 * Ubigeo de Perú (INEI 2016) leído de plugin/proikos/resources/ubigeo/*.json.
 * Las listas de los formularios de inscripción con config.options_source toman
 * de aquí sus opciones (no se guardan en la base): departamentos, provincias
 * agrupadas por departamento y distritos agrupados por provincia.
 */
class UbigeoPeru
{
    /** Orígenes de opciones: clave => etiqueta. */
    const SOURCES = [
        'ubigeo_departamentos' => 'Ubigeo Perú: regiones / departamentos',
        'ubigeo_provincias' => 'Ubigeo Perú: provincias (según la región elegida)',
        'ubigeo_distritos' => 'Ubigeo Perú: distritos (según la provincia elegida)',
    ];

    /** @var array|null */
    private static $levels = null;

    /**
     * Todas las opciones de un origen, sin agrupar y sin repetir.
     *
     * @return string[]
     */
    public static function flatOptions(string $source): array
    {
        $levels = self::levels();
        switch ($source) {
            case 'ubigeo_departamentos':
                return $levels['departamentos'];
            case 'ubigeo_provincias':
            case 'ubigeo_distritos':
                $key = substr($source, strlen('ubigeo_'));

                return array_values(array_unique(array_merge(...array_values($levels[$key] ?: [[]]))));
            default:
                return [];
        }
    }

    /**
     * Opciones agrupadas por la opción del padre ({"Lima": ["Barranca", …]}),
     * o null si el origen no se agrupa (departamentos).
     */
    public static function groups(string $source)
    {
        $key = substr($source, strlen('ubigeo_'));

        return in_array($key, ['provincias', 'distritos'], true) ? self::levels()[$key] : null;
    }

    private static function levels(): array
    {
        if (null !== self::$levels) {
            return self::$levels;
        }

        $read = function (string $file): array {
            $json = @file_get_contents(__DIR__.'/../resources/ubigeo/ubigeo_peru_2016_'.$file.'.json');

            return is_string($json) ? (json_decode($json, true) ?: []) : [];
        };

        // Algunos nombres de la fuente traen espacios al final («Ica »).
        $levels = ['departamentos' => [], 'provincias' => [], 'distritos' => []];
        $departments = $provinces = [];
        foreach ($read('region') as $d) {
            $departments[$d['id']] = trim($d['name']);
            $levels['departamentos'][] = trim($d['name']);
        }
        foreach ($read('provincias') as $p) {
            $provinces[$p['id']] = trim($p['name']);
            if (isset($departments[$p['department_id']])) {
                $levels['provincias'][$departments[$p['department_id']]][] = trim($p['name']);
            }
        }
        foreach ($read('distritos') as $d) {
            if (isset($provinces[$d['province_id']])) {
                $levels['distritos'][$provinces[$d['province_id']]][] = trim($d['name']);
            }
        }

        // Orden alfabético sin que las tildes manden al final («Áncash» junto a «Amazonas»).
        $sortKey = function (string $s) {
            return strtr(mb_strtolower($s), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n~']);
        };
        $sort = function (array $names) use ($sortKey) {
            $names = array_values(array_unique($names));
            usort($names, function ($a, $b) use ($sortKey) {
                return strcmp($sortKey($a), $sortKey($b));
            });

            return $names;
        };
        $levels['departamentos'] = $sort($levels['departamentos']);
        foreach (['provincias', 'distritos'] as $level) {
            foreach ($levels[$level] as $parent => $names) {
                $levels[$level][$parent] = $sort($names);
            }
        }

        return self::$levels = $levels;
    }
}
