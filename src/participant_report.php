<?php
require_once __DIR__ . '/../config.php';
api_block_anonymous_users();
$action = $_GET['action'] ?? null;
$plugin = ProikosPlugin::create();
$tool_name = $plugin->get_lang('ParticipantReport');
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

$content = $tpl->fetch('proikos/view/proikos_fora.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
