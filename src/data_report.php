<?php

$cidReset = true;

require_once __DIR__ . '/../config.php';
api_block_anonymous_users();

if (!api_is_platform_admin() && !api_is_drh() && !api_is_contractor_admin()) {
    api_not_allowed(true);
}

$plugin = ProikosPlugin::create();
$tool_name = 'Data';
$actionLinks = null;
$message = null;

$action = $_GET['action'] ?? null;
$dni = $_GET['keyword'] ?? null;
$courseId = $_GET['course_id'] ?? '%';
$sessionId = $_GET['session_id'] ?? '%';
$ruc = $_GET['ruc'] ?? '0';

$page = isset($_GET['page']) ? $_GET['page'] : 1;
$perPage = isset($_GET['perPage']) ? $_GET['perPage'] : 30;

if (isset($action)) {
    switch ($action) {
        case 'cron':
            $rawData = $plugin->getDataReport($dni, $courseId, $sessionId, $ruc,1,10, true, 'ASC');
            $count = 0;

            foreach ($rawData['users'] as $row) {
                if ($row['status_id'] != 1) {
                    $count++;
                    $plugin->registerData($row);
                }
            }

            echo 'se registraron ' . $count . ' registros';
            break;
        case 'xls':
            $fileName = 'report_' . api_get_local_time();
            $rawData = $plugin->getDataReport($dni, $courseId, $sessionId, $ruc,1,10, true);
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
                'Observaciones',
                'Certificados Adjuntos',
            ];
            $cleanData = [];

            foreach ($rawData['users'] as $row) {
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
                    'metadata_exists' => $row['metadata_exists'],
                ];
            }
            array_unshift($cleanData, $headers);
            Export::arrayToXls($cleanData, $fileName);
            break;
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
            console.log(courseId)

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
$form->addText('ruc', $plugin->get_lang('SearchUserByRUC'), false, [
    'placeholder' => 'Buscar por RUC de empresa',
    'style' => 'display: block'
]);
$form->addButtonSearch(get_lang('Search'));
$actionsLeft = $form->returnForm();

$actionsRight = Display::url(
    Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), [], ICON_SIZE_MEDIUM),
    api_get_self() . '?' . http_build_query([
        'action' => 'xls',
        'course_id' => $courseId,
        'session_id' => $sessionId,
    ])
);

$toolbarActions = Display::toolbarAction('toolbarData', [$actionsLeft, '', $actionsRight], [9, 1, 2]);

$data = $plugin->getDataReport($dni, $courseId, $sessionId, $ruc, $page, $perPage);

$tpl->assign('actions', Display::toolbarAction('toolbar', [$actionLinks]));
$tpl->assign('message', $message);
$tpl->assign('data', $data);
$tpl->assign('perPage', $perPage);
$content = $tpl->fetch('proikos/view/proikos_report_data.tpl');
$tpl->assign('content', $toolbarActions . $content);
$tpl->display_one_col_template();
