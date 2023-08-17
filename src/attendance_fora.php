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
    $marginTop = '0.5cm';
    $marginBottom = '0.5cm';
    $margin = $marginTop . ' ' . $marginRight . ' ' . $marginBottom . ' ' . $marginLeft;
    $infoCompany = $plugin->getEntity($idCompany);
    $logoCompany = $url.$infoCompany['picture'];

    $tplPDFHeader = new Template();
    $tplPDFFooter = new Template();

    $tplPDFHeader->assign('logo_company', $logoCompany);
    $tplPDFHeader->assign('margin', $margin);

    $html =  '
        <!DOCTYPE html>
        <body style="margin: 0; padding: 0;">
        <div style="font-family: Arial, Helvetica, sans-serif; padding:'.$margin.'">
    ';
    $employerHeader = true;
    $employerFooter = false;

    $students = $plugin->getStudentsSession($idSession);
    $recordPerPage = 15;
    $onePage = 10;
    $pages = ceil(count($students) / $recordPerPage);

    for ($page = 1; $page <= $pages; $page++) {
        //$html .= 'Página ' . $page;

        if($page <= 1){
            $recordPerPage = $onePage;
        } else {
            $start = ($page - 2) * $recordPerPage + $onePage;
            $recordPerPage = 15;
            $employerHeader = false;
            $employerFooter = true;
        }
        $tplPDFHeader->assign('number_page', $page);
        $tplPDFHeader->assign('total_pages', $pages);
        $tplPDFHeader->assign('employer_header', $employerHeader);
        $tplPDFFooter->assign('employer_footer', $employerFooter);
        $contentHeader = $tplPDFHeader->fetch('proikos/view/proikos_pdf_fora_header.tpl');
        $contentFooter = $tplPDFFooter->fetch('proikos/view/proikos_pdf_fora_footer.tpl');

        $html.= $contentHeader;
        $html.= '
                <table style="border: 2px solid #000; width: 850px; border-collapse: collapse;">
                <tr>
                    <td style="border-right: 1px solid #000; text-align: center; font-weight: bold; width: 50px; height: 40px;">
                        Nº
                    </td>
                    <td style="border-right: 1px solid #000; text-align: center; text-transform: uppercase; width: 40%; font-weight: bold;">
                        Apellidos y Nombres
                    </td>
                    <td style="border-right: 1px solid #000; text-align: center; text-transform: uppercase;  width: 10%; font-weight: bold;">
                        DNI Nº
                    </td>
                    <td style="border-right: 1px solid #000; text-align: center; text-transform: uppercase; width: 10%; font-weight: bold;">
                        Ficha Nº
                    </td>
                    <td style="border-right: 1px solid #000; text-align: center; text-transform: uppercase; width: 10%; font-weight: bold;">
                        Dependencia
                    </td>
                    <td style="border-right: 1px solid #000; text-align: center; text-transform: uppercase; width: 10%; font-weight: bold;">
                        Firma
                    </td>
                    <td style="border-right: 1px solid #000; text-align: center; text-transform: uppercase; width: 15%; font-weight: bold;">
                        Observaciones
                    </td>
                </tr>
                ';
        foreach (array_slice($students, $start, $recordPerPage) as $student) {
            $html.= '
                <tr style="text-align: center; border: 1px solid #000;">
                    <td style="border-right: 1px solid #000; width: 50px; height: 40px;">'.$student['number'].'</td>
                    <td style="border-right: 1px solid #000; width: 20%; font-size: 12px; text-transform: uppercase;">'.$student['lastname'].','.$student['firstname'].'</td>
                    <td style="border-right: 1px solid #000; width: 10%; text-align: center">'.$student['email'].'</td>
                    <td style="border-right: 1px solid #000; width: 10%; ">&nbsp; </td>
                    <td style="border-right: 1px solid #000; width: 10%; ">&nbsp; </td>
                    <td style="border-right: 1px solid #000; width: 10%; ">&nbsp; </td>
                    <td style="width: 10%;"></td>
                </tr>
            ';
        }
        $html.= '</table>';
        $html.= $contentFooter;
        if($page < $pages){
            $html.="<p style='page-break-after: always'></p>";
        }
    }

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

    $html.= '</div></body></html>';
    $content = $html;

    $tplPDF =  new Template($tool_name,false,false,false,false,false,false);
    $tpl->assign('students', $students);
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
