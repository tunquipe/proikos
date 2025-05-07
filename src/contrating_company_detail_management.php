<?php

require_once __DIR__ . '/../config.php';
api_block_anonymous_users();
$action = $_GET['action'] ?? null;
$plugin = ProikosPlugin::create();
$allow = api_is_platform_admin() || api_is_teacher();

if (!$allow) {
    api_not_allowed(true);
}

$id = $_GET['id'];
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $plugin->deleteContratingCompanyDetail($_GET['item_id']);
    $url = api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_company_detail_management.php?id=' . $id;
    header('Location: ' . $url);
}

if (empty($id)) {
    api_not_allowed(true);
}

$message = '';
$actionLinks = '';
$actionLinks .= Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_company_management.php'
);

$empresa = $plugin->getContratingCompanyById($id);
$tool_name = 'Gestionar detalles de la empresa';
$tpl = new Template($tool_name);
$items = $plugin->getContratingCompanyDetailById($id);

// ------------------------
// Form
// ------------------------
$form = new FormValidator(
    'add_contrating_company_details',
    'post',
    api_get_self() . '?id=' . Security::remove_XSS($_GET['id'])
);
$form->addHeader($plugin->get_lang('AddContratingCompanyDetailsQuota'));
$form->addText('company_name', $plugin->get_lang('CompanyRuc'), false, [
    'value' => $empresa['ruc'],
    'disabled' => 'disabled'
]);
$form->addText('company_name', $plugin->get_lang('CompanyName'), false, [
    'value' => $empresa['name'],
    'disabled' => 'disabled'
]);
$form->addText('company_admin_name', $plugin->get_lang('ContratingAdminName'), false, [
    'value' => $empresa['admin_name'],
    'disabled' => 'disabled'
]);
$form->addText('company_total_user_quota', $plugin->get_lang('CompanyTotalUserQuota'), false, [
    'value' => $empresa['total_user_quota'],
    'disabled' => 'disabled'
]);

$form->addNumeric('user_quota', $plugin->get_lang('ContratingCompanyUserQuota'), [], true);

$form->addElement('date_picker', 'validity', $plugin->get_lang('Validity'));
$form->addRule('validity', $plugin->get_lang('ValidityRequired'), 'required');

