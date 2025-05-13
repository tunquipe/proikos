<?php

require_once __DIR__ . '/../config.php';
api_block_anonymous_users();
$action = $_GET['action'] ?? null;
$plugin = ProikosPlugin::create();
$tool_name = 'Gestionar usuarios';
$message = null;
$actionLinks = null;
$htmlHeadXtra[] = api_get_css_asset('cropper/dist/cropper.min.css');
$htmlHeadXtra[] = api_get_asset('cropper/dist/cropper.min.js');

$actionLinks .= Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_PLUGIN_PATH) . 'proikos/start.php'
);


$tpl = new Template($tool_name);
$isAdmin = api_is_platform_admin();
$users = $plugin->getUsers();
$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$actionLinks])
);

$tpl->assign('message', $message);
$tpl->assign('users', $users);
$content = $tpl->fetch('proikos/view/proikos_users.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
