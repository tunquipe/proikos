<?php

$cidReset = true;

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/cache_manager.php'; // Incluir el gestor de caché

api_block_anonymous_users();

if (!api_is_platform_admin() && !api_is_drh() && !api_is_contractor_admin()) {
    api_not_allowed(true);
}

$plugin = ProikosPlugin::create();

$cacheLifetimeAdmin = $plugin->get('cache_data_admin');
if (empty($cacheLifetimeAdmin) || !is_numeric($cacheLifetimeAdmin)) {
    $cacheLifetimeAdmin = 7200; // Valor por defecto: 2 hora
}
$cacheLifetimeManager = $plugin->get('cache_data_manager');
if (empty($cacheLifetimeManager) || !is_numeric($cacheLifetimeManager)) {
    $cacheLifetimeManager = 3600; // Valor por defecto: 1 hora
}

// Inicializar gestor de caché
$cacheManager = new ProikosCacheManager();

if (api_is_platform_admin()) {
    // Admins: 2 horas (consultan mucho, datos cambian poco)
    $cacheManager->setCacheLifetime($cacheLifetimeAdmin);
} else if (api_is_contractor_admin()) {
    $cacheManager->setCacheLifetime($cacheLifetimeManager);
} else {
    $cacheManager->setCacheLifetime($cacheLifetimeManager);
}

//$cacheManager->setCacheLifetime(3600); // 1 hora

// Verificar si el caché está habilitado
if (!$cacheManager->isEnabled()) {
    $message = Display::return_message(
        'El sistema de caché no está disponible. Los reportes pueden ser más lentos. ' .
        'Por favor, verifica que el directorio /plugin/proikos/cache/ exista y tenga permisos de escritura (777).',
        'warning'
    );
}

// Agregar Vue.js
$htmlHeadXtra[] = '<script src="https://cdn.jsdelivr.net/npm/vue@2.7.14/dist/vue.min.js"></script>';


$tool_name = 'Data';
$actionLinks = null;
$message = null;

$action = $_GET['action'] ?? null;
$dni = $_GET['keyword'] ?? null;
$courseId = $_GET['course_id'] ?? '%';
$sessionId = $_GET['session_id'] ?? '%';
$ruc = $_GET['ruc'] ?? '0';
$dateFrom = $_GET['date_from'] ?? '';  // formato YYYY-MM-DD (primer día del mes)
$dateTo   = $_GET['date_to']   ?? '';  // formato YYYY-MM-DD (último día del mes)

