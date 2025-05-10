<?php

require_once __DIR__ . '/../config.php';
api_block_anonymous_users();
$action = $_GET['action'] ?? null;
$plugin = ProikosPlugin::create();
$allow = api_is_platform_admin() || api_is_teacher();

if (!$allow) {
    api_not_allowed(true);
}

$tool_name = 'Asignar cupos a sesiones';
$tpl = new Template($tool_name);
$actionLinks = '';
$actionLinks .= Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies_quota_cab.php?company_id=' . $_GET['company_id']
);
$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$actionLinks])
);

// ------------------------
// Form
// ------------------------
$form = new FormValidator(
    'proikos_contrating_companies_assign_quota_to_session',
    'post',
    api_get_self() . '?company_id=' . Security::remove_XSS($_GET['company_id']) .
    '&action=' . $action . '&quota_cab_id=' . Security::remove_XSS($_GET['quota_cab_id'])
);
$form->addHeader($plugin->get_lang('Company'));
$empresa = $plugin->contratingCompaniesModel()->getData($_GET['company_id']);
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

$form->addHeader($plugin->get_lang('Asignar cupos'));
$deleteIcon = Display::return_icon(
    'delete.png',
    get_lang('Delete'),
    [],
    ICON_SIZE_SMALL
);
$detalle = $plugin->contratingCompaniesQuotaCabModel()->getDetails($_GET['quota_cab_id']);
$activeSessions = SessionManager::getSessionsForAdmin(
    api_get_user_id(),
    [],
    false,
    [],
    'active'
);

if (empty($activeSessions)) {
    $activeSessions = [];
}

$sessionsList = json_encode($activeSessions);

if ($form->isSubmitted()) {
    $formValues = $form->getSubmitValues();
    $sessionConfig = $formValues['session'];

    if (!empty($sessionConfig)) {
        foreach ($sessionConfig as $key => $value) {
            if (empty($value['session_id']) || empty($value['quota'])) {
                continue;
            }

            // save
        }
    }
}

foreach ($detalle as $key => $det) {
    $form->addHtml(<<<EOT
        <div class="form-group">
            <div class="col-sm-2"></div>
            <div class="col-sm-8">
                <div class="card">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width: 500px;">{$plugin->get_lang('SessionCategory')}</th>
                                <th class="text-center" style="width: 150px;">{$plugin->get_lang('ContratingCompanyUserQuota')}</th>
                                <th></th>
                            </tr>
                            <tr>
                                <th style="width: 500px;">{$det['category_name']}</th>
                                <th class="text-center" style="width: 150px;">
                                    <input type="text" readonly class="form-control" value="{$det['quota']}" style="text-align: right;">
                                </th>
                                <th style="text-align: center;">
                                    <button type="button" class="btn btn-primary" id="add_session" onclick="addNewRow(this,'{$key}', '{$det['id']}', '{$det['session_category_id']}')">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="session_detail_container">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-sm-2"></div>
        </div>
        <script>

        </script>
EOT
    );
}
$form->addHtml(<<<EOT
<script>
const sessionsList = JSON.parse('{$sessionsList}');

function addNewRow(self, itemIndex, detId, session_category_id) {
    // filter sessionList where session_category_id is equal to session_category_id
    const sessionsByCategory = sessionsList.filter(session => session.session_category_id == session_category_id);
    const tableBody = self.closest('table').querySelector('tbody');
    const newRow = document.createElement('tr');
    const sessionsSelect = document.createElement('select');
    sessionsSelect.name = 'session[' + itemIndex + '][session_id]';
    sessionsSelect.className = 'form-control';

    sessionsSelect.innerHTML = '<option value="">{$plugin->get_lang('SelectSession')}</option>';
    sessionsByCategory.forEach(session => {
        const option = document.createElement('option');
        option.value = session.id;
        option.text = session.name;
        sessionsSelect.appendChild(option);
    });

    newRow.innerHTML = `
        <td>
            ` + sessionsSelect.outerHTML + `
        </td>
        <td>
            <input type="hidden" name="session[` + itemIndex + `][det_id]" class="form-control text-right" value="` + detId +`">
            <input type="number" name="session[` + itemIndex + `][quota]" class="form-control text-right">
        </td>
        <td style="text-align: center;">
            <a href="javascript:void(0);" id="remove_item_` + itemIndex + `">
                {$deleteIcon}
            </a>
        </td>`;
    tableBody.appendChild(newRow);

    const sessionSelectElement = newRow.querySelector('select[name="session[' + itemIndex + '][session_id]"]');
    $(sessionSelectElement).selectpicker({
        width: '500px',
        liveSearch: true
    });
}
</script>
EOT
);

$form->addButtonSave($plugin->get_lang('SaveContratingCompanyDetailsQuota'));

$tpl->assign('form', $form->returnForm());
$content = $tpl->fetch('proikos/view/proikos_contrating_companies_assign_quota_to_session.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
