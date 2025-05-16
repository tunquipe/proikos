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
$actionLinks .= Display::url(
    Display::return_icon('home.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies.php'
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
$cabecera = $plugin->contratingCompaniesQuotaCabModel()->getData($_GET['quota_cab_id']);
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
$form->addText('company_total_user_quota', $plugin->get_lang('ContratingCompanyUserQuota'), false, [
    'value' => $cabecera['total_user_quota'],
    'disabled' => 'disabled'
]);
$form->addElement('text', 'validity_date', $plugin->get_lang('Validity'), [
    'value' => $cabecera['vigency_date_es'],
    'disabled' => 'disabled'
]);

$deleteIcon = Display::return_icon(
    'delete.png',
    get_lang('Delete'),
    [],
    ICON_SIZE_SMALL
);
$detalle = $plugin->contratingCompaniesQuotaCabModel()->getDetails($_GET['quota_cab_id']);
$today = date('Y-m-d');
$whereClause = "
    1 = 1
    AND (
        display_start_date >= '$today'
        OR (
            display_start_date <= '$today'
            AND (
                display_end_date IS NULL
                OR display_end_date >= '$today'
            )
        )
    )
";
$activeSessions = SessionManager::getSessionsForAdmin(
    api_get_user_id(),
    [
        'where' => $whereClause,
        'extra' => []
    ],
    false,
    [],
    'all'
);

if (empty($activeSessions)) {
    $activeSessions = [];
}

$sessionDistributions = [];
if (!empty($detalle)) {
    foreach ($detalle as $det) {
        $sessionDistributions[$det['id']] = $plugin->contratingCompaniesQuotaSessionModel()->getDistributionByDetId($det['id'], $det['session_category_id'], $det['session_mode']);
    }
}

$sessionFormValues = [];
$hasErrors = false;
$errorsMessage = [];

if ($form->isSubmitted()) {
    // check if $cabecera['validity_date'] is greater than today
    $validityDate = DateTime::createFromFormat('Y-m-d', $cabecera['validity_date']);
    $today = new DateTime();
    if ($validityDate < $today) {
        $hasErrors = true;
        $form->setElementError('validity_date', 'La fecha de vigencia no puede ser menor a la fecha actual');
    }

    $formValues = $form->getSubmitValues();
    $sessionFormValues = $formValues['session'];

    $sessionsInfoByDetId = [];
    if (!empty($sessionFormValues)) {
        // check if session_id and user_quota are empty
        foreach ($sessionFormValues as $key => $value) {
            if (empty($value['session_id']) || empty($value['user_quota']) || $value['user_quota'] < 0) {
                $hasErrors = true;
                $errorsMessage[$value['det_id']][] = 'Completar correctamente todos los campos';
                continue;
            }

            if (
                !isset($sessionsInfoByDetId[$value['det_id']][$value['session_id']])
            ) {
                $sessionInfo = api_get_session_info($value['session_id']);
                $sessionsInfoByDetId[$value['det_id']][$value['session_id']] = [
                    'maximum_users' => $sessionInfo['maximum_users'] ?? 0,
                    'user_quota' => 0,
                    'session_name' => $sessionInfo['name'] ?? '',
                ];
            }

            $sessionsInfoByDetId[$value['det_id']][$value['session_id']]['user_quota'] = (
                $sessionsInfoByDetId[$value['det_id']][$value['session_id']]['user_quota'] ?? 0
            ) + $value['user_quota'];
        }

        foreach ($sessionsInfoByDetId as $detId => $sessionInfo) {
            $totalUserQuota = 0;
            foreach ($sessionInfo as $sessionId => $info) {
                $totalUserQuota += $info['user_quota'];
                // check if user_quota is greater than maximum_users
                if ($info['user_quota'] > $info['maximum_users']) {
                    $hasErrors = true;
                    $errorsMessage[$detId][] =  $info['session_name'] . ' - Máximo de usuarios: ' . $info['maximum_users'];
                }
            }

            // check if $info['user_quota'] is greater than detalle['quota']
            if (!empty($detalle)) {
                // Filter the $detalle array to find the matching det_id
                $matchingDetalle = array_filter($detalle, function ($d) use ($detId) {
                    return $d['id'] == $detId;
                });
                $matchingDetalle = reset($matchingDetalle);

                // Get the first matching detalle
                if (!empty($matchingDetalle)) {
                    $quota = $matchingDetalle['quota'];
                    if ($totalUserQuota > $quota) {
                        $hasErrors = true;
                        $errorsMessage[$detId][] = 'La suma de los cupos asignados a las sesiones no puede ser mayor a ' . $quota;
                    }

                    // check remaining user_quota
                    if (isset($sessionDistributions[$detId])) {
                        $totalUserQuotaAssigned = 0;
                        foreach ($sessionDistributions[$detId] as $session) {
                            $totalUserQuotaAssigned += $session['user_quota'];
                        }

                        $remainingUserQuota = $quota - $totalUserQuotaAssigned;
                        if ($remainingUserQuota < $totalUserQuota) {
                            $hasErrors = true;
                            $errorsMessage[$detId][] = 'No hay suficientes cupos disponibles para asignar a las sesiones. Quedan ' . $remainingUserQuota . ' cupos disponibles';
                        }
                    }
                }
            }
        }
    }
}

if ($form->validate() && false === $hasErrors) {
    $sessionFormValues = $form->getSubmitValues();
    $sessionFormValues = $sessionFormValues['session'];

    foreach ($sessionFormValues as $key => $value) {
        // save
        $id = $plugin->contratingCompaniesQuotaSessionModel()->save([
            'det_id' => $value['det_id'],
            'session_id' => $value['session_id'],
            'user_quota' => $value['user_quota'],
            'created_user_id' => api_get_user_id()
        ]);

        // quota_session_det
        for ($i = 0; $i < $value['user_quota']; $i++) {
            $plugin->contratingCompaniesQuotaSessionDetModel()->save([
                'quota_session_id' => $id,
                'session_id' => $value['session_id'],
                'expiration_date' => $cabecera['validity_date'],
                'created_user_id' => api_get_user_id()
            ]);
        }

    }

    $url = api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies_assign_quota_to_session.php?company_id=' . $_GET['company_id'] . '&action=assign_quota_to_session&quota_cab_id=' . $_GET['quota_cab_id'];
    header('Location: ' . $url);
}

$sessionsList = json_encode($activeSessions);
$sessionFormValues = json_encode($sessionFormValues);
$errorsMessage = json_encode($errorsMessage);
$form->addHtml('<div id="root_asignar_cupos"></div>');

$detalle = json_encode($detalle ?? []);
$form->addHtml(<<<EOT
<script>
const detalle = JSON.parse('{$detalle}');
const sessionsList = JSON.parse('{$sessionsList}');
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

        sessionDetailContainer.insertAdjacentHTML('beforeend',
        `<div class="form-group" id="` + containerId + `">
            <div class="col-sm-2"></div>
            <div class="col-sm-8">
                <div class="card">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width: 200px;">{$plugin->get_lang('Mode')}</th>
                                <th style="width: 300px;">{$plugin->get_lang('SessionCategory')}</th>
                                <th class="text-center" style="width: 150px;">{$plugin->get_lang('ContratingCompanyUserQuota')}</th>
                                <th></th>
                            </tr>
                            <tr>
                                <td style="width: 200px; vertical-align: middle;">` + item.session_mode_name + `</td>
                                <td style="width: 300px; vertical-align: middle;">` + item.category_name + `</td>
                                <td class="text-center" style="width: 150px;">
                                    <input type="text" readonly class="form-control" value="` + item.quota + `" style="text-align: right;">
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" class="btn btn-primary" id="` + plusButtonId + `">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </td>
                            </tr>
                        </thead>
                        <tbody id="` + tableBodyId + `">
                        </tbody>
                    </table>
                </div>
                <div class="errors_message" id="errors_message_` + uniqueId + `"></div>
            </div>
            <div class="col-sm-2"></div>
        </div>`);

        document.getElementById(plusButtonId).addEventListener('click', function() {
            lastIndex++;
            const itemIndex = parseInt(lastIndex);
            addNewRow(itemIndex, tableBodyId, item.session_mode, item.session_category_id, item.id);
        });

        let sessionValues = JSON.parse('{$sessionFormValues}');
        if (sessionValues && Object.keys(sessionValues)?.length > 0) {
            for (const [key, value] of Object.entries(sessionValues)) {
                if (item.id != value.det_id) {
                    continue;
                }

                lastIndex++;
                const itemIndex = parseInt(lastIndex);
                addNewRow(itemIndex, tableBodyId, item.session_mode, item.session_category_id, item.id, value.session_id, value.user_quota);
            }
        }

        let errorsMessage = JSON.parse('{$errorsMessage}');
        if (errorsMessage && Object.keys(errorsMessage)?.length > 0) {
            for (const [key, value] of Object.entries(errorsMessage)) {
                if (item.id != key) {
                    continue;
                }

                let errorsMessageContainer = document.getElementById('errors_message_' + uniqueId);
                if (errorsMessageContainer) {
                    errorsMessageContainer.innerHTML = '';
                    value.forEach((error) => {
                        errorsMessageContainer.insertAdjacentHTML('beforeend', '<div class="alert alert-warning">' + error + '</div>');
                    });
                }
            }
        }
    });
}