// Validar que el rango no supere 3 meses
if (!empty($dateFrom) && !empty($dateTo)) {
    $dfObj = new DateTime($dateFrom);
    $dtObj = new DateTime($dateTo);
    $diff  = $dfObj->diff($dtObj);
    $monthsDiff = $diff->y * 12 + $diff->m + ($diff->d > 0 ? 1 : 0);
    if ($monthsDiff > 3) {
        $dateTo = (clone $dfObj)->modify('+2 months')->modify('last day of this month')->format('Y-m-d');
    }
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 25;


if (isset($action)) {
    switch ($action) {
        case 'clear_user_cache':
            // Limpiar solo el caché del usuario actual
            $deleted = $cacheManager->clearUserCache();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "Se eliminaron $deleted archivos de caché de su sesión",
                'deleted' => $deleted
            ]);
            exit;
        case 'clear_cache':
            // Solo administradores pueden limpiar el caché
            if (api_is_platform_admin()) {
                $deleted = $cacheManager->clear();
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => "Se eliminaron $deleted archivos de caché",
                    'deleted' => $deleted
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'No tiene permisos para realizar esta acción'
                ]);
            }
            exit;

        case 'cache_info':
            // Información del caché
            if (api_is_platform_admin()) {
                $info = $cacheManager->getInfo();
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'data' => $info
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'No tiene permisos'
                ]);
            }
            exit;

        case 'clear_expired_cache':
            // Limpiar solo caché expirado
            if (api_is_platform_admin()) {
                $deleted = $cacheManager->clearExpired();
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => "Se eliminaron $deleted archivos de caché expirados",
                    'deleted' => $deleted
                ]);
            }
            exit;

        case 'cron':
            $rawData = $plugin->getDataReport($dni, $courseId, $sessionId, $ruc, 1, 10, true, 'ASC');
            $count = 0;

            foreach ($rawData['users'] as $row) {
                if ($row['status_id'] != 1) {
                    $count++;
                    $plugin->registerData($row);
                }
            }

            // Limpiar caché después del cron porque los datos cambiaron
            $cacheManager->clear();

            echo 'se registraron ' . $count . ' registros y se limpió el caché';
            exit;

        case 'xls':
            $fileName = 'report_' . api_get_local_time();

            // Parámetros para caché de exportación
            $cacheParams = [
                'keyword' => $dni,
                'courseId' => $courseId,
                'sessionId' => $sessionId,
                'ruc' => $ruc,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'page' => 1,
                'perPage' => 9999,
                'export' => 'xls'
            ];

            // Intentar obtener del caché
            $cachedData = $cacheManager->get($cacheParams);

            if ($cachedData !== null && isset($cachedData['data'])) {
                $rawData = $cachedData['data'];
            } else {
                // No hay caché, consultar DB
                $rawData = $plugin->getDataReport($dni, $courseId, $sessionId, $ruc, 1, 9999, true, 'DESC', $dateFrom, $dateTo);
                $cacheManager->set($cacheParams, $rawData);
            }

            $headers = [
                'Nº',
                'Codigo',
                'Fecha',
                'Nº Horas',
                'Curso',
                'Sesión',
                'Apellidos y Nombres',
                'DNI / C.E',
                'RUC',
                'Nombre de Empresa',
                'Sede',
                'Examen de entrada - 10%',
                'Taller - 60%',
                'Examen de salida - 30%',
                'Puntaje',
                'Estado',
                'Observaciones certificado',
                'F. Emision Certificado',
                'F. Vencimiento Certificado',
                'Adjuntos por el estudiante',
                'Incidencias'
            ];
            $cleanData = [];

            foreach ($rawData['users'] as $row) {
                $sustenance = $plugin->getSustenanceByUserAndSession($row['id'], $row['session_id']);
                $cleanData[] = [
                    'id' => $row['id'],
                    'code_user' => 'PROK'.$row['id'],
                    'registration_date' => $row['registration_date_normal'],
                    'time_course' => $row['time_course'],
                    'session_category_name' => $row['session_category_name'],
                    'session_name' => $row['session_name'],
                    'student' => $row['student'],
                    'DNI' => $row['DNI'],
                    'ruc_company' => $row['ruc_company'],
                    'name_company' => $row['name_company'],
                    'area' => $row['area'],
                    'examen_de_entrada' => isset($row['exams']['examen_de_entrada']) ? $row['exams']['examen_de_entrada'] : 0,
                    'taller' => isset($row['exams']['taller']) ? $row['exams']['taller'] : 0,
                    'examen_de_salida' => isset($row['exams']['examen_de_salida']) ? $row['exams']['examen_de_salida'] : 0,
                    'score' => $row['score'],
                    'status' => strip_tags($row['status']),
                    'certificate_status' => $row['certificate_status'],
                    'created_at' => $row['certificate_date']['created_at'],
                    'expiration_date' => $row['certificate_date']['expiration_date'],
                    'metadata_exists' => $row['metadata_exists'],
                    'sustenance' => $sustenance,
                ];
            }
            array_unshift($cleanData, $headers);
            Export::arrayToXls($cleanData, $fileName);
            exit;

        case 'xls_delete_cache':
            header('Content-Type: application/json');
            $currentUserId = api_get_user_id();
            $today = date('Y-m-d');
            $exportParams = ['keyword' => $dni, 'courseId' => $courseId, 'sessionId' => $sessionId, 'ruc' => $ruc, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo];
            $paramsHash = md5(serialize($exportParams));
            $exportToken = 'export_' . $currentUserId . '_' . $today . '_' . $paramsHash;
            $exportDir = __DIR__ . '/../cache/exports/';
            $exportFile = $exportDir . $exportToken . '.xlsx';
            if (file_exists($exportFile)) {
                @unlink($exportFile);
            }
            echo json_encode(['success' => true]);
            exit;

        case 'xls_async':
            // Capturar cualquier output espurio (xdebug, notices) para que no rompa el JSON
            ob_start();
            @ini_set('display_errors', 0);
            @ini_set('memory_limit', '512M');
            @set_time_limit(600);

            $currentUserId = api_get_user_id();
            $today = date('Y-m-d');
            $exportParams = ['keyword' => $dni, 'courseId' => $courseId, 'sessionId' => $sessionId, 'ruc' => $ruc, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo];
            $paramsHash = md5(serialize($exportParams));
            $exportToken = 'export_' . $currentUserId . '_' . $today . '_' . $paramsHash;
            $exportDir = __DIR__ . '/../cache/exports/';
            $exportFile = $exportDir . $exportToken . '.xlsx';
            $lockFile = $exportFile . '.lock';

            if (!is_dir($exportDir)) {
                mkdir($exportDir, 0777, true);
                file_put_contents($exportDir . 'index.html', '');
            }

            // Limpiar archivos de exportación de días anteriores del usuario
            foreach (glob($exportDir . 'export_' . $currentUserId . '_*.xlsx') as $oldFile) {
                if (strpos(basename($oldFile), 'export_' . $currentUserId . '_' . $today . '_') === false) {
                    @unlink($oldFile);
                }
            }

            if (file_exists($exportFile)) {
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'cached' => true, 'token' => $exportToken]);
                exit;
            }

            // Si hay un lock activo, indicar que todavía está generando
            if (file_exists($lockFile) && (time() - filemtime($lockFile)) < 300) {
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'generating' => true, 'message' => 'El reporte se está generando, espere un momento.']);
                exit;
            }

            // Crear lock para evitar generaciones paralelas
            file_put_contents($lockFile, time());

            // Intentar obtener datos del caché JSON
            $cacheParams = ['keyword' => $dni, 'courseId' => $courseId, 'sessionId' => $sessionId, 'ruc' => $ruc, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'page' => 1, 'perPage' => 9999, 'export' => 'xls'];
            $cachedData = $cacheManager->get($cacheParams);
            if ($cachedData !== null && isset($cachedData['data'])) {
                $rawData = $cachedData['data'];
            } else {
                $rawData = $plugin->getDataReport($dni, $courseId, $sessionId, $ruc, 1, 9999, true, 'DESC', $dateFrom, $dateTo);
                $cacheManager->set($cacheParams, $rawData);
            }

            $xlsHeaders = ['Nº','Codigo','Fecha','Nº Horas','Curso','Sesión','Apellidos y Nombres','DNI / C.E','RUC','Nombre de Empresa','Sede','Examen de entrada - 10%','Taller - 60%','Examen de salida - 30%','Puntaje','Estado','Observaciones certificado','F. Emision Certificado','F. Vencimiento Certificado','Adjuntos por el estudiante','Incidencias'];
            $cleanData = [$xlsHeaders];

            foreach ($rawData['users'] as $row) {
                $sustenance = $plugin->getSustenanceByUserAndSession($row['id'], $row['session_id']);
                $cleanData[] = [
                    'id' => $row['id'],
                    'code_user' => 'PROK'.$row['id'],
                    'registration_date' => $row['registration_date_normal'],
                    'time_course' => $row['time_course'],
                    'session_category_name' => $row['session_category_name'],
                    'session_name' => $row['session_name'],
                    'student' => $row['student'],
                    'DNI' => $row['DNI'],
                    'ruc_company' => $row['ruc_company'],
                    'name_company' => $row['name_company'],
                    'area' => $row['area'],
                    'examen_de_entrada' => isset($row['exams']['examen_de_entrada']) ? $row['exams']['examen_de_entrada'] : 0,
                    'taller' => isset($row['exams']['taller']) ? $row['exams']['taller'] : 0,
                    'examen_de_salida' => isset($row['exams']['examen_de_salida']) ? $row['exams']['examen_de_salida'] : 0,
                    'score' => $row['score'],
                    'status' => strip_tags($row['status']),
                    'certificate_status' => $row['certificate_status'],
                    'created_at' => $row['certificate_date']['created_at'],
                    'expiration_date' => $row['certificate_date']['expiration_date'],
                    'metadata_exists' => $row['metadata_exists'],
                    'sustenance' => $sustenance,
                ];
            }

            $excel = @new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $rowNum = 1;
            foreach ($cleanData as $item) {
                $values = array_values($item);
                for ($i = 0; $i < count($values); $i++) {
                    @$excel->getActiveSheet()->setCellValueByColumnAndRow($i + 1, $rowNum, $values[$i]);
                }
                $rowNum++;
            }
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($excel, 'Xlsx');
            $writer->save($exportFile);
            @unlink($lockFile);

            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'cached' => false, 'token' => $exportToken]);
            exit;

        case 'xls_download':
            $token = $_GET['token'] ?? '';
            $currentUserId = api_get_user_id();
            $today = date('Y-m-d');

            if (!preg_match('/^export_[0-9]+_[0-9]{4}-[0-9]{2}-[0-9]{2}_[a-f0-9]{32}$/', $token)) {
                http_response_code(400);
                die('Token inválido');
            }
            // Verify token belongs to current user and today
            if (strpos($token, 'export_' . $currentUserId . '_' . $today . '_') !== 0) {
                http_response_code(403);
                die('Acceso no autorizado');
            }

            $exportDir = __DIR__ . '/../cache/exports/';
            $exportFile = $exportDir . $token . '.xlsx';

            if (!file_exists($exportFile)) {
                http_response_code(404);
                die('Archivo no encontrado. Por favor genere el reporte nuevamente.');
            }

            DocumentManager::file_send_for_download($exportFile, true, 'reporte_data_' . $today . '.xlsx');
            exit;

        default:
            break;
    }

    exit;
}

