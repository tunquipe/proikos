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
    $plugin->contratingCompaniesQuotaCabModel()->delete($_GET['item_id']);
    $url = api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies_quota_cab.php?id=' . $id;
    header('Location: ' . $url);
}

if (empty($id)) {
    api_not_allowed(true);
}

$message = '';
$actionLinks = '';
$actionLinks .= Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies.php'
);

$empresa = $plugin->contratingCompaniesModel()->getData($id);
$tool_name = 'Gestionar detalles de la empresa';
$tpl = new Template($tool_name);
$items = $plugin->contratingCompaniesQuotaCabModel()->getData($id);

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

$defaultIndex = 0;
$defaultCourseDetail = [];
$courseDetailHasError = false;
$courseDetailHasErrorClass = '';
$courseDetailErrorMessage = '';

if ($form->isSubmitted()) {
    $formValues = $form->getSubmitValues();
    $defaultCourseDetail = $formValues['course_detail'] ?? [];

    if (empty($defaultCourseDetail)) {
        $courseDetailHasErrorClass = 'has-error';
        $courseDetailErrorMessage = $plugin->get_lang('CoursesConfigurationRequired');
    } else {
        foreach ($defaultCourseDetail as $key => $value) {
            if (empty($value['type']) || empty($value['course']) || empty($value['quota'])) {
                $courseDetailHasError = true;
            }

            if ($key > $defaultIndex) {
                $defaultIndex = $key;
            }
        }

        if ($defaultIndex >= 0) {
            $defaultIndex += 1;
        }

        if ($courseDetailHasError) {
            $courseDetailHasErrorClass = 'has-error';
            $courseDetailErrorMessage = $plugin->get_lang('CoursesConfigurationPleaseCompleteAllFields');
        }
    }
}
$defaultCourseDetail = json_encode($defaultCourseDetail);

$form->addHtml(
    <<<EOT
    <div class="form-group {$courseDetailHasErrorClass}">
        <label for="configure_courses" class="col-sm-2 control-label">
            <span class="form_required">*</span>
            Configurar Cupos
        </label>
        <div class="col-sm-8">
            <div class="card">
                <table class="table table-striped" style="margin-bottom: 0px;">
                    <thead>
                        <tr>
                            <th>{$plugin->get_lang('TypeCourse')}</th>
                            <th style="width: 200px;">{$plugin->get_lang('Course')}</th>
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
            <span class="help-inline help-block">{$courseDetailErrorMessage}</span>
        </div>
        <div class="col-sm-2"></div>
    </div>
    <script>
        let index = {$defaultIndex};
        const typeCourse = JSON.parse('{$typeCourse}');
        const courses = JSON.parse('{$coursesByType}');

        function addNewRow(itemIndex = null, itemType = null, itemCourse = null, itemQuota = null) {
            const tableBody = document.getElementById('course-detail-container');
            const newRow = document.createElement('tr');
            itemIndex = itemIndex === null ? index : itemIndex;

            // ----- Create the select element for typeCourse -----
            const typeCourseSelect = document.createElement('select');
            typeCourseSelect.name = 'course_detail[' + itemIndex + '][type]';
            typeCourseSelect.id = 'course_type';
            typeCourseSelect.className = 'form-control';
            typeCourseSelect.dataset.index = itemIndex;
            for (const [value, text] of Object.entries(typeCourse)) {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = text;
                typeCourseSelect.appendChild(option);
            }

            // ----- Create the select element for course -----
            const courseSelect = document.createElement('select');
            courseSelect.name = 'course_detail[' + itemIndex + '][course]';
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
                    <input type="number" name="course_detail[` + itemIndex + `][quota]" id="quota" class="form-control">
                </td>
                <td style="text-align: center;">
                    <a href="javascript:void(0);" id="remove_item_` + itemIndex + `">
                        {$deleteIcon}
                    </a>
                </td>`;
            tableBody.appendChild(newRow);
            const quotaInput = newRow.querySelector('input[name="course_detail[' + itemIndex + '][quota]"]');
            quotaInput.addEventListener('input', function() {
                updateTotalQuota();
            });

            if (itemQuota != null) {
                quotaInput.value = itemQuota;
                quotaInput.dispatchEvent(new Event('input'));
            }

            const deleteButton = newRow.querySelector('a[id="remove_item_' + itemIndex + '"]');
            deleteButton.addEventListener('click', function() {
                tableBody.removeChild(newRow);
                updateTotalQuota();
            });

            const typeCourseSelectElement = newRow.querySelector('select[name="course_detail[' + itemIndex + '][type]"]');
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

            $(typeCourseSelectElement).selectpicker({
                width: '285px'
            });

            if (itemType != null) {
                typeCourseSelectElement.value = itemType;
                typeCourseSelectElement.dispatchEvent(new Event('change'));
            }

            const courseSelectElement = newRow.querySelector('select[name="course_detail[' + itemIndex + '][course]"]');
            $(courseSelectElement).selectpicker({
                liveSearch: true,
                width: '200px',
            });

            if (itemCourse != null) {
                courseSelectElement.value = itemCourse;
                courseSelectElement.dispatchEvent(new Event('change'));
            }
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

        document.getElementById('add_course_session').addEventListener('click', function() {
            addNewRow();
            index++;
        });

        // Add initial row if needed
        let defaultCourseDetail = JSON.parse('{$defaultCourseDetail}');
        if (Object.keys(defaultCourseDetail)?.length > 0) {
            for (const [key, value] of Object.entries(defaultCourseDetail)) {
                addNewRow(parseInt(key), value.type, value.course, value.quota);
            }
        }
    </script>
EOT
);
$form->addElement('date_picker', 'validity', $plugin->get_lang('Validity'));
$form->addRule('validity', $plugin->get_lang('ValidityRequired'), 'required');
$form->addButtonSave($plugin->get_lang('SaveContratingCompanyDetailsQuota'));

if ($form->validate() && $courseDetailHasError === false) {
    $values = $form->getSubmitValues();

    // Save cab
    $params = [
        'contrating_company_id' => $id,
        'created_user_id' => api_get_user_id()
    ];
    $plugin->contratingCompaniesQuotaCabModel()->save($params);

    // save det
    foreach ($values['course_detail'] as $key => $value) {
        $params = [
            'cab_id' => $id,
            'type_course_id' => $value['type'],
            'course_id' => $value['course'],
            'user_quota' => $value['quota'],
            'created_user_id' => api_get_user_id(),
        ];
        $plugin->contratingCompaniesQuotaDetModel()->save($params);
    }

    $message = Display::return_message(
        $plugin->get_lang('ContratingCompanyDetailsQuotaAdded'),
        'success'
    );

    $url = api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies_quota_cab.php?id=' . $id;
    header('Location: ' . $url);
}

init_form:
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
