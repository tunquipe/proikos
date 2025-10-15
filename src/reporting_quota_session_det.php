<?php

require_once __DIR__ . '/../config.php';
api_block_anonymous_users();
$action = $_GET['action'] ?? null;
$plugin = ProikosPlugin::create();
$allow = api_is_platform_admin() || api_is_drh() || api_is_contractor_admin();
$htmlHeadXtra[] = api_get_css(api_get_path(WEB_PLUGIN_PATH) . 'proikos/css/style.css');

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 30;

if (!$allow) {
    api_not_allowed(true);
}
if (api_is_platform_admin()) {
    switch ($action) {
        case 'delete':
            $idReport = $_GET['id'] ?? null;
            $idQuotaSession = $_GET['quota_id_s'] ?? null;
            $plugin->updateMinusSessionQuota($idQuotaSession);
            $res = $plugin->deleteReportLogRow($idReport);
            if ($res) {
                $url = api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/reporting_quota_session_det.php';
                header('Location: ' . $url);
            }
            break;
            case 'delete_select';
                $idQuotaSession = $_GET['quota_id_s'] ?? null;
            echo 'elkiminados';
            exit;
            default;
    }
}

$actionLinks = Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_PLUGIN_PATH) . 'proikos/start.php'
);

$tool_name = $plugin->get_lang('CouponRegistrationReport');
$items = $plugin->contratingCompaniesQuotaSessionDetModel()->getPaginatedData($page, $perPage);
$total = $plugin->contratingCompaniesQuotaSessionDetModel()->getTotalRecords();
$totalPages = ceil($total / 30);

$url_self = api_get_self();
$tpl = new Template($tool_name);
$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$actionLinks])
);
$tpl->assign('items', $items);
$tpl->assign('url_self', $url_self);
$tpl->assign('current_page', $page);
$tpl->assign('total_pages', $totalPages);
$tpl->assign('total_records', $total);
$tpl->assign('per_page', $perPage);

$content = $tpl->fetch('proikos/view/proikos_reporting_quota_session_det.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
