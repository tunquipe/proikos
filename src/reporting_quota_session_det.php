<?php

require_once __DIR__ . '/../config.php';
api_block_anonymous_users();
$action = $_GET['action'] ?? null;
$plugin = ProikosPlugin::create();
$allow = api_is_platform_admin() || api_is_drh() || api_is_contractor_admin();

if (!$allow) {
    api_not_allowed(true);
}

$actionLinks = '';
$actionLinks .= Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_PLUGIN_PATH) . 'proikos/start.php'
);

$tool_name = 'Reporte de administración de cupones';
$items = $plugin->contratingCompaniesQuotaSessionDetModel()->getData();

$tpl = new Template($tool_name);
$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$actionLinks])
);
$tpl->assign('items', $items);
$content = $tpl->fetch('proikos/view/proikos_reporting_quota_session_det.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
