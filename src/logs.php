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

$page = $_GET['page'] ?? 1;
$perPage = $_GET['perPage'] ?? 100;

$tpl = new Template($tool_name);
$isAdmin = api_is_platform_admin();

$form = new FormValidator('search_simple', 'get', null, null, null, 'inline');
$form->addText('keyword', $plugin->get_lang('SearchUserByDNI'), false, [
    'placeholder' => 'Buscar usuario por DNI',
    'style' => 'display: block'
]);
$form->addButtonSearch(get_lang('Search'));
$actionsLeft = $form->returnForm();
$toolbarActions = Display::toolbarAction('toolbarData', [$actionsLeft], [9, 1, 2]);

$actionLinks .= Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_PLUGIN_PATH) . 'proikos/start.php'
);

$data = $plugin->getDataUsersReportProikos($dni, $courseId, $sessionId, $ruc, $page, $perPage);

$tpl->assign('actions', Display::toolbarAction('toolbar', [$actionLinks]));
$tpl->assign('message', $message);
$tpl->assign('data', $data);
$tpl->assign('perPage', $perPage);
$content = $tpl->fetch('proikos/view/proikos_logs.tpl');
$tpl->assign('content', $toolbarActions. $content);
$tpl->display_one_col_template();

