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

$sessionDistributions = [];
if (!empty($detalle)) {
    foreach ($detalle as $det) {
        $sessionDistributions[$det['id']] = $plugin->contratingCompaniesQuotaSessionModel()->getDistributionByDetId($det['id']);
    }
}

$sessionFormValues = [];
$hasErrors = false;
$erroMessage = 'Completar correctamente todos los campos';
$errorsMessage = [];

if ($form->isSubmitted()) {
    $formValues = $form->getSubmitValues();
    $sessionFormValues = $formValues['session'];

    if (!empty($sessionFormValues)) {
        foreach ($sessionFormValues as $key => $value) {
            if (empty($value['session_id']) || (empty($value['user_quota']) && $value['user_quota'] != 0) || $value['user_quota'] < 0) {
                $hasErrors = true;
            }

            //$sessionInfo = api_get_session_info($value['session_id']);
            //$maxUsers = $sessionInfo['maximum_users'] ?? 0;
        }
    }
}

if ($form->validate() && false === $hasErrors) {
    $sessionFormValues = $form->getSubmitValues();
    $sessionFormValues = $sessionFormValues['session'];

    $itemsToUpdate = [];
    foreach ($sessionFormValues as $key => $value) {
        // update
        if (isset($value['id']) && !empty($value['id'])) {
            $itemsToUpdate[] = $value['id'];
            $plugin->contratingCompaniesQuotaSessionModel()->update([
                'id' => $value['id'],
                'session_id' => $value['session_id'],
                'user_quota' => $value['user_quota']
            ]);
        } else {
            // save
            $plugin->contratingCompaniesQuotaSessionModel()->save([
                'det_id' => $value['det_id'],
                'session_id' => $value['session_id'],
                'user_quota' => $value['user_quota']
            ]);
        }
    }

    // delete
    // check details that are not in the $itemsToUpdate array
    $itemsToDelete = [];
    foreach ($sessionDistributions as $key => $value) {
        foreach ($value as $session) {
            if (!in_array($session['id'], $itemsToUpdate)) {
                $itemsToDelete[] = $session['id'];
            }
        }
    }
    $plugin->contratingCompaniesQuotaSessionModel()->delete($itemsToDelete);

    $url = api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies_assign_quota_to_session.php?company_id=' . $_GET['company_id'] . '&action=assign_quota_to_session&quota_cab_id=' . $_GET['quota_cab_id'];
    header('Location: ' . $url);
}

$sessionsList = json_encode($activeSessions);
$sessionFormValues = json_encode($sessionFormValues);
$form->addHtml('<div id="root_asignar_cupos"></div>');

$detalle = json_encode($detalle ?? []);
$sessionDistributions = json_encode($sessionDistributions);
$form->addHtml(<<<EOT
<script>
const detalle = JSON.parse('{$detalle}');
const sessionsList = JSON.parse('{$sessionsList}');
const sessionDistributions = JSON.parse('{$sessionDistributions}');
let lastIndex = 0;

const uuId = function() {
    let now = new Date();
    let time = now.getTime();

    return 'id-' + time + Math.floor(Math.random() * 1000) + '-' + Math.floor(Math.random() * 1000);
}

if (detalle?.length > 0) {
    const sessionDetailContainer = document.getElementById('root_asignar_cupos');
    detalle.forEach((item, index) => {
        const uniqueId = uuId();
        const containerId = 'session_detail_container_' + uniqueId;
        const tableBodyId = 'table_body_' + uniqueId;
        const plusButtonId = 'plus_button_' + uniqueId;

        sessionDetailContainer.innerHTML = `<div class="form-group" id="` + containerId + `">
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
                                <th style="width: 500px;">` + item.category_name + `</th>
                                <th class="text-center" style="width: 150px;">
                                    <input type="text" readonly class="form-control" value="` + item.quota + `" style="text-align: right;">
                                </th>
                                <th style="text-align: center;">
                                    <button type="button" class="btn btn-primary" id="` + plusButtonId + `">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="` + tableBodyId + `">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-sm-2"></div>
        </div>`;

        sessionDistributions[item.id].forEach((session, distIndex) => {
            addNewRow(distIndex, tableBodyId, item.session_category_id, session.det_id, session.id, session.session_id, session.user_quota);
            lastIndex = distIndex;
        });

        document.getElementById(plusButtonId).addEventListener('click', function() {
            lastIndex++;
            const itemIndex = parseInt(lastIndex);
            addNewRow(itemIndex, tableBodyId, item.session_category_id, item.id);
        });
    });
}

function addNewRow(itemIndex, tableBodyId, itemSessionCategoryId, itemDetId = null, itemId = null, itemSessionId = null, itemUserQuota = null) {
    const sessionsByCategory = sessionsList.filter(session => session.session_category_id == itemSessionCategoryId);
    const tableBody = document.getElementById(tableBodyId);
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
            <input type="hidden" name="session[` + itemIndex + `][id]" class="form-control text-right" value="` + ( itemId ?? '') +`">
            <input type="hidden" name="session[` + itemIndex + `][det_id]" class="form-control text-right" value="` + ( itemDetId ?? '') +`">
            <input type="number" name="session[` + itemIndex + `][user_quota]" class="form-control text-right">
        </td>
        <td style="text-align: center;">
            <a href="javascript:void(0);" id="remove_item_` + itemIndex + `">
                {$deleteIcon}
            </a>
        </td>`;
    tableBody.appendChild(newRow);

    const deleteButton = newRow.querySelector('a[id="remove_item_' + itemIndex + '"]');
    deleteButton.addEventListener('click', function() {
        tableBody.removeChild(newRow);
    });

    const sessionSelectElement = newRow.querySelector('select[name="session[' + itemIndex + '][session_id]"]');
    $(sessionSelectElement).selectpicker({
        width: '500px',
        liveSearch: true
    });

    if (itemSessionId !== null) {
        sessionSelectElement.value = itemSessionId;
        sessionSelectElement.dispatchEvent(new Event('change'));
    }

    if (itemDetId !== null && itemSessionId != null) {
        document.querySelector('input[name="session[' + itemIndex + '][det_id]"]').value = itemDetId;
    }

    if (itemSessionId !== null) {
        document.querySelector('select[name="session[' + itemIndex + '][session_id]"]').value = itemSessionId;
    }

    if (itemUserQuota !== null) {
        document.querySelector('input[name="session[' + itemIndex + '][user_quota]"]').value = itemUserQuota;
    }
}

let sessionValues = JSON.parse('{$sessionFormValues}');
if (sessionValues && Object.keys(sessionValues)?.length > 0) {
    for (const [key, value] of Object.entries(sessionValues)) {
        //addNewRow(parseInt(key));
    }
}
</script>
EOT
);

$form->addButtonSave($plugin->get_lang('SaveContratingCompanyDetailsQuota'));

$tpl->assign('form', $form->returnForm());
$content = $tpl->fetch('proikos/view/proikos_contrating_companies_assign_quota_to_session.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
