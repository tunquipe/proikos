<?php

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

if (isset($action)) {
    switch ($action) {
        case 'xls':
            $fileName = 'report_' . api_get_local_time();
            $rawData = $plugin->getData(null, null, null, null, $keyword);

            $headers = [
                'Nº',
                'Fecha',
                'Nº Horas',
                'Nombre del curso',
                'Nombres y Apellidos',
                'Nº DNI / C.E',
                'Empresa',
                'Sede',
                'Ex. Entrada (10%)',
                'Ex. Práctico (60%)',
                'Ex. Salida (30%)',
                'Nota Final',
                'Estado',
                'Observaciones'
            ];

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
    global $plugin, $keyword;
    return $plugin->getData(null, null, null, null, $keyword, true);
}

function get_user_data($from, $number_of_items, $column, $direction)
{
    global $plugin, $keyword;
    return $plugin->getData($from, $number_of_items, $column, $direction, $keyword);
}

$table = new SortableTable('users', 'get_number_of_users', 'get_user_data', 2);

if (isset($keyword)) {
    $table->set_additional_parameters(['keyword' => $keyword]);
}

$table->set_header(0, 'Nº', true);
$table->set_header(1, 'Fecha', true);
$table->set_header(2, 'Nº Horas', true);
$table->set_header(3, 'Nombre del curso', true);
$table->set_header(4, 'Nombres y Apellidos', true);
$table->set_header(5, 'Nº DNI / C.E', true);
$table->set_header(6, 'Empresa', true);
$table->set_header(7, 'Sede', true);
$table->set_header(8, 'Ex. Entrada (10%)', true);
$table->set_header(9, 'Ex. Práctico (60%)', true);
$table->set_header(10, 'Ex. Salida (30%)', true);
$table->set_header(11, 'Nota Final', true);
$table->set_header(12, 'Estado', true);
$table->set_header(13, 'Observaciones', true);

$contentTable = $table->return_table();

$form = new FormValidator('search_simple', 'get', null, null, null, 'inline');
$form->addText('keyword', get_lang('Search'), false, ['placeholder' => 'Buscar usuario']);
$form->addButtonSearch(get_lang('Search'));
$actionsLeft = $form->returnForm();

$actionsRight = Display::url(
    Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), [], ICON_SIZE_MEDIUM),
    api_get_self() . '?' . http_build_query(['export' => 'xls'])
);

$toolbarActions = Display::toolbarAction('toolbarData', [$actionsLeft, '', $actionsRight], [4, 4, 4]);

$tpl->assign('actions', Display::toolbarAction('toolbar', [$actionLinks]));
$tpl->assign('message', $message);
$tpl->assign('users_table', $contentTable);
$content = $tpl->fetch('proikos/view/proikos_data.tpl');
$tpl->assign('content', $toolbarActions . $content);
$tpl->display_one_col_template();