$tpl = new Template($tool_name);
$isAdmin = api_is_platform_admin();

$actionLinks .= Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_PLUGIN_PATH) . 'proikos/start.php'
);

// Botones de caché para administradores
if ($isAdmin) {
    $cacheInfo = $cacheManager->getInfo();

    // Botón para limpiar TODO el caché (solo admins)
    $actionLinks .= ' ' . Display::url(
            Display::return_icon('data-clear.png', 'Limpiar Todo el Caché', [], ICON_SIZE_MEDIUM),
            'javascript:void(0)',
            [
                'id' => 'btn-clear-cache',
                //'class' => 'btn btn-warning btn-sm',
                'title' => 'Limpiar todo el caché del sistema'
            ]
        );

    // Botón para limpiar solo caché de administradores
    $actionLinks .= ' ' . Display::url(
            Display::return_icon('user.png', 'Limpiar Caché de Admins', [], ICON_SIZE_MEDIUM),
            'javascript:void(0)',
            [
                'id' => 'btn-clear-user-cache',
                //'class' => 'btn btn-info btn-sm',
                'title' => 'Limpiar solo caché compartido de administradores'
            ]
        );

    $actionLinks .= ' ' . Display::url(
            Display::return_icon('broom.png', 'Limpiar Caché Expirado', [], ICON_SIZE_MEDIUM),
            'javascript:void(0)',
            [
                'id' => 'btn-clear-expired-cache',
                //'class' => 'btn btn-info btn-sm',
                'title' => 'Limpiar solo caché expirado'
            ]
        );

    // Información más detallada
    $cacheInfoText = sprintf(
        '%s | Total: %d archivos (%s MB) | Admins: %d | Gestores: %d | Válidos: %d | Expirados: %d | Duración: %s',
        $cacheInfo['current_user_role'],
        $cacheInfo['total_files'],
        $cacheInfo['total_size_mb'],
        $cacheInfo['admin_files'],
        $cacheInfo['contractor_files'],
        $cacheInfo['valid_files'],
        $cacheInfo['expired_files'],
        $cacheInfo['cache_lifetime_formatted']
    );

    $actionLinks .= ' <span id="cache-info" class="label label-info" style="cursor: help; padding: 5px 10px;" title="' .
        $cacheInfoText . '">' .
        '<i class="fa fa-database"></i> Admins: ' . $cacheInfo['admin_files'] . ' | Gestores: ' . $cacheInfo['contractor_files'] . ' (' . $cacheInfo['total_size_mb'] . ' MB)' .
        '</span>';

} else if (api_is_contractor_admin()) {
    // Botón solo para limpiar el caché de su RUC (gestores de cupo)
    $cacheInfo = $cacheManager->getInfo();

    $actionLinks .= ' ' . Display::url(
            Display::return_icon('data-clear.png', 'Limpiar Mi Caché', [], ICON_SIZE_MEDIUM),
            'javascript:void(0)',
            [
                'id' => 'btn-clear-user-cache',
                //'class' => 'btn btn-warning btn-sm',
                'title' => 'Limpiar caché de mi RUC'
            ]
        );

    $plugin = ProikosPlugin::create();
    $myRuc = $plugin::getUserRucCompany();
    $myRucFiles = $cacheInfo['by_ruc'][$myRuc] ?? 0;

    $actionLinks .= ' <span id="cache-info" class="label label-info" style="cursor: help; padding: 5px 10px;" title="' .
        $cacheInfo['current_user_role'] . ' | Archivos de tu RUC: ' . $myRucFiles . '">' .
        '<i class="fa fa-database"></i> ' . $myRucFiles . ' archivos de tu RUC' .
        '</span>';
}