function addNewRow(itemIndex, tableBodyId, itemSessionMode, itemSessionCategoryId, itemDetId = null, itemSessionId = null, itemUserQuota = null) {
    const sessionsByCategory = sessionsList.filter(session => session.session_category_id == itemSessionCategoryId && session.session_mode == itemSessionMode);
    const tableBody = document.getElementById(tableBodyId);
    const newRow = document.createElement('tr');
    const sessionsSelect = document.createElement('select');
    sessionsSelect.name = 'session[' + itemIndex + '][session_id]';
    sessionsSelect.className = 'form-control';

    sessionsSelect.innerHTML = '<option value="">{$plugin->get_lang('SelectSession')}</option>';
    sessionsByCategory.forEach(session => {
        const option = document.createElement('option');
        option.value = session.id;
        option.text = session.name + ' - ' + (session.time_in_session > 0 ? (session.time_in_session + ' Horas') : '');
        sessionsSelect.appendChild(option);
    });

    newRow.innerHTML = `
        <td colspan="2">
            ` + sessionsSelect.outerHTML + `
        </td>
        <td>
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
</script>
EOT
);

$form->addButtonSave($plugin->get_lang('SaveContratingCompanyDetailsQuota'));

$items = [];
if (!empty($sessionDistributions)) {
    foreach ($sessionDistributions as $key => $value) {
        foreach ($value as $session) {
            $session['session_name'] = $session['time_in_session'] > 0 ? ($session['session_name'] . ' - ' . $session['time_in_session'] . ' Horas') : $session['session_name'];
            $items[] = $session;
        }
    }
}

$tpl->assign('form', $form->returnForm());
$tpl->assign('items', $items);
$content = $tpl->fetch('proikos/view/proikos_contrating_companies_assign_quota_to_session.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
