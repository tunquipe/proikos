<?php

require_once __DIR__ . '/../config.php';
api_block_anonymous_users();
$action = $_GET['action'] ?? null;
$plugin = ProikosPlugin::create();
$allow = api_is_platform_admin() || api_is_teacher();

if (!$allow) {
    api_not_allowed(true);
}

$companyId = $_GET['company_id'];
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $plugin->contratingCompaniesQuotaCabModel()->delete($_GET['quota_cab_id']);
    $url = api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies_quota_cab.php?company_id=' . $companyId;
    header('Location: ' . $url);
}

if (empty($companyId)) {
    api_not_allowed(true);
}

$message = '';
$actionLinks = '';
$actionLinks .= Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies.php'
);

$empresa = $plugin->contratingCompaniesModel()->getData($companyId);
$tool_name = 'Gestionar cupos de la empresa';
$tpl = new Template($tool_name);
$items = $plugin->contratingCompaniesQuotaCabModel()->getDataByCompanyId($companyId);

// ------------------------
// Form
// ------------------------
$form = new FormValidator(
    'add_contrating_company_details',
    'post',
    api_get_self() . '?company_id=' . Security::remove_XSS($_GET['company_id'])
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

$courseDetailHasError = $plugin->getCRUDQuotaDet($form);
$form->addElement('date_picker', 'validity_date', $plugin->get_lang('Validity'));
$form->addRule('validity_date', $plugin->get_lang('ValidityRequired'), 'required');
$form->addButtonSave($plugin->get_lang('SaveContratingCompanyDetailsQuota'));

if ($form->validate() && $courseDetailHasError === false) {
    $values = $form->getSubmitValues();

    // Save cab
    $params = [
        'contrating_company_id' => $companyId,
        'validity_date' => $values['validity_date'],
        'created_user_id' => api_get_user_id()
    ];
    $cabId = $plugin->contratingCompaniesQuotaCabModel()->save($params);

    if (false !== $cabId) {
        // save det
        foreach ($values['course_detail'] as $key => $value) {
            $params = [
                'cab_id' => $cabId,
                'type_course_id' => $value['type'],
                'course_id' => $value['course'],
                'user_quota' => $value['quota'],
                'created_user_id' => api_get_user_id(),
            ];
            $plugin->contratingCompaniesQuotaDetModel()->save($params);
        }
    }

    $message = Display::return_message(
        $plugin->get_lang('ContratingCompanyDetailsQuotaAdded'),
        'success'
    );

    $url = api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies_quota_cab.php?company_id=' . $companyId;
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
$content = $tpl->fetch('proikos/view/proikos_contrating_companies_quota_cab.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