$courses = [];
$courses['%'] = $plugin->get_lang('SelectCourse');
$coursesList = CourseManager::get_courses_list(
    0,
    0,
    'title',
    'asc',
    -1,
    null,
    api_get_current_access_url_id(),
    false,
    [],
    []
);

$coursesJsFormat = [];
$coursesJsFormat[] = [
    'id' => '%',
    'name' => $plugin->get_lang('SelectCourse'),
    'badge' => ''
];
foreach ($coursesList as $course) {
    $courses[$course['id']] = $course['title'] . (!empty($course['visual_code']) ? " (" . $course['visual_code'] . ")" : "");
    $coursesJsFormat[] = [
        'id' => $course['id'],
        'name' => $course['title'],
        'badge' => (!empty($course['visual_code']) ? "<span class='label label-info'>" . $course['visual_code'] . "</span>" : "")
    ];
}

$url = api_get_self();
$sessions = [];
$sessions['%'] = $plugin->get_lang('SelectSession');
if (!empty($courseId)) {
    $sessionsList = SessionManager::getSessionsForAdmin(
        api_get_user_id(),
        [
            'where' => [],
            'extra' => []
        ],
        false,
        [],
        'all'
    );

    foreach ($sessionsList as $session) {
        $sessions[$session['id']] = $session['name'];
    }
}

