<?php

require_once __DIR__.'/config.php';
$plugin = ProikosPlugin::create();
$enable = $plugin->get('tool_enable') == 'true';
$nameTools = $plugin->get_lang('AcademicResults');
$htmlHeadXtra[] = api_get_css(api_get_path(WEB_PLUGIN_PATH) . 'proikos/css/results.css');

api_block_anonymous_users();

$userID = api_get_user_id();
$courseCode = $_GET['cidReq'] ?? null;
$courseID = api_get_course_int_id($courseCode);
$sessionID = $_GET['id_session'] ?? null;

$userScore = $plugin->getResultExerciseStudent($userID, $courseID, $sessionID, false, true);
$urlCertificate = $plugin->getUrlCertificate($userID, $sessionID);

// ==============================================================
// OBTENER VALORES RAW (pueden ser -1, 0, o la nota)
// ==============================================================
// -1 = Examen no resuelto (pendiente)
// 0 = Examen resuelto pero sacó 0 (falló todas)
// >0 = Nota obtenida

$examen_de_entrada_raw = isset($userScore['examen_de_entrada']) && $userScore['examen_de_entrada'] !== ''
    ? floatval($userScore['examen_de_entrada'])
    : -1;

$examen_de_salida_raw = isset($userScore['examen_de_salida']) && $userScore['examen_de_salida'] !== ''
    ? floatval($userScore['examen_de_salida'])
    : -1;

$taller_raw = isset($userScore['taller']) && $userScore['taller'] !== ''
    ? floatval($userScore['taller'])
    : -1;

// ==============================================================
// DEFINIR PONDERACIONES
// ==============================================================
$ponderacion_entrada = 0.10;  // 10%
$ponderacion_salida = 0.30;   // 30%
$ponderacion_taller = 0.60;   // 60%

$min_passing_score = 70; // Puntaje mínimo para aprobar (sobre 100)

// ==============================================================
// PREPARAR ARRAYS
// ==============================================================

// Array para mostrar en la vista (mantiene -1 para identificar pendientes)
$exams_display = [
    'entrance' => $examen_de_entrada_raw,
    'workshop' => $taller_raw,
    'exit' => $examen_de_salida_raw
];

// Array para cálculos (convierte -1 a null para excluir del cálculo)
$exams_calc = [
    'entrance' => ($examen_de_entrada_raw == -1) ? null : $examen_de_entrada_raw,
    'workshop' => ($taller_raw == -1) ? null : $taller_raw,
    'exit' => ($examen_de_salida_raw == -1) ? null : $examen_de_salida_raw
];

// Ponderaciones en array
$weights = [
    'entrance' => $ponderacion_entrada,
    'workshop' => $ponderacion_taller,
    'exit' => $ponderacion_salida
];

// ==============================================================
// VERIFICAR EXÁMENES PENDIENTES
// ==============================================================

$has_pending = false;
$pending_count = 0;
$completed_exams = [];
$total_weight_completed = 0;

foreach ($exams_calc as $key => $value) {
    if ($value === null) {
        $has_pending = true;
        $pending_count++;
    } else {
        $completed_exams[$key] = $value;
        $total_weight_completed += $weights[$key];
    }
}

// ==============================================================
// CALCULAR PROMEDIO PONDERADO Y PUNTAJE TOTAL
// ==============================================================

if ($has_pending) {
    // ====== CASO 1: HAY EXÁMENES PENDIENTES ======

    if (count($completed_exams) > 0) {
        // Hay algunos exámenes completados
        $weighted_sum = 0;

        foreach ($completed_exams as $key => $score) {
            $weighted_sum += $score * $weights[$key];
        }

        // Promedio ponderado sobre 20 (solo de los completados)
        $weightedAverage = $weighted_sum / $total_weight_completed;

        // Puntaje sobre 100 (solo de los completados)
        $totalScore = ($weighted_sum / $total_weight_completed) / 20 * 100;

        // Porcentaje de avance del curso
        $course_completion = ($total_weight_completed * 100);

    } else {
        // No hay ningún examen completado
        $weightedAverage = 0;
        $totalScore = 0;
        $course_completion = 0;
    }

    $status = 'pending';

} else {
    // ====== CASO 2: TODOS LOS EXÁMENES COMPLETADOS ======

    // Promedio ponderado sobre 20
    $weightedAverage = ($exams_calc['entrance'] * $ponderacion_entrada) +
        ($exams_calc['workshop'] * $ponderacion_taller) +
        ($exams_calc['exit'] * $ponderacion_salida);

    // Puntaje total sobre 100
    $totalScore = (($exams_calc['entrance'] * $ponderacion_entrada) +
            ($exams_calc['exit'] * $ponderacion_salida) +
            ($exams_calc['workshop'] * $ponderacion_taller)) / 20 * 100;

    $course_completion = 100;

    // Determinar si aprobó o desaprobó
    $status = ($totalScore >= $min_passing_score) ? 'approved' : 'failed';
}