$typeCourse = json_encode([
    '0' => 'Seleccione un tipo de curso',
    '1' => 'Asíncrono',
    '2' => 'Síncrono'
]);
$coursesByType = json_encode([
    '1' => [
        '1' => 'Curso 1',
        '2' => 'Curso 2',
    ],
    '2' => [
        '3' => 'Curso 3',
        '4' => 'Curso 4',
    ]
]);
$deleteIcon = Display::return_icon(
    'delete.png',
    get_lang('Delete'),
    [],
    ICON_SIZE_SMALL
);
$form->addHtml(
    <<<EOT
    <div class="form-group">
        <div class="col-sm-2"></div>
        <div class="col-sm-8">
            <div class="card">
                <table class="table table-striped" style="margin-bottom: 0px;">
                    <thead>
                        <tr>
                            <th>{$plugin->get_lang('TypeCourse')}</th>
                            <th>{$plugin->get_lang('Course')}</th>
                            <th>{$plugin->get_lang('ContratingCompanyUserQuota')}</th>
                            <th style="text-align: center;">
                                <button type="button" class="btn btn-primary" id="add_course_session">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="course-detail-container">
                    </tbody>
                    <tfoot style="display: none;">
                        <tr>
                            <td></td>
                            <td style="text-align: right;">
                                <label for="total_quota" class="control-label">
                                    Total Nº Cupos
                                </label>
                            </td>
                            <td>
                                <input type="number" name="total_quota" id="total_quota" readonly class="form-control">
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="col-sm-2"></div>
    </div>
    <script>
        let index = 0;
        function addNewRow() {
            const tableBody = document.getElementById('course-detail-container');
            const newRow = document.createElement('tr');

            // ----- Create the select element for typeCourse -----
            const typeCourse = JSON.parse('{$typeCourse}');
            const courses = JSON.parse('{$coursesByType}');
            const typeCourseSelect = document.createElement('select');
            typeCourseSelect.name = 'course_detail[' + index + '][type]';
            typeCourseSelect.id = 'course_type';
            typeCourseSelect.className = 'form-control';
            typeCourseSelect.dataset.index = index;
            for (const [value, text] of Object.entries(typeCourse)) {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = text;
                typeCourseSelect.appendChild(option);
            }

            // ----- Create the select element for course -----
            const courseSelect = document.createElement('select');
            courseSelect.name = 'course_detail[' + index + '][course]';
            courseSelect.id = 'course_session';
            courseSelect.className = 'form-control';
            courseSelect.innerHTML = '<option value="0">Seleccione un curso</option>';

            newRow.innerHTML = `
                <td>
                    ` + typeCourseSelect.outerHTML + `
                </td>
                <td>
                    ` + courseSelect.outerHTML + `
                </td>
                <td>
                    <input type="number" name="course_detail[` + index + `][quota]" id="quota" class="form-control">
                </td>
                <td style="text-align: center;">
                    <a href="javascript:void(0);" id="remove_item_` + index + `">
                        {$deleteIcon}
                    </a>
                </td>`;
            tableBody.appendChild(newRow);
            const quotaInput = newRow.querySelector('input[name="course_detail[' + index + '][quota]"]');
            quotaInput.addEventListener('input', function() {
                updateTotalQuota();
            });

            const deleteButton = newRow.querySelector('a[id="remove_item_' + index + '"]');
            deleteButton.addEventListener('click', function() {
                tableBody.removeChild(newRow);
                updateTotalQuota();
            });

            const typeCourseSelectElement = newRow.querySelector('select[name="course_detail[' + index + '][type]"]');
            typeCourseSelectElement.addEventListener('change', function() {
                const selectedType = this.value;
                const selfIndex = this.dataset.index;
                const courseSelectElement = newRow.querySelector('select[name="course_detail[' + selfIndex + '][course]"]');

                $(courseSelectElement).empty();
                $(courseSelectElement).append(new Option('Seleccione un curso', '0'));
                if (courses[selectedType]) {
                    for (const [value, text] of Object.entries(courses[selectedType])) {
                        $(courseSelectElement).append(new Option(text, value));
                    }
                }
                $(courseSelectElement).selectpicker('refresh');
            });

            const courseSelectElement = newRow.querySelector('select[name="course_detail[' + index + '][course]"]');
            $(courseSelectElement).selectpicker({
                liveSearch: true,
                width: '200px',
            });

            index++;
        }

        function updateTotalQuota() {
            const quotaInputs = document.querySelectorAll('input[name^="course_detail["][name$="[quota]"]');
            let totalQuota = 0;
            quotaInputs.forEach(input => {
                const quotaValue = parseInt(input.value) || 0;
                totalQuota += quotaValue;
            });
            document.getElementById('total_quota').value = totalQuota;

            if (totalQuota > 0) {
                document.querySelector('tfoot').style.display = 'table-row-group';
            } else {
                document.querySelector('tfoot').style.display = 'none';
            }
        }

        document.getElementById('add_course_session').addEventListener('click', addNewRow);
    </script>
EOT
);
$form->addButtonSave($plugin->get_lang('SaveContratingCompanyDetailsQuota'));

if ($form->validate()) {
    $values = $form->getSubmitValues();
    $params = [
        'cab_id' => $id,
        'user_id' => api_get_user_id(),
        'user_quota' => $values['user_quota'],
        'event' => ProikosPlugin::EVENT_ADD_QUOTA
    ];
    $plugin->addContratingCompanyDetail($params);

    $message = Display::return_message(
        $plugin->get_lang('ContratingCompanyDetailsQuotaAdded'),
        'success'
    );

    $url = api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_company_detail_management.php?id=' . $id;
    header('Location: ' . $url);
}

$tpl->assign('form', $form->returnForm());
// ------------------------
// End Form
// ------------------------

$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$actionLinks])
);
$tpl->assign('message', $message);
$tpl->assign('items', $items);
$content = $tpl->fetch('proikos/view/proikos_contrating_company_detail.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