$form = new FormValidator('search_simple', 'get', null, null, null, 'inline');

$form->addSelect(
    'course_id',
    get_lang('Course'),
    $courses
);
$coursesJsFormat = json_encode($coursesJsFormat, JSON_UNESCAPED_UNICODE);
$form->addHtml(
    <<<EOT
    <script>
        $(document).ready(function() {
            $('select[name="course_id"]').change(function() {
                var courseId = $(this).val();
                if (courseId) {
                    window.location.href = '{$url}?course_id=' + courseId;
                }
            });

            const courses = JSON.parse(`{$coursesJsFormat}`);
            const \$select = $('select[name="course_id"]');
            \$select.empty();

            const courseId = '{$courseId}';

            courses.forEach(course => {
               const isSelected = course.id == courseId ? true : false;
              \$select.append(
                $('<option>', {
                  value: course.id,
                  'data-content': course.name + ' ' + (course.badge ?? ''),
                  text: course.name,
                  selected: isSelected
                })
              );
            });
        });

    </script>
EOT
);

$form->addElement(
    'select',
    'session_id',
    get_lang('Session'),
    $sessions,
    ['style' => 'width: 200px;']
);
$form->addHtml(
    <<<EOT
    <script>
        $(document).ready(function() {
            $('select[name="session_id"]').change(function() {
                var sessionId = $(this).val();
                if (sessionId) {
                    window.location.href = '{$url}?course_id={$courseId}&session_id=' + sessionId;
                }
            });
        });

    </script>
EOT
);