// ==============================================================
// CONFIGURAR APARIENCIA SEGÚN ESTADO
// ==============================================================

if ($status == 'pending') {
    $css_status = 'pending';
    $css_status_light = 'highlight-pending';
    $image_icon = api_get_path(WEB_PLUGIN_PATH) . 'proikos/images/hourglass.png';
    $status_id = 1;
    $status_icon = '⏳';
    $status_title = 'EVALUACIONES PENDIENTES';
    $status_subtitle = 'Aún tienes ' . $pending_count . ' evaluación(es) por completar';

} elseif ($status == 'approved') {
    $css_status = 'approved';
    $css_status_light = 'highlight-approved';
    $image_icon = api_get_path(WEB_PLUGIN_PATH) . 'proikos/images/winking-face.png';
    $status_id = 2;
    $status_icon = '😊';
    $status_title = '¡APROBADO!';
    $status_subtitle = 'Puntaje Alcanzado con Éxito';

} else { // failed
    $css_status = 'failed';
    $css_status_light = 'highlight-failed';
    $image_icon = api_get_path(WEB_PLUGIN_PATH) . 'proikos/images/sad-face.png';
    $status_id = 0;
    $status_icon = '😔';
    $status_title = 'DESAPROBADO';
    $status_subtitle = 'No se alcanzó el puntaje mínimo requerido';
}

// ==============================================================
// PREPARAR DATOS PARA LA VISTA
// ==============================================================

// Formatear exams para el template
$exams = [
    'entrance' => $examen_de_entrada_raw,
    'workshop' => $taller_raw,
    'exit' => $examen_de_salida_raw
];

// Información adicional para la vista
$exam_info = [
    'entrance' => [
        'name' => 'Examen de Entrada',
        'weight' => '10%',
        'score' => $examen_de_entrada_raw,
        'is_pending' => ($examen_de_entrada_raw == -1),
        'is_passed' => ($examen_de_entrada_raw >= 14 && $examen_de_entrada_raw != -1)
    ],
    'workshop' => [
        'name' => 'Taller',
        'weight' => '60%',
        'score' => $taller_raw,
        'is_pending' => ($taller_raw == -1),
        'is_passed' => ($taller_raw >= 14 && $taller_raw != -1)
    ],
    'exit' => [
        'name' => 'Examen de Salida',
        'weight' => '30%',
        'score' => $examen_de_salida_raw,
        'is_pending' => ($examen_de_salida_raw == -1),
        'is_passed' => ($examen_de_salida_raw >= 14 && $examen_de_salida_raw != -1)
    ]
];

// ==============================================================
// ASIGNAR VARIABLES AL TEMPLATE
// ==============================================================

$tpl = new Template($nameTools, true, true, false, false, true, false);

// Variables existentes
$tpl->assign('exams', $exams);
$tpl->assign('weighted_average', round($weightedAverage, 2));
$tpl->assign('total_score', round($totalScore, 2));
$tpl->assign('image_icon', $image_icon);
$tpl->assign('css_status', $css_status);
$tpl->assign('status_id', $status_id);
$tpl->assign('css_status_light', $css_status_light);
$tpl->assign('url_certificate', $urlCertificate);

// Nuevas variables para mejor control
$tpl->assign('status', $status);
$tpl->assign('status_icon', $status_icon);
$tpl->assign('status_title', $status_title);
$tpl->assign('status_subtitle', $status_subtitle);
$tpl->assign('has_pending', $has_pending);
$tpl->assign('pending_count', $pending_count);
$tpl->assign('course_completion', round($course_completion, 1));
$tpl->assign('exam_info', $exam_info);
$tpl->assign('min_passing_score', $min_passing_score);

$content = $tpl->fetch('proikos/view/proikos_results.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
