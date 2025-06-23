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

$action = $_GET['export'] ?? null;
$keyword = $_GET['keyword'] ?? null;
$courseId = $_GET['course_id'] ?? '%';
$sessionId = $_GET['session_id'] ?? '%';

if (isset($action)) {
    switch ($action) {
        case 'xls':
            $fileName = 'report_' . api_get_local_time();
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

$users = $plugin->getDataReport();

$tpl->assign('actions', Display::toolbarAction('toolbar', [$actionLinks]));
$tpl->assign('message', $message);
$tpl->assign('users', $users);
$content = $tpl->fetch('proikos/view/proikos_report_data.tpl');
$tpl->assign('content', $toolbarActions . $content);
$tpl->display_one_col_template();