$form->setDefaults([
    'course_id' => $courseId,
    'session_id' => $sessionId
]);

$form->addText('keyword', $plugin->get_lang('SearchUserByDNI'), false, [
    'placeholder' => 'Buscar usuario por DNI',
    'style' => 'display: block'
]);

$contratingCompanies = $plugin->contratingCompaniesModel()->getData();
$listRuc = [
    '0' => 'Selecciona una empresa',
    '20100128218' => '20100128218 - PETROPERÚ S.A.'
];
foreach ($contratingCompanies as $company) {
    if ($company['ruc'] === '20100128218') {
        continue;
    }
    $listRuc[$company['ruc']] = $company['ruc'].' - '.$company['name'];
}

$form->addSelect('ruc', $plugin->get_lang('Company_RUC'), $listRuc);

// Selectores mes/año para filtro de fecha de sesión
$months = ['' => 'Mes', '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril', '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto', '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'];
$currentYear = (int) date('Y');
$years = ['' => 'Año'];
for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
    $years[$y] = $y;
}

// Extraer mes/año actuales de $dateFrom y $dateTo
$fromMonth = $dateFrom ? substr($dateFrom, 5, 2) : '';
$fromYear  = $dateFrom ? substr($dateFrom, 0, 4) : '';
$toMonth   = $dateTo   ? substr($dateTo, 5, 2)   : '';
$toYear    = $dateTo   ? substr($dateTo, 0, 4)   : '';

$form->addHtml(
    '<div class="form-group" style="display:inline-block; vertical-align:bottom; margin-left:8px;">' .
    '<label style="display:block; font-size:12px; margin-bottom:2px;">Desde (mes/año)</label>' .
    '<div style="display:flex; gap:4px;">' .
    '<select name="from_month" class="form-control input-sm" style="width:100px;">'
);
foreach ($months as $val => $label) {
    $sel = ($fromMonth === $val) ? ' selected' : '';
    $form->addHtml("<option value=\"{$val}\"{$sel}>{$label}</option>");
}
$form->addHtml(
    '</select>' .
    '<select name="from_year" class="form-control input-sm" style="width:78px;">'
);
foreach ($years as $val => $label) {
    $sel = ((string)$fromYear === (string)$val) ? ' selected' : '';
    $form->addHtml("<option value=\"{$val}\"{$sel}>{$label}</option>");
}
$form->addHtml(
    '</select></div></div>' .
    '<div class="form-group" style="display:inline-block; vertical-align:bottom; margin-left:8px;">' .
    '<label style="display:block; font-size:12px; margin-bottom:2px;">Hasta (mes/año) <span style="color:#888; font-size:11px;">máx. 3 meses</span></label>' .
    '<div style="display:flex; gap:4px;">' .
    '<select name="to_month" class="form-control input-sm" style="width:100px;">'
);
foreach ($months as $val => $label) {
    $sel = ($toMonth === $val) ? ' selected' : '';
    $form->addHtml("<option value=\"{$val}\"{$sel}>{$label}</option>");
}
$form->addHtml(
    '</select>' .
    '<select name="to_year" class="form-control input-sm" style="width:78px;">'
);
foreach ($years as $val => $label) {
    $sel = ((string)$toYear === (string)$val) ? ' selected' : '';
    $form->addHtml("<option value=\"{$val}\"{$sel}>{$label}</option>");
}
$form->addHtml('</select></div></div>');

