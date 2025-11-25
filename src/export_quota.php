<?php
/* Para /plugin/proikos/export_quota.php */

require_once __DIR__ . '/../config.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Verificar permisos
api_block_anonymous_users();

$plugin = ProikosPlugin::create();
$tpl = new Template($plugin->get_lang('ExportQuota'));

// Obtener parámetros
$company_id = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$export = isset($_GET['export']) ? $_GET['export'] : '';

// Formulario de filtros
$form = new FormValidator('filter_form', 'get', api_get_self());
$form->addHeader($plugin->get_lang('FilterQuota'));

// Select de empresas
$companies = get_contracting_companies();
$totalCompanies = count($companies);
$company_options = [0 => $plugin->get_lang('AllCompanies').' - '.$totalCompanies];
foreach ($companies as $company) {
    $company_options[$company['id']] = $company['name'] . ' - ' .$company['ruc'];
}
$form->addSelect('company_id', $plugin->get_lang('Company'), $company_options);

// Filtros de fecha
$form->addDatePicker('date_from', $plugin->get_lang('DateFrom'));
$form->addDatePicker('date_to', $plugin->get_lang('DateTo'));
// Campo oculto para el export
$form->addHidden('export', '');
// Botones
$form->addButtonSearch($plugin->get_lang('Filter'), 'submit_filter');
$form->addButton('submit_export', $plugin->get_lang('ExportExcel'), 'file-excel-o', 'success');

$js = "
<script>
$(document).ready(function() {
    // Botón de filtrar
    $('button[name=\"submit_filter\"]').on('click', function(e) {
        $('input[name=\"export\"]').val('');
    });

    // Botón de exportar
    $('button[name=\"submit_export\"]').on('click', function(e) {
        e.preventDefault();
        $('input[name=\"export\"]').val('excel');
        $('#filter_form').submit();
    });
});
</script>
";

// Validar que al menos haya un filtro aplicado
$has_filters = ($company_id > 0 || !empty($date_from) || !empty($date_to));

if ($has_filters) {

    // Construir consulta SQL con JOINs a tablas de Chamilo
    $sql = "SELECT
                pqc.id,
                pqc.contrating_company_id,
                cc.name as company_name,
                cc.ruc,
                pqc.created_at,
                pqcd.session_category_id,
                sc.name as session_category_name,
                pqcd.user_quota as quota_total,
                pqcd.price_unit,
                pqcd.session_mode,
                CASE
                    WHEN pqcd.session_mode = 1 THEN 'Asincrónico'
                    WHEN pqcd.session_mode = 2 THEN 'Sincrónico'
                    ELSE CONCAT('Modo ', pqcd.session_mode)
                END as session_mode_text,
                pqcs.session_id,
                s.name as session_name,
                pqcs.user_quota,
                pqcs.created_user_id as gestor,
                CONCAT(u.firstname, ' ', u.lastname) as gestor_name
            FROM plugin_proikos_contrating_companies_quota_cab pqc
            INNER JOIN plugin_proikos_contrating_companies_quota_det pqcd
                ON pqcd.cab_id = pqc.id
            INNER JOIN plugin_proikos_contrating_companies_quota_session pqcs
                ON pqcd.id = pqcs.det_id
            LEFT JOIN plugin_proikos_contrating_companies cc
                ON pqc.contrating_company_id = cc.id
            LEFT JOIN session s
                ON pqcs.session_id = s.id
            LEFT JOIN session_category sc
                ON pqcd.session_category_id = sc.id
            LEFT JOIN user u
                ON pqcs.created_user_id = u.id
            WHERE 1=1";

    // Agregar filtro de empresa si se seleccionó una específica
    if ($company_id > 0) {
        $sql .= " AND pqc.contrating_company_id = ".intval($company_id);
    }

    // Agregar filtros de fecha si existen
    if (!empty($date_from)) {
        $sql .= " AND pqc.created_at >= '".Database::escape_string($date_from)." 00:00:00'";
    }
    if (!empty($date_to)) {
        $sql .= " AND pqc.created_at <= '".Database::escape_string($date_to)." 23:59:59'";
    }

    $sql .= " ORDER BY pqc.contrating_company_id, pqc.created_at DESC";

    $result = Database::query($sql);
    $data = Database::store_result($result, 'ASSOC');

    // Si se solicita exportar
    if ($export === 'excel' && !empty($data)) {
        export_to_excel($data, $company_id, $date_from, $date_to);
        exit;
    }

    // Mostrar tabla de resultados
    if (!empty($data)) {
        $table = new HTML_Table(['class' => 'table table-hover table-striped']);

        // Encabezados actualizados
        $headers = [
            $plugin->get_lang('Id'),
            $plugin->get_lang('CompanyName'),
            $plugin->get_lang('CompanyRuc'),
            $plugin->get_lang('CreatedAt'),
            $plugin->get_lang('SessionCategoryName'),
            $plugin->get_lang('SessionName'),
            $plugin->get_lang('SessionModeText'),
            $plugin->get_lang('QuotaTotal'),
            $plugin->get_lang('PriceUnit'),
            $plugin->get_lang('UserQuota'),
            $plugin->get_lang('GestorName')
        ];
        $table->setHeaderContents(0, 0, $headers);

        // Datos
        $row = 1;
        foreach ($data as $item) {
            $col = 0;
            $table->setCellContents($row, $col++, $item['id']);
            $table->setCellContents($row, $col++, $item['company_name'] ?: 'N/A');
            $table->setCellContents($row, $col++, $item['ruc'] ?: '-');
            $table->setCellContents($row, $col++, api_convert_and_format_date($item['created_at']));
            $table->setCellContents($row, $col++, $item['session_category_name'] ?: 'Sin categoría');
            $table->setCellContents($row, $col++, $item['session_name'] ?: 'N/A');
            $table->setCellContents($row, $col++, $item['session_mode_text']);
            $table->setCellContents($row, $col++, $item['quota_total']);
            $table->setCellContents($row, $col++, number_format($item['price_unit'], 2));
            $table->setCellContents($row, $col++, $item['user_quota']);
            $table->setCellContents($row, $col++, $item['gestor_name'] ?: 'N/A');
            $row++;
        }

        $tpl->assign('table', $table->toHtml());
        $tpl->assign('total_records', count($data));

        // Información de filtros aplicados
        $filter_info = [];
        if ($company_id > 0) {
            $company_name = $company_options[$company_id];
            $filter_info[] = $plugin->get_lang('Company').': <strong>'.$company_name.'</strong>';
        } else {
            $filter_info[] = $plugin->get_lang('Company').': <strong>'.$plugin->get_lang('AllCompanies').'</strong>';
        }
        if (!empty($date_from)) {
            $filter_info[] = $plugin->get_lang('DateFrom').': <strong>'.api_format_date($date_from).'</strong>';
        }
        if (!empty($date_to)) {
            $filter_info[] = $plugin->get_lang('DateTo').': <strong>'.api_format_date($date_to).'</strong>';
        }
        $tpl->assign('filter_info', implode(' | ', $filter_info));

    } else {
        $tpl->assign('message', Display::return_message($plugin->get_lang('NoDataFound'), 'warning'));
    }
} else {
    $tpl->assign('message', Display::return_message($plugin->get_lang('SelectFilters'), 'info'));
}

