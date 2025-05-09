<?php

require_once __DIR__ . '/../config.php';
api_block_anonymous_users();
$action = $_GET['action'] ?? null;
$plugin = ProikosPlugin::create();
$allow = api_is_platform_admin() || api_is_teacher();

if (!$allow) {
    api_not_allowed(true);
}

$tool_name = 'Gestionar configuración de cupos';
$tpl = new Template($tool_name);
$empresa = $plugin->contratingCompaniesModel()->getData($_GET['company_id']);
$cabecera = $plugin->contratingCompaniesQuotaCabModel()->getData($_GET['quota_cab_id']);
$detalle = $plugin->contratingCompaniesQuotaCabModel()->getDetails($_GET['quota_cab_id']);

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
    'contrating_companies_quota_det',
    'post',
    api_get_self() . '?company_id=' . Security::remove_XSS($_GET['company_id']) .
    '&action=' . $action . '&quota_cab_id=' . Security::remove_XSS($_GET['quota_cab_id'])
);
$form->addHeader($plugin->get_lang('Company'));
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

$form->addHeader($plugin->get_lang('EditContratingCompanyDetailsQuota'));
$form->addText('cab_total_user_quota', $plugin->get_lang('ContratingCompanyUserQuota'), false, [
    'value' => $cabecera['total_user_quota'],
    'disabled' => 'disabled'
]);
$form->addText('cab_total_price_unit_quota', $plugin->get_lang('ContratingCompanyUserQuotaTotalPrice'), false, [
    'value' => $cabecera['total_price_unit_quota'],
    'disabled' => 'disabled'
]);
$form->addText('cab_created_at', $plugin->get_lang('ContratingCompanyCreationDate'), false, [
    'value' => $cabecera['formatted_created_at'],
    'disabled' => 'disabled'
]);
$form->addText('cab_user_name', $plugin->get_lang('ContratingCompanyCreatedByUser'), false, [
    'value' => $cabecera['user_name'],
    'disabled' => 'disabled'
]);
$courseDetailHasError = $plugin->getCRUDQuotaDet($form, $detalle);
$form->addElement('date_picker', 'validity_date', $plugin->get_lang('Validity'), [
    'value' => $cabecera['formatted_input_validity_date']
]);
$form->addRule('validity_date', $plugin->get_lang('ValidityRequired'), 'required');
$form->addButtonSave($plugin->get_lang('SaveContratingCompanyDetailsQuota'));

if ($form->validate() && $courseDetailHasError === false) {
    $values = $form->getSubmitValues();

    // Update cab
    $params = [
        'id' => $_GET['quota_cab_id'],
        'validity_date' => $values['validity_date'],
    ];
    $plugin->contratingCompaniesQuotaCabModel()->update($params);

    $itemsToUpdate = [];
    // update / save det
    foreach ($values['course_detail'] as $key => $value) {
        // update
        if (isset($value['id'])) {
            $itemsToUpdate[] = $value['id'];
            $params = [
                'id' => $value['id'],
                'session_category_id' => $value['session_category_id'],
                'user_quota' => $value['quota'],
                'price_unit' => $value['price_unit'] ?? 0,
                'updated_user_id' => api_get_user_id(),
            ];
            $plugin->contratingCompaniesQuotaDetModel()->update($params);
        } else {
            // save
            $params = [
                'cab_id' => $_GET['quota_cab_id'],
                'session_category_id' => $value['session_category_id'],
                'user_quota' => $value['quota'],
                'price_unit' => $value['price_unit'] ?? 0,
                'created_user_id' => api_get_user_id(),
            ];
            $plugin->contratingCompaniesQuotaDetModel()->save($params);
        }
    }

    // delete
    // check details that are not in the $itemsToUpdate array
    $itemsToDelete = [];
    foreach ($detalle as $key => $value) {
        if (!in_array($value['id'], $itemsToUpdate)) {
            $itemsToDelete[] = $value['id'];
        }
    }
    $plugin->contratingCompaniesQuotaDetModel()->delete($itemsToDelete);

    $message = Display::return_message(
        $plugin->get_lang('ContratingCompanyDetailsQuotaUpdated'),
        'success'
    );

    $url = api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies_quota_det.php?company_id=' . $_GET['company_id'] . '&action=edit&quota_cab_id=' . $_GET['quota_cab_id'];
    header('Location: ' . $url);
}

$tpl->assign('form', $form->returnForm());
$content = $tpl->fetch('proikos/view/proikos_contrating_companies_quota_det.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
