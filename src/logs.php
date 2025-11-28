<?php

$cidReset = true;

require_once __DIR__ . '/../config.php';
api_block_anonymous_users();

if (!api_is_platform_admin() && !api_is_drh() && !api_is_contractor_admin()) {
    api_not_allowed(true);
}
$htmlHeadXtra[] = api_get_css(api_get_path(WEB_PLUGIN_PATH) . 'proikos/css/style.css');
$plugin = ProikosPlugin::create();
$tool_name = 'Data';
$actionLinks = null;
$message = null;

$action = $_GET['action'] ?? null;
$dni = $_GET['keyword'] ?? null;
$courseId = $_GET['course_id'] ?? '%';
$sessionId = $_GET['session_id'] ?? '%';
$ruc = $_GET['ruc'] ?? '0';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$page = $_GET['page'] ?? 1;
$perPage = $_GET['perPage'] ?? 100;

if (isset($action)) {
    switch ($action) {
        case 'delete':
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $result = $plugin->deleteProikosLogRecord($id);
            if ($result) {
                Display::addFlash(
                    Display::return_message('Registro eliminado exitosamente', 'success')
                );
            } else {
                Display::addFlash(
                    Display::return_message('Error al eliminar el registro', 'error')
                );
            }
            break;
        case 'update':
        default:
            break;
    }
}

$tpl = new Template($tool_name);
$isAdmin = api_is_platform_admin();

$actionLinks .= Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_PLUGIN_PATH) . 'proikos/start.php'
);
$url = api_get_self();

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


$form->addText('keyword', $plugin->get_lang('SearchUserByDNI'), false, [
    'placeholder' => 'Buscar usuario por DNI',
    'style' => 'display: block'
]);

$form->addButtonSearch(get_lang('Search'));
$actionsLeft = $form->returnForm();
$toolbarActions = Display::toolbarAction('toolbarData', [$actionsLeft], [9, 1, 2]);

$data = $plugin->getDataUsersReportProikos($dni, $courseId, $sessionId, $ruc, $page, $perPage,false,[2,3]);
$urlAjax = api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/ajax.php';

$tpl->assign('actions', Display::toolbarAction('toolbar', [$actionLinks]));
$tpl->assign('message', $message);
$tpl->assign('url_ajax', $urlAjax);
$tpl->assign('is_platform_admin', $isAdmin);
$tpl->assign('data', $data);
$tpl->assign('perPage', $perPage);
$content = $tpl->fetch('proikos/view/proikos_logs.tpl');
$tpl->assign('content', $toolbarActions. $content);
$tpl->display_one_col_template();

