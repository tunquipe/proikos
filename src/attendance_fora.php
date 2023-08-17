<?php
require_once __DIR__ . '/../config.php';
api_block_anonymous_users();
$action = $_GET['action'] ?? null;

$plugin = ProikosPlugin::create();
$tool_name = $plugin->get_lang('ManageEntities');
$actionLinks = null;
$tpl = new Template($tool_name);
$isAdmin = api_is_platform_admin();

if($isAdmin){
    $actionLinks .= Display::url(
        Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_PLUGIN_PATH) . 'proikos/start.php'
    );
}

if ($isAdmin) {
    $tpl->assign(
        'actions',
        Display::toolbarAction('toolbar', [$actionLinks])
    );
}

if($action == 'export_pdf'){
    $idSession = $_REQUEST['keyword'] ?? null;
    $filename = 'list-fora-';
    $plugin->exportListForaPDF($idSession);
}


$form = new FormValidator(
    'export',
    'post',
    api_get_self() . '?action=export_pdf'
);
try {
    $form->addSelectAjax(
        'keyword',
        $plugin->get_lang('SessionName'),
        [],
        ['url' => api_get_path(WEB_AJAX_PATH) . 'session.ajax.php?a=search_session', 'id' => 'session']
    );
} catch (Exception $e) {
    print_r($e);
}
$form->addButtonExport($plugin->get_lang('Export'));

$form->addHeader($plugin->get_lang('ExportList'));
$tpl->assign('form', $form->returnForm());



$content = $tpl->fetch('proikos/view/proikos_fora.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
