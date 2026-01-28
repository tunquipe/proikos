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

$userScore = $plugin->getResultExerciseStudent($userID, $courseID, $sessionID);
$urlCertificate = $plugin->getUrlCertificate($userID, $sessionID);

$examen_de_entrada = isset($userScore['examen_de_entrada']) && $userScore['examen_de_entrada'] !== '' ? $userScore['examen_de_entrada'] : 0;
$examen_de_salida = isset($userScore['examen_de_salida']) && $userScore['examen_de_salida'] !== '' ? $userScore['examen_de_salida'] : 0;
$taller = isset($userScore['taller']) && $userScore['taller'] !== '' ? $userScore['taller'] : 0;

// Definir los porcentajes para cada examen
$ponderacion_entrada = 0.10;  // 10%
$ponderacion_salida = 0.30;   // 30%
$ponderacion_taller = 0.60;   // 60%

$exams = [
    'entrance' => floatval($examen_de_entrada),
    'workshop' => floatval($taller),
    'exit' => floatval($examen_de_salida)
];

$weightedAverage = ($exams['entrance'] * $ponderacion_entrada) + ($exams['workshop'] * $ponderacion_taller) + ($exams['exit'] * $ponderacion_salida);

$totalScore = (($examen_de_entrada * $ponderacion_entrada) +
        ($examen_de_salida * $ponderacion_salida) +
        ($taller * $ponderacion_taller)) / 20 * 100;

if ($totalScore == 0) {
    $css_status = 'pending';
    $css_status_light = 'highlight-pending';
    $status_id = 1;
} else if ($examen_de_entrada == 0 || $examen_de_salida == 0 || $taller == 0) {
    $css_status = 'failed';
    $css_status_light = 'highlight-failed';
    $status_id = 0;
} else if ($totalScore >= 70) {
    $css_status = 'approved';
    $css_status_light = 'highlight-approved';
    $status_id = 2;
} else {
    $css_status = 'failed';
    $css_status_light = 'highlight-failed';
    $status_id = 0;
}

$tpl = new Template($nameTools, true, true, false, false, true, false);
$tpl->assign('exams', $exams);
$tpl->assign('weighted_average', $weightedAverage);
$tpl->assign('total_score', $totalScore);
$tpl->assign('css_status', $css_status);
$tpl->assign('status_id', $status_id);
$tpl->assign('css_status_light', $css_status_light);
$tpl->assign('url_certificate', $urlCertificate);
$content = $tpl->fetch('proikos/view/proikos_results.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
