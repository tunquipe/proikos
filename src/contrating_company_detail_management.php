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
$form->addText('company_total_user_quota', $plugin->get_lang('CompanyTotalUserQuota'), false, [
    'value' => $empresa['total_user_quota'],
    'disabled' => 'disabled'
]);

$form->addNumeric('user_quota', $plugin->get_lang('ContratingCompanyUserQuota'), [], true);
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