$tpl->assign('form', $form->returnForm());
$tpl->assign('js_content', $js);
$content = $tpl->fetch('proikos/view/proikos_export_quota.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();

/**
 * Función para exportar a Excel
 */
function export_to_excel($data, $company_id, $date_from, $date_to) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Reporte Cuotas');

    // Encabezados
    $headers = [
        'ID',
        'ID Empresa',
        'Nombre Empresa',
        'Fecha Creación',
        'ID Categoría Sesión',
        'Categoría Sesión',
        'ID Sesión',
        'Nombre Sesión',
        'ID Modo Sesión',
        'Modo Sesión',
        'Cuota Total',
        'Precio Unitario',
        'Cuota Usuario',
        'ID Gestor',
        'Nombre Gestor'
    ];

    $sheet->fromArray($headers, NULL, 'A1');

    // Estilo para encabezados
    $headerStyle = [
        'font' => ['bold' => true],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'CCCCCC']
        ]
    ];

    $sheet->getStyle('A1:O1')->applyFromArray($headerStyle);

    // Auto ajustar columnas
    foreach(range('A','O') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Datos
    $row = 2;
    foreach ($data as $item) {
        $dataRow = [
            $item['id'],
            $item['contrating_company_id'],
            $item['company_name'] ?: 'N/A',
            $item['created_at'],
            $item['session_category_id'],
            $item['session_category_name'] ?: 'Sin categoría',
            $item['session_id'],
            $item['session_name'] ?: 'N/A',
            $item['session_mode'],
            $item['session_mode_text'],
            $item['quota_total'],
            'S/ ' . number_format($item['price_unit'], 2),
            $item['user_quota'],
            $item['gestor'],
            $item['gestor_name'] ?: 'N/A'
        ];
        $sheet->fromArray($dataRow, NULL, 'A'.$row);
        $row++;
    }

    // Nombre del archivo según filtros
    $filename_parts = ['reporte_cuotas'];
    if ($company_id > 0) {
        $filename_parts[] = 'empresa_'.$company_id;
    } else {
        $filename_parts[] = 'todas_empresas';
    }
    if (!empty($date_from) || !empty($date_to)) {
        if (!empty($date_from)) {
            $filename_parts[] = 'desde_'.str_replace('-', '', $date_from);
        }
        if (!empty($date_to)) {
            $filename_parts[] = 'hasta_'.str_replace('-', '', $date_to);
        }
    }
    $filename_parts[] = date('Ymd_His');
    $filename = implode('_', $filename_parts).'.xlsx';

    // Headers para descarga
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="'.$filename.'"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
}

/**
 * Función auxiliar para obtener empresas
 */
function get_contracting_companies() {
    $sql = "SELECT id, name, ruc FROM plugin_proikos_contrating_companies ORDER BY name";
    $result = Database::query($sql);
    return Database::store_result($result, 'ASSOC');
}
