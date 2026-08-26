<?php

require_once __DIR__ . '/../config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

api_block_anonymous_users();
$action = $_GET['action'] ?? null;
$plugin = ProikosPlugin::create();
$tool_name = 'Gestionar usuarios';
$message = null;
$actionLinks = null;

$tpl = new Template($tool_name);
$isAdmin = api_is_platform_admin();

switch ($action){
    case 'edit':
        $actionLinks = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/users_management.php?action=list'
        );
        $idUser = $_GET['user_id'] ?? null;
        $user = $plugin->getInfoUserProikos($idUser);

        $form = new FormValidator(
            'edit',
            'post',
            api_get_self() . '?user_id=' . $idUser.'&action=' . Security::remove_XSS($_GET['action'])
        );
        $form->addHeader($plugin->get_lang('EditUser'));
        $form->addText('lastname', get_lang('LastName'), false, ['disabled' => true]);
        $form->addText('firstname', get_lang('FirstName'), false, ['disabled' => true]);
        $form->addText('email', get_lang('Email'), false, ['disabled' => true]);
        $form->addText('username', get_lang('Username'), false, ['disabled' => true]);
        $form->addText('phone', $plugin->get_lang('Phone'), false);
        $typesDocuments = [
            '0' => 'Seleccione una opción',
            '1' => 'DNI',
            '2' => 'Carnet de Extranjeria',
            '3' => 'Pasaporte',
            '5' => 'Otros',
        ];
        $form->addSelect('type_document', $plugin->get_lang('TypeDocument'), $typesDocuments);
        $form->addText('number_document', $plugin->get_lang('NumberDocument'), false);
        $form->addNumeric('age', $plugin->get_lang('Age'), ['class' => 'form-control']);
        $genders = [
            '0' => 'Seleccione una opción',
            'M' => 'Masculino',
            'F' => 'Femenino'
        ];
        $form->addSelect('gender', $plugin->get_lang('Gender'), $genders);

        $instructions = [
            '0' => 'Seleccione una opción',
            '1' => 'Primaria',
            '2' => 'Secundaria',
            '3' => 'Técnica superior',
            '4' => 'Universitaria Bachiller',
            '5' => 'Universitaria Titulada',
        ];
        $form->addSelect('instruction', $plugin->get_lang('GradeInstructions'), $instructions);
        $stakeholders = [
            '0' => 'Seleccione una opción',
            '1' => 'Petroperu',
            '2' => 'Contratista',
            '3' => 'Cliente',
            '99' => 'Otros',
        ];
        $form->addSelect('stakeholders', $plugin->get_lang('Stakeholder'), $stakeholders);
        $contratingCompanies = $plugin->contratingCompaniesModel()->getDataCompanies();
        $form->addSelect('name_company', $plugin->get_lang('Company_RUC'), $contratingCompanies);
        $position = $plugin->getPositions(2, true);
        $form->addSelect('position_company', $plugin->get_lang('Position'), $position);

        $area = $plugin->getPetroArea(true);
        $form->addSelect('area', $plugin->get_lang('Sede'), $area);
        $form->addText('code_reference', $plugin->get_lang('CodeReference'));
        $form->addHidden('user_id', $idUser);
        $form->addButtonSave($plugin->get_lang('SaveUserExtra'));
        $form->setDefaults($user);

        if ($form->validate()) {
            $values = $form->exportValues();
            $ruc = $plugin->getRUC($values['name_company']);
            $values['ruc'] = $ruc;

            $existsUserProikos = $plugin->getExistsUserProikos($values['user_id']);
            if($existsUserProikos != 0){
                $plugin->updateProikosUser($values);
            } else {
                $plugin->insertProikosUser($values);
            }
            header('Location: '.api_get_path(WEB_PLUGIN_PATH).'proikos/src/users_management.php?action=list');
        }
        $tpl->assign('form_edit', $form->returnForm());

        break;
    case 'list':
        $actionLinks .= Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_PLUGIN_PATH) . 'proikos/start.php'
        );
        $actionLinks .= Display::url(
            Display::return_icon('export_excel.png', $plugin->get_lang('UsersSessionReport'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/users_management.php?action=report'
        );
        $perPage = 50;
        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $totalUsers = $plugin->countUsers($search);
        $totalPages = max(1, (int) ceil($totalUsers / $perPage));
        $page = min($page, $totalPages);

        $users = $plugin->getUsers($search, ($page - 1) * $perPage, $perPage);

        $tpl->assign('users', $users);
        $tpl->assign('search', Security::remove_XSS($search));
        $tpl->assign('current_page', $page);
        $tpl->assign('total_pages', $totalPages);
        $tpl->assign('page_start', max(1, $page - 3));
        $tpl->assign('page_end', min($totalPages, $page + 3));
        $tpl->assign('total_users', $totalUsers);
        $tpl->assign('list_url', api_get_path(WEB_PLUGIN_PATH).'proikos/src/users_management.php?action=list');
        break;
    case 'report':
        $actionLinks = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/users_management.php?action=list'
        );

        $sessionId = isset($_GET['session_id']) ? (int) $_GET['session_id'] : 0;
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        $export = $_GET['export'] ?? '';

        $form = new FormValidator('report_form', 'get', api_get_self());
        $form->addHeader($plugin->get_lang('UsersSessionReport'));

        $tableSession = Database::get_main_table(TABLE_MAIN_SESSION);
        $result = Database::query("SELECT id, name FROM $tableSession ORDER BY name");
        $sessionOptions = [0 => $plugin->get_lang('AllSessions')];
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $sessionOptions[$row['id']] = $row['name'];
        }
        $form->addSelect('session_id', $plugin->get_lang('SessionName'), $sessionOptions);
        $form->addDatePicker('date_from', $plugin->get_lang('DateFrom'));
        $form->addDatePicker('date_to', $plugin->get_lang('DateTo'));
        $form->addHidden('action', 'report');
        $form->addHidden('export', '');
        $form->addButtonSearch($plugin->get_lang('Filter'), 'submit_filter');
        $form->addButton('submit_export', $plugin->get_lang('ExportExcel'), 'file-excel-o', 'success');
        $form->setDefaults([
            'session_id' => $sessionId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);

        $js = "
        <script>
        $(document).ready(function() {
            $('button[name=\"submit_filter\"]').on('click', function() {
                $('input[name=\"export\"]').val('');
            });
            $('button[name=\"submit_export\"]').on('click', function(e) {
                e.preventDefault();
                $('input[name=\"export\"]').val('excel');
                $('#report_form').submit();
            });
        });
        </script>
        ";
        $tpl->assign('js_content', $js);

        $hasFilters = ($sessionId > 0 || !empty($dateFrom) || !empty($dateTo));

        if ($hasFilters) {
            $tableUser = Database::get_main_table(TABLE_MAIN_USER);
            $tableSessionUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);
            $tableUserProikos = Database::get_main_table('plugin_proikos_users');

            $sql = "SELECT
                        u.lastname,
                        u.firstname,
                        u.email,
                        u.username,
                        ppu.ruc_company,
                        ppu.name_company,
                        s.name AS session_name,
                        sru.registered_at
                    FROM $tableSessionUser sru
                    INNER JOIN $tableUser u ON u.id = sru.user_id
                    INNER JOIN $tableSession s ON s.id = sru.session_id
                    LEFT JOIN $tableUserProikos ppu ON ppu.user_id = u.id
                    WHERE sru.relation_type = 0";

            if ($sessionId > 0) {
                $sql .= " AND sru.session_id = $sessionId";
            }
            if (!empty($dateFrom)) {
                $sql .= " AND sru.registered_at >= '".Database::escape_string($dateFrom)." 00:00:00'";
            }
            if (!empty($dateTo)) {
                $sql .= " AND sru.registered_at <= '".Database::escape_string($dateTo)." 23:59:59'";
            }
            $sql .= " ORDER BY s.name, u.lastname, u.firstname";

            $result = Database::query($sql);
            $data = Database::store_result($result, 'ASSOC');

            if ($export === 'excel' && !empty($data)) {
                exportUsersSessionReport($data, $sessionId, $dateFrom, $dateTo);
                exit;
            }

            if (!empty($data)) {
                $table = new HTML_Table(['class' => 'table table-hover table-striped']);
                $headers = [
                    '#',
                    $plugin->get_lang('LastNamesAndFirstNames'),
                    $plugin->get_lang('Email'),
                    $plugin->get_lang('Username'),
                    $plugin->get_lang('ContratingCompanyRUC'),
                    $plugin->get_lang('ContratingCompanyName'),
                    $plugin->get_lang('SessionName'),
                    $plugin->get_lang('RegisteredAt'),
                ];
                $table->setHeaderContents(0, 0, $headers);

                $row = 1;
                foreach ($data as $item) {
                    $col = 0;
                    $table->setCellContents($row, $col++, $row);
                    $table->setCellContents($row, $col++, $item['lastname'].' '.$item['firstname']);
                    $table->setCellContents($row, $col++, $item['email']);
                    $table->setCellContents($row, $col++, $item['username']);
                    $table->setCellContents($row, $col++, $item['ruc_company'] ?: '-');
                    $table->setCellContents($row, $col++, $item['name_company'] ?: '-');
                    $table->setCellContents($row, $col++, $item['session_name']);
                    $table->setCellContents(
                        $row,
                        $col++,
                        $item['registered_at']
                            ? api_convert_and_format_date($item['registered_at'], DATE_TIME_FORMAT_SHORT)
                            : '-'
                    );
                    $row++;
                }

                $tpl->assign('report_table', $table->toHtml());
                $tpl->assign('report_total', count($data));

                $filterInfo = [];
                if ($sessionId > 0) {
                    $filterInfo[] = $plugin->get_lang('SessionName').': <strong>'
                        .Security::remove_XSS($sessionOptions[$sessionId] ?? $sessionId).'</strong>';
                } else {
                    $filterInfo[] = $plugin->get_lang('SessionName').': <strong>'
                        .$plugin->get_lang('AllSessions').'</strong>';
                }
                if (!empty($dateFrom)) {
                    $filterInfo[] = $plugin->get_lang('DateFrom').': <strong>'.api_format_date($dateFrom).'</strong>';
                }
                if (!empty($dateTo)) {
                    $filterInfo[] = $plugin->get_lang('DateTo').': <strong>'.api_format_date($dateTo).'</strong>';
                }
                $tpl->assign('report_filter_info', implode(' | ', $filterInfo));
            } else {
                $message = Display::return_message($plugin->get_lang('NoDataFound'), 'warning');
            }
        } else {
            $message = Display::return_message($plugin->get_lang('SelectReportFilters'), 'info');
        }

        $tpl->assign('form_report', $form->returnForm());
        break;
        default;
}
$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$actionLinks])
);
$tpl->assign('message', $message);
$content = $tpl->fetch('proikos/view/proikos_users.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();

/**
 * Exporta a Excel la lista de usuarios inscritos en sesiones.
 */
function exportUsersSessionReport($data, $sessionId, $dateFrom, $dateTo)
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Usuarios por Sesión');

    $headers = [
        '#',
        'Apellidos y Nombres',
        'Correo',
        'Usuario',
        'RUC Empresa',
        'Nombre Empresa',
        'Sesión',
        'Fecha de Inscripción',
    ];
    $sheet->fromArray($headers, null, 'A1');

    $headerStyle = [
        'font' => ['bold' => true],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'CCCCCC'],
        ],
    ];
    $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
    foreach (range('A', 'H') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $row = 2;
    foreach ($data as $index => $item) {
        $sheet->fromArray([
            $index + 1,
            $item['lastname'].' '.$item['firstname'],
            $item['email'],
            $item['username'],
            $item['ruc_company'] ?: '-',
            $item['name_company'] ?: '-',
            $item['session_name'],
            $item['registered_at']
                ? api_convert_and_format_date($item['registered_at'], DATE_TIME_FORMAT_SHORT)
                : '-',
        ], null, 'A'.$row);
        $row++;
    }

    $filenameParts = ['usuarios_sesion'];
    if ($sessionId > 0) {
        $filenameParts[] = 'sesion_'.$sessionId;
    }
    if (!empty($dateFrom)) {
        $filenameParts[] = 'desde_'.str_replace('-', '', $dateFrom);
    }
    if (!empty($dateTo)) {
        $filenameParts[] = 'hasta_'.str_replace('-', '', $dateTo);
    }
    $filenameParts[] = date('Ymd_His');
    $filename = implode('_', $filenameParts).'.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="'.$filename.'"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
}
