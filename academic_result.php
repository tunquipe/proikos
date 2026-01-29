<?php

require_once __DIR__.'/config.php';
$plugin = ProikosPlugin::create();
$enable = $plugin->get('tool_enable') == 'true';
$nameTools = $plugin->get_lang('AcademicResults');

// Solo cargar CSS (el JS está en el template)
$htmlHeadXtra[] = api_get_css(api_get_path(WEB_PLUGIN_PATH) . 'proikos/css/results.css');

api_block_anonymous_users();

$userID = api_get_user_id();
$courseCode = $_GET['cidReq'] ?? api_get_course_id();  // Fallback a api_get_course_id()
$courseID = api_get_course_int_id($courseCode);
$sessionID = isset($_GET['id_session']) ? (int)$_GET['id_session'] : 0;

$userScore = $plugin->getResultExerciseStudent($userID, $courseID, $sessionID, false, true);

// ==============================================================
// OBTENER VALORES RAW DE LOS EXÁMENES
// ==============================================================

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
// CONFIGURACIÓN DE PONDERACIONES
// ==============================================================

$ponderacion_entrada = 0.10;  // 10%
$ponderacion_salida = 0.30;   // 30%
$ponderacion_taller = 0.60;   // 60%

$min_passing_score = 70; // Puntaje mínimo para aprobar (sobre 100)

// ==============================================================
// PREPARAR ARRAYS
// ==============================================================

$exams_display = [
    'entrance' => $examen_de_entrada_raw,
    'workshop' => $taller_raw,
    'exit' => $examen_de_salida_raw
];

$exams_calc = [
    'entrance' => ($examen_de_entrada_raw == -1) ? null : $examen_de_entrada_raw,
    'workshop' => ($taller_raw == -1) ? null : $taller_raw,
    'exit' => ($examen_de_salida_raw == -1) ? null : $examen_de_salida_raw
];

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
$pending_exam_names = [];

foreach ($exams_calc as $key => $value) {
    if ($value === null) {
        $has_pending = true;
        $pending_count++;
        if ($key == 'entrance') $pending_exam_names[] = 'Examen de Entrada';
        if ($key == 'workshop') $pending_exam_names[] = 'Taller';
        if ($key == 'exit') $pending_exam_names[] = 'Examen de Salida';
    } else {
        $completed_exams[$key] = $value;
        $total_weight_completed += $weights[$key];
    }
}

// ==============================================================
// CALCULAR PROMEDIO Y PUNTAJE
// ==============================================================

if ($has_pending) {
    // HAY EXÁMENES PENDIENTES
    if (count($completed_exams) > 0) {
        $weighted_sum = 0;
        foreach ($completed_exams as $key => $score) {
            $weighted_sum += $score * $weights[$key];
        }
        $weightedAverage = $weighted_sum / $total_weight_completed;
        $totalScore = ($weighted_sum / $total_weight_completed) / 20 * 100;
        $course_completion = ($total_weight_completed * 100);
    } else {
        $weightedAverage = 0;
        $totalScore = 0;
        $course_completion = 0;
    }
    $status = 'pending';

} else {
    // TODOS LOS EXÁMENES COMPLETADOS
    $weightedAverage = ($exams_calc['entrance'] * $ponderacion_entrada) +
        ($exams_calc['workshop'] * $ponderacion_taller) +
        ($exams_calc['exit'] * $ponderacion_salida);

    $totalScore = (($exams_calc['entrance'] * $ponderacion_entrada) +
            ($exams_calc['exit'] * $ponderacion_salida) +
            ($exams_calc['workshop'] * $ponderacion_taller)) / 20 * 100;

    $course_completion = 100;
    $status = ($totalScore >= $min_passing_score) ? 'approved' : 'failed';
}

// ==============================================================
// VERIFICAR SI YA EXISTE EL CERTIFICADO (sin generar)
// ==============================================================

$certificate_url = null;
$certificate_exists = false;

if ($status == 'approved' && !empty($courseCode)) {
    // Solo verificar si existe, NO generar automáticamente
    $certificate_url = $plugin->getUrlCertificate($userID, $sessionID, $courseCode);
    $certificate_exists = !empty($certificate_url);
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

} else {
    $css_status = 'failed';
    $css_status_light = 'highlight-failed';
    $image_icon = api_get_path(WEB_PLUGIN_PATH) . 'proikos/images/sad-face.png';
    $status_id = 0;
    $status_icon = '😔';
    $status_title = 'DESAPROBADO';
    $status_subtitle = 'No se alcanzó el puntaje mínimo requerido';
}

// ==============================================================
// PREPARAR INFORMACIÓN DE EXÁMENES PARA LA VISTA
// ==============================================================

$exams = [
    'entrance' => $examen_de_entrada_raw,
    'workshop' => $taller_raw,
    'exit' => $examen_de_salida_raw
];

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

// Variables de puntajes
$tpl->assign('exams', $exams);
$tpl->assign('weighted_average', round($weightedAverage, 2));
$tpl->assign('total_score', round($totalScore, 2));

// Variables de estado
$tpl->assign('image_icon', $image_icon);
$tpl->assign('css_status', $css_status);
$tpl->assign('status_id', $status_id);
$tpl->assign('css_status_light', $css_status_light);
$tpl->assign('status', $status);
$tpl->assign('status_icon', $status_icon);
$tpl->assign('status_title', $status_title);
$tpl->assign('status_subtitle', $status_subtitle);

// Variables de progreso
$tpl->assign('has_pending', $has_pending);
$tpl->assign('pending_count', $pending_count);
$tpl->assign('pending_exam_names', $pending_exam_names);
$tpl->assign('course_completion', round($course_completion, 1));

// Información de exámenes
$tpl->assign('exam_info', $exam_info);
$tpl->assign('min_passing_score', $min_passing_score);

// Certificado - solo la URL si ya existe
$tpl->assign('url_certificate', $certificate_url);
$tpl->assign('certificate_exists', $certificate_exists);

// ====================================================================
// IMPORTANTE: Variables necesarias para el botón de certificado AJAX
// ====================================================================
$tpl->assign('user_id', $userID);
$tpl->assign('course_code', $courseCode);
$tpl->assign('session_id', $sessionID);

// Debug (remover en producción)
if (api_is_platform_admin()) {
    error_log("PROIKOS DEBUG - User: $userID, Course: $courseCode, Session: $sessionID, Status: $status");
}

$content = $tpl->fetch('proikos/view/proikos_results.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
