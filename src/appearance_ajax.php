<?php

require_once __DIR__.'/../config.php';
require_once __DIR__.'/AppearanceManager.php';

use src\AppearanceManager;

header('Content-Type: application/json; charset=utf-8');

$respond = function (array $data, int $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

if (!api_is_platform_admin()) {
    $respond(['error' => get_lang('NotAllowed')], 403);
}

AppearanceManager::ensureTable();
$action = $_REQUEST['a'] ?? '';

// Todo lo que no sea lectura exige POST con token.
if (!in_array($action, ['get', 'export'], true)) {
    if ('POST' !== $_SERVER['REQUEST_METHOD'] || !Security::check_token('post', null, 'pk_appearance')) {
        $respond(['error' => 'La sesión expiró. Recargue la página.'], 403);
    }
}

$readSettings = function () use ($respond) {
    $settings = json_decode($_POST['settings'] ?? '', true);
    if (!is_array($settings)) {
        $respond(['error' => 'Datos no válidos.'], 400);
    }

    return $settings;
};

switch ($action) {
    case 'get':
        $respond([
            'schema' => AppearanceManager::schema(),
            // Objeto JSON aunque esté vacío ({} y no []), el JS asigna propiedades.
            'settings' => (object) AppearanceManager::getSettings(),
        ]);
        break;

    case 'preview':
        $settings = AppearanceManager::sanitize($readSettings());
        $respond([
            'css' => AppearanceManager::buildCss($settings),
            'vars' => (object) AppearanceManager::templateVars($settings),
        ]);
        break;

    case 'save':
        $settings = AppearanceManager::save($readSettings());
        $respond(['ok' => true, 'settings' => (object) $settings, 'message' => 'Cambios publicados.']);
        break;

    case 'reset':
        AppearanceManager::save([]);
        $respond(['ok' => true, 'settings' => new stdClass(), 'message' => 'Se restauró la apariencia original del tema.']);
        break;

    case 'upload':
        $result = AppearanceManager::uploadImage($_FILES['image'] ?? []);
        $respond($result, isset($result['error']) ? 400 : 200);
        break;

    case 'export':
        $path = AppearanceManager::exportPackage();
        $host = preg_replace('/[^a-z0-9.-]/i', '', (string) parse_url(api_get_path(WEB_PATH), PHP_URL_HOST));
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="apariencia-'.$host.'-'.date('Ymd-His').'.zip"');
        header('Content-Length: '.filesize($path));
        readfile($path);
        unlink($path);
        exit;

    case 'import':
        $result = AppearanceManager::importPackage($_FILES['package'] ?? []);
        if (isset($result['error'])) {
            $respond($result, 400);
        }
        $message = 'Configuración importada como borrador. Revísela y pulse «Guardar y publicar».';
        if ($result['ignored'] > 0) {
            $message .= ' Se omitieron '.$result['ignored'].' opciones que no existen o no son válidas en esta versión.';
        }
        $respond([
            'ok' => true,
            'settings' => (object) $result['settings'],
            'errors' => $result['errors'],
            'message' => $message,
        ]);
        break;

    default:
        $respond(['error' => 'Acción no válida.'], 400);
}
