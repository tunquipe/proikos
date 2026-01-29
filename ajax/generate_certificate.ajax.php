<?php
/**
 * Endpoint AJAX para generar certificados
 * Archivo: plugin/proikos/ajax/generate_certificate.ajax.php
 */

require_once __DIR__ . '/../config.php';

// Verificar que sea una petición AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    exit(json_encode(['success' => false, 'message' => 'Invalid request']));
}

// Verificar que el usuario esté autenticado
api_block_anonymous_users();

// Cargar el plugin
$plugin = ProikosPlugin::create();

// Verificar que el plugin esté habilitado
if ($plugin->get('tool_enable') !== 'true') {
    exit(json_encode([
        'success' => false,
        'message' => 'Plugin no habilitado'
    ]));
}

// ==============================================================
// OBTENER PARÁMETROS
// ==============================================================

$action = isset($_POST['action']) ? $_POST['action'] : '';
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$course_code = isset($_POST['course_code']) ? $_POST['course_code'] : '';
$session_id = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;

// Validar parámetros
if (empty($action) || empty($user_id) || empty($course_code)) {
    exit(json_encode([
        'success' => false,
        'message' => 'Parámetros incompletos'
    ]));
}

// Verificar que el usuario solo pueda generar su propio certificado
$current_user_id = api_get_user_id();
if ($user_id != $current_user_id && !api_is_platform_admin()) {
    exit(json_encode([
        'success' => false,
        'message' => 'No tienes permisos para generar este certificado'
    ]));
}

// ==============================================================
// PROCESAR ACCIONES
// ==============================================================

switch ($action) {

    case 'check_status':
        // Verificar el estado del certificado sin generarlo
        $status = checkCertificateStatus($plugin, $user_id, $course_code, $session_id);
        exit(json_encode($status));
        break;

    case 'generate':
        // Generar el certificado
        $result = generateCertificate($plugin, $user_id, $course_code, $session_id);
        exit(json_encode($result));
        break;

    default:
        exit(json_encode([
            'success' => false,
            'message' => 'Acción no válida'
        ]));
}

// ==============================================================
// FUNCIONES AUXILIARES
// ==============================================================

/**
 * Verifica el estado del certificado sin generarlo
 */
function checkCertificateStatus($plugin, $user_id, $course_code, $session_id)
{
    $courseInfo = api_get_course_info($course_code);
    $course_id = $courseInfo['real_id'];

    // Obtener categoría del gradebook
    $category = Category::load(null, null, $course_code, null, null, $session_id, false);

    if (empty($category) || !isset($category[0])) {
        return [
            'success' => false,
            'can_generate' => false,
            'exists' => false,
            'message' => 'No se encontró la categoría del curso'
        ];
    }

    $category_id = $category[0]->get_id();

    // Verificar si ya existe el certificado
    $existing_certificate = GradebookUtils::get_certificate_by_user_id($category_id, $user_id);

    if (!empty($existing_certificate)) {
        // El certificado ya existe
        $certificate_url = api_get_path(WEB_PATH) . 'certificates/index.php?id=' .
            $existing_certificate['id'] . '&user_id=' . $user_id;

        return [
            'success' => true,
            'exists' => true,
            'can_generate' => false,
            'certificate_url' => $certificate_url,
            'certificate_id' => $existing_certificate['id'],
            'message' => 'El certificado ya está generado'
        ];
    }

    // Verificar si puede generar el certificado
    $userScore = $plugin->getResultExerciseStudent($user_id, $course_id, $session_id, false, true);

    $examen_de_entrada = isset($userScore['examen_de_entrada']) && $userScore['examen_de_entrada'] !== ''
        ? floatval($userScore['examen_de_entrada'])
        : -1;

    $taller = isset($userScore['taller']) && $userScore['taller'] !== ''
        ? floatval($userScore['taller'])
        : -1;

    $examen_de_salida = isset($userScore['examen_de_salida']) && $userScore['examen_de_salida'] !== ''
        ? floatval($userScore['examen_de_salida'])
        : -1;

    // Verificar si hay pendientes
    $has_pending = ($examen_de_entrada == -1 || $taller == -1 || $examen_de_salida == -1);

    if ($has_pending) {
        return [
            'success' => false,
            'exists' => false,
            'can_generate' => false,
            'message' => 'Aún tienes exámenes pendientes por completar'
        ];
    }

    // Calcular puntaje
    $ponderacion_entrada = 0.10;
    $ponderacion_taller = 0.60;
    $ponderacion_salida = 0.30;

    $totalScore = (($examen_de_entrada * $ponderacion_entrada) +
            ($examen_de_salida * $ponderacion_salida) +
            ($taller * $ponderacion_taller)) / 20 * 100;

    if ($totalScore < 70) {
        return [
            'success' => false,
            'exists' => false,
            'can_generate' => false,
            'message' => 'No alcanzaste el puntaje mínimo para aprobar (70%)',
            'score' => round($totalScore, 2)
        ];
    }

    // Puede generar el certificado
    return [
        'success' => true,
        'exists' => false,
        'can_generate' => true,
        'message' => 'Puedes generar tu certificado',
        'score' => round($totalScore, 2)
    ];
}

/**
 * Genera el certificado
 */
function generateCertificate($plugin, $user_id, $course_code, $session_id)
{
    $courseInfo = api_get_course_info($course_code);
    $course_id = $courseInfo['real_id'];

    // Obtener categoría del gradebook
    $category = Category::load(null, null, $course_code, null, null, $session_id, false);

    if (empty($category) || !isset($category[0])) {
        return [
            'success' => false,
            'message' => 'No se encontró la categoría del curso'
        ];
    }

    $category_id = $category[0]->get_id();

    // Intentar generar el certificado usando la función del plugin
    $result = $plugin->generateStudentCertificate($user_id, $course_id, $session_id, $category_id);

    if (!$result['success']) {
        return [
            'success' => false,
            'message' => $result['message']
        ];
    }

    // Éxito en la generación
    return [
        'success' => true,
        'message' => $result['already_exists']
            ? 'El certificado ya estaba generado'
            : '¡Certificado generado exitosamente!',
        'certificate_url' => $result['certificate_url'],
        'certificate_id' => $result['certificate_id'],
        'already_exists' => $result['already_exists'],
        'expedition_date' => $result['expedition_date'],
        'expiration_date' => $result['expiration_date']
    ];
}
