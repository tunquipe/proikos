<?php

$cidReset = true;

require_once __DIR__ . '/../config.php';
api_block_anonymous_users();

if (!api_is_platform_admin()) {
    api_not_allowed(true);
}

$plugin = ProikosPlugin::create();
$tool_name = 'Data';
$actionLinks = null;
$message = null;
$htmlHeadXtra[] = api_get_css_asset('cropper/dist/cropper.min.css');
$htmlHeadXtra[] = api_get_asset('cropper/dist/cropper.min.js');

$action = $_GET['export'] ?? null;
$keyword = $_GET['keyword'] ?? null;
$courseId = $_GET['course_id'] ?? '%';
$sessionId = $_GET['session_id'] ?? '%';

if (isset($action)) {
    switch ($action) {
        case 'xls':
            $fileName = 'report_' . api_get_local_time();
            $rawData = $plugin->getData(null, null, null, null, $courseId, $sessionId, $keyword);

            $headers = [
                'Nº',
                'Fecha',
                'Nº Horas',
                'Nombre del curso',
                'Nombres y Apellidos',
                'Nº DNI / C.E',
                'RUC',
                'Empresa',
                'Sede',
            ];

            foreach ($plugin->getDATAcolumns($courseId, $sessionId) as $column) {
                $headers[] = $column;
            }

            if (count($headers) > 9) {
                $headers[] = 'Estado';
                $headers[] = 'Observaciones';
            }

            $cleanData = [];
            foreach ($rawData as $row) {
                $cleanRow = array_map(function ($value) {
                    return strip_tags($value);
                }, array_values($row));
                $cleanData[] = $cleanRow;
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

function get_number_of_users()
{
    global $plugin, $keyword, $courseId, $sessionId;
    return $plugin->getData(null, null, null, null, $courseId, $sessionId, $keyword, true);
}

function get_user_data($from, $number_of_items, $column, $direction)
{
    global $plugin, $keyword, $courseId, $sessionId;
    return $plugin->getData($from, $number_of_items, $column, $direction, $courseId, $sessionId, $keyword);
}

$table = new SortableTable('users', 'get_number_of_users', 'get_user_data', 2);

if (isset($keyword)) {
    $table->set_additional_parameters(['keyword' => $keyword]);
}

if (isset($courseId) && isset($sessionId)) {
    $table->set_additional_parameters([
        'course_id' => $courseId,
        'session_id' => $sessionId
    ]);
}

$table->set_header(0, 'Nº', false);
$table->set_header(1, 'Fecha', false);
$table->set_header(2, 'Nº Horas', false);
$table->set_header(3, 'Nombre del curso', false);
$table->set_header(4, 'Sesión', false);
$table->set_header(5, 'Nombres y Apellidos', true);
$table->set_header(6, 'Nº DNI / C.E', false);
$table->set_header(7, 'RUC', false);
$table->set_header(8, 'Empresa', false);
$table->set_header(9, 'Sede', false);

$initialIndex = 10;
foreach ($plugin->getDATAcolumns($courseId, $sessionId) as $column) {
    $table->set_header($initialIndex, $column, false);
    $initialIndex++;
}

$table->set_header($initialIndex++, 'Estado', false);
$table->set_header($initialIndex, 'Observaciones', false);

$contentTable = $table->return_table();

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
$form->addButtonSearch(get_lang('Search'));
$actionsLeft = $form->returnForm();

$actionsRight = Display::url(
    Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), [], ICON_SIZE_MEDIUM),
    api_get_self() . '?' . http_build_query([
        'export' => 'xls',
        'course_id' => $courseId,
        'session_id' => $sessionId,
    ])
);

$toolbarActions = Display::toolbarAction('toolbarData', [$actionsLeft, '', $actionsRight], [9, 1, 2]);

$tpl->assign('actions', Display::toolbarAction('toolbar', [$actionLinks]));
$tpl->assign('message', $message);
$tpl->assign('users_table', $contentTable);
$content = $tpl->fetch('proikos/view/proikos_data.tpl');
$tpl->assign('content', $toolbarActions . $content);
$tpl->display_one_col_template();
