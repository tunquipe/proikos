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
    $idCompany = $_REQUEST['company'] ?? null;
    $url = api_get_path(WEB_UPLOAD_PATH);
    $filename = 'list-fora-';

    $marginLeft = '1cm';
    $marginRight = '1cm';
    $marginTop = '1cm';
    $marginBottom = '1cm';
    $margin = $marginTop . ' ' . $marginRight . ' ' . $marginBottom . ' ' . $marginLeft;
    $infoCompany = $plugin->getEntity($idCompany);
    $logoCompany = $url.$infoCompany['picture'];

    $params = [
        'filename' => api_replace_dangerous_char(
            $filename.' '.
            api_get_local_time()
        ),
        'orientation' => 'P',
        'format' => 'A4',
        'left' => 0,
        'top' => 0,
        'bottom' => 0,
        'right' => 0
    ];

    $students = $plugin->getStudentsSession($idSession);
    $tplPDF =  new Template($tool_name,false,false,false,false,false,false);
    $tpl->assign('students', $students);
    $tpl->assign('margin', $margin);
    $tpl->assign('logo_company', $logoCompany);
    $content = $tpl->fetch('proikos/view/proikos_pdf_fora.tpl');
    $pdf = new PDF($params['format'], $params['orientation'], $params);
    $pdf->content_to_pdf($content, false, $filename, null,'D',false,null,false,false,false);
    exit;
}

$company = $plugin->getListEntity();
$listCompanies = [];
foreach ($company as $row){
    $listCompanies[$row['id']] = $row['name_entity'];
}

$form = new FormValidator(
    'export',
    'post',
    api_get_self() . '?action=export_pdf'
);
$form->addSelect('company',$plugin->get_lang('NameEntity'), $listCompanies);
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
