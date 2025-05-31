<?php

require_once __DIR__ . '/../config.php';
api_block_anonymous_users();
$action = $_GET['action'] ?? null;
$plugin = ProikosPlugin::create();
$allow = api_is_platform_admin() || api_is_drh() || api_is_contractor_admin();

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
$actionLinks .= Display::url(
    Display::return_icon('home.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
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

$totalQuotaDispon = 0;
foreach ($items as $item) {
    $totalQuotaDispon += $item['quota_dispon'];
}

$form->addText('company_total_user_quota', $plugin->get_lang('ContratingCompanyUserQuotaDispon'), false, [
    'value' => $totalQuotaDispon,
    'disabled' => 'disabled'
]);

if (api_is_platform_admin() || api_is_drh()) {
$courseDetailHasError = $plugin->getCRUDQuotaDet($form);
$form->addElement('select', 'validity_select', '', [
    '+3 months' => '3 meses',
    '+6 months' => '6 meses',
    '+1 year' => '1 año',
    '+2 years' => '2 años',
]);
$form->addElement('date_picker', 'validity_date', $plugin->get_lang('Validity'), [
    'value' => date('Y-m-d', strtotime('+6 months'))
]);
$form->addRule('validity_date', $plugin->get_lang('ValidityRequired'), 'required');

// set default value to validity_select
$form->setDefaults([
    'validity_select' => '+6 months',
]);

$form->addElement('html', <<<EOD
<style>
#validity_date_alt_text {
    text-transform: lowercase;
}
</style>
<script>
    $(document).ready(function () {
        const select = $('[name="validity_select"]');
        const dateInput = $('[name="validity_date"]');

        select.on('change', function () {
            const offset = $(this).val();
            const now = new Date();
            let targetDate = new Date(now);

            if (offset === '+3 months') {
                targetDate.setMonth(now.getMonth() + 3);
            } else if (offset === '+6 months') {
                targetDate.setMonth(now.getMonth() + 6);
            } else if (offset === '+1 year') {
                targetDate.setFullYear(now.getFullYear() + 1);
            } else if (offset === '+2 years') {
                targetDate.setFullYear(now.getFullYear() + 2);
            }

            const yyyy = targetDate.getFullYear();
            const mm = String(targetDate.getMonth() + 1).padStart(2, '0');
            const dd = String(targetDate.getDate()).padStart(2, '0');
            const formatted = yyyy + '-' + mm + '-' + dd;

            dateInput.val(formatted);
            dateInput.datepicker('setDate', formatted); // Notificar al datepicker

            // trigger change event
            dateInput.trigger('change');
        });
    });
</script>
EOD
);




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
                'session_category_id' => $value['session_category_id'],
                'user_quota' => $value['quota'],
                'price_unit' => $value['price_unit'] ?? 0,
                'session_mode' => $value['session_mode'] ?? 0,
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
