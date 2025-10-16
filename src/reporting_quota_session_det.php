<?php

require_once __DIR__ . '/../config.php';
api_block_anonymous_users();
$action = $_REQUEST['action'] ?? null;
$plugin = ProikosPlugin::create();
$allow = api_is_platform_admin() || api_is_drh() || api_is_contractor_admin();
$htmlHeadXtra[] = api_get_css(api_get_path(WEB_PLUGIN_PATH) . 'proikos/css/style.css');

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 30;

// Capturar parámetros de búsqueda
$searchBy = $_GET['search_by'] ?? '';
$searchTerm = $_GET['search_term'] ?? '';

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
        case 'delete_select':
            $idQuotas = $_REQUEST['ids'] ?? null;
            foreach ($idQuotas as $idReport) {
                $idQuotaSession = $plugin->getIDSessionQuota($idReport);
                $plugin->updateMinusSessionQuota($idQuotaSession);
                $res = $plugin->deleteReportLogRow($idReport);
            }
            $url = api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/reporting_quota_session_det.php';
            header('Location: ' . $url);
            break;
        default:
    }
}

// Crear el formulario de búsqueda
$searchForm = '
<form method="get" class="form-inline" style="margin-bottom: 10px;">
    <div class="form-group" style="margin-right: 10px;">
        <select name="search_by" class="form-control" required>
            <option value="">Buscar por...</option>
            <option value="ruc" ' . ($searchBy == 'ruc' ? 'selected' : '') . '>RUC</option>
            <option value="company" ' . ($searchBy == 'company' ? 'selected' : '') . '>Nombre de Empresa</option>
            <option value="student" ' . ($searchBy == 'student' ? 'selected' : '') . '>Nombre de Estudiante</option>
        </select>
    </div>
    <div class="form-group" style="margin-right: 10px;">
        <input type="text" name="search_term" class="form-control" placeholder="Término de búsqueda" value="' . htmlspecialchars($searchTerm) . '" required>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="fa fa-search"></i> Buscar
    </button>
    <a href="' . api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/reporting_quota_session_det.php" class="btn btn-default">
        <i class="fa fa-refresh"></i> Limpiar
    </a>
</form>';

$actionLinks = Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_PLUGIN_PATH) . 'proikos/start.php'
);

$tool_name = $plugin->get_lang('CouponRegistrationReport');

// Pasar parámetros de búsqueda a las funciones
$searchParams = [
    'search_by' => $searchBy,
    'search_term' => $searchTerm
];

$items = $plugin->contratingCompaniesQuotaSessionDetModel()->getPaginatedData($page, $perPage, $searchParams);
$total = $plugin->contratingCompaniesQuotaSessionDetModel()->getTotalRecords($searchParams);
$totalPages = ceil($total / $perPage);

// Construir URL con parámetros de búsqueda para la paginación
$url_self = api_get_self();
$queryParams = [];
if (!empty($searchBy)) $queryParams[] = 'search_by=' . urlencode($searchBy);
if (!empty($searchTerm)) $queryParams[] = 'search_term=' . urlencode($searchTerm);
$queryString = !empty($queryParams) ? '?' . implode('&', $queryParams) : '';

$tpl = new Template($tool_name);
$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$actionLinks, $searchForm],[5,7])
);
$isAdmin = api_is_platform_admin();
$tpl->assign('items', $items);
$tpl->assign('url_self', $url_self . $queryString);
$tpl->assign('current_page', $page);
$tpl->assign('total_pages', $totalPages);
$tpl->assign('total_records', $total);
$tpl->assign('per_page', $perPage);
$tpl->assign('is_admin', $isAdmin);

$content = $tpl->fetch('proikos/view/proikos_reporting_quota_session_det.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