// JS para convertir mes/año → date_from / date_to y validar max 3 meses antes de submit
$form->addHtml(<<<EOT
<script>
$(document).ready(function() {
    $('form[name="search_simple"]').on('submit', function(e) {
        var fromM = $('select[name="from_month"]').val();
        var fromY = $('select[name="from_year"]').val();
        var toM   = $('select[name="to_month"]').val();
        var toY   = $('select[name="to_year"]').val();

        if ((fromM && !fromY) || (!fromM && fromY) || (toM && !toY) || (!toM && toY)) {
            alert('Por favor seleccione mes Y año para el filtro de fecha.');
            e.preventDefault();
            return;
        }

        if (fromM && fromY) {
            var lastDay = new Date(parseInt(fromY), parseInt(fromM), 0).getDate();
            $('<input>').attr({type:'hidden', name:'date_from', value: fromY+'-'+fromM+'-01'}).appendTo(this);

            if (toM && toY) {
                var fromDate = new Date(parseInt(fromY), parseInt(fromM)-1, 1);
                var toDate   = new Date(parseInt(toY),   parseInt(toM)-1,   1);
                var monthsDiff = (toDate.getFullYear() - fromDate.getFullYear()) * 12 + (toDate.getMonth() - fromDate.getMonth());

                if (toDate < fromDate) {
                    alert('La fecha "Hasta" no puede ser anterior a "Desde".');
                    e.preventDefault();
                    return;
                }
                if (monthsDiff > 2) {
                    alert('El rango máximo es 3 meses. Se ajustará automáticamente.');
                    toDate = new Date(fromDate.getFullYear(), fromDate.getMonth() + 2, 1);
                    toY = toDate.getFullYear();
                    toM = String(toDate.getMonth() + 1).padStart(2, '0');
                }
                var toLastDay = new Date(parseInt(toY), parseInt(toM), 0).getDate();
                $('<input>').attr({type:'hidden', name:'date_to', value: toY+'-'+toM+'-'+toLastDay}).appendTo(this);
            } else {
                // Sin "hasta": usar último día del mes "desde"
                $('<input>').attr({type:'hidden', name:'date_to', value: fromY+'-'+fromM+'-'+lastDay}).appendTo(this);
            }
        }
    });
});
</script>
EOT
);

$form->addButtonSearch(get_lang('Search'));
$actionsLeft = $form->returnForm();

$actionsRight = Display::url(
    Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), [], ICON_SIZE_MEDIUM),
    'javascript:void(0)',
    ['onclick' => 'openExportModal()', 'title' => 'Exportar Excel']
);

$toolbarActions = Display::toolbarAction('toolbarData', [$actionsLeft, '', $actionsRight], [9, 1, 2]);

$urlAjaxPlugin = api_get_path(WEB_PLUGIN_PATH)."proikos/src/ajax.php";

// Pasar parámetros a Vue
$vueParams = json_encode([
    'keyword' => $dni,
    'course_id' => $courseId,
    'session_id' => $sessionId,
    'ruc' => $ruc,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'page' => $page,
    'perPage' => $perPage,
    'ajaxUrl' => $urlAjaxPlugin
]);

$tpl->assign('actions', Display::toolbarAction('toolbar', [$actionLinks]));
$tpl->assign('message', $message);
$tpl->assign('url_ajax', $urlAjaxPlugin);
$tpl->assign('vue_params', $vueParams);
$tpl->assign('perPage', $perPage);
$tpl->assign('data_report_url', api_get_self());

$content = $tpl->fetch('proikos/view/proikos_report_data_vue.tpl');
$tpl->assign('content', $toolbarActions . $content);
$tpl->display_one_col_template();
