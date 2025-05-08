<?php

require_once __DIR__ . '/../config.php';
api_block_anonymous_users();
$action = $_GET['action'] ?? null;
$plugin = ProikosPlugin::create();
$tool_name = $plugin->get_lang('ManageContratingCompanies');
$message = null;
$actionLinks = null;
$htmlHeadXtra[] = api_get_css_asset('cropper/dist/cropper.min.css');
$htmlHeadXtra[] = api_get_asset('cropper/dist/cropper.min.js');

$tpl = new Template($tool_name);
$isAdmin = api_is_platform_admin();
$contratingCompanies = null;

$allow = api_is_platform_admin() || api_is_teacher();
if (!$allow) {
    api_not_allowed(true);
}

if ($isAdmin) {
    $actionLinks .= Display::url(
        Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_PLUGIN_PATH) . 'proikos/start.php'
    );
    $actionLinks .= Display::url(
        Display::return_icon('new_class.png', get_lang('Add'), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_PLUGIN_PATH).'proikos/src/contrating_companies.php?action=create'
    );

    if ($action === 'delete') {
        $id = $_GET['id'] ?? null;
        $contratingCompany = $plugin->contratingCompaniesModel()->getData($id);
        if (!empty($contratingCompany)) {
            $res = $plugin->contratingCompaniesModel()->delete($id);
            if ($res) {
                $message = Display::return_message(
                    $plugin->get_lang('ContratingCompanyDeleted'),
                    'success'
                );
            }
        }
    }

    $contratingCompanies = $plugin->contratingCompaniesModel()->getData();
    $tpl->assign('contrating_companies', $contratingCompanies);

    switch ($action) {
        case 'create':
            $actionLinks = Display::url(
                Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
                api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies.php'
            );
            $form = new FormValidator(
                'add_contrating_company',
                'post',
                api_get_self() . '?action=' . Security::remove_XSS($_GET['action'])
            );
            $form->addHeader($plugin->get_lang('AddContratingCompany'));
            $form->addText('ruc', $plugin->get_lang('ContratingCompanyRUC'));
            $form->addText('name', $plugin->get_lang('ContratingCompanyName'));
            $form->addText('admin_name', $plugin->get_lang('ContratingAdminName'));

            $group = [];
            $group[] = $form->createElement('radio', 'status', null, get_lang('Active'), 1);
            $group[] = $form->createElement('radio', 'status', null, get_lang('Inactive'), 0);
            $form->addGroup($group, 'status', get_lang('Status'), null, false);
            $form->addButtonSave($plugin->get_lang('SaveContratingCompany'));

            if ($form->validate()) {
                $values = $form->getSubmitValues();

                $exists = $plugin->contratingCompaniesModel()->getDataByRUC($values['ruc']);
                if (!empty($exists)) {
                    $message = Display::return_message(
                        $plugin->get_lang('ContratingCompanyRUCExists'),
                        'error'
                    );
                } else {
                    $res = $plugin->contratingCompaniesModel()->save($values);
                    $url = api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies.php';
                    header('Location: ' . $url);
                }
            }
            $tpl->assign('form', $form->returnForm());
            break;
        case 'edit':
            $actionLinks = Display::url(
                Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
                api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies.php'
            );
            $idContratingCompany = $_GET['id'] ?? null;
            $contratingCompany = $plugin->contratingCompaniesModel()->getData($idContratingCompany);

            //edit form
            $form = new FormValidator(
                'edit_contrating_company',
                'post',
                api_get_self() . '?action=' . Security::remove_XSS($_GET['action'])
            );

            $form->addHeader($plugin->get_lang('UpdateContratingCompany'));
            $form->addText('ruc', $plugin->get_lang('ContratingCompanyRUC'));
            $form->addText('name', $plugin->get_lang('ContratingCompanyName'));
            $form->addText('admin_name', $plugin->get_lang('ContratingAdminName'));

            $group = [];
            $group[] = $form->createElement('radio', 'status', null, get_lang('Active'), 1);
            $group[] = $form->createElement('radio', 'status', null, get_lang('Inactive'), 0);
            $form->addGroup($group, 'status', get_lang('Status'), null, false);
            $form->addHidden('id', $contratingCompany['id']);
            $form->addButtonSave($plugin->get_lang('SaveContratingCompany'));

            $form->setDefaults($contratingCompany);

            if ($form->validate()) {
                $values = $form->exportValues();
                $contratingCompany = $plugin->contratingCompaniesModel()->getData($values['id']);
                $update = true;

                if ($contratingCompany) {
                    if ($contratingCompany['ruc'] != $values['ruc']) {
                        $exists = $plugin->contratingCompaniesModel()->getDataByRUC($values['ruc']);
                        if (!empty($exists)) {
                            $message = Display::return_message(
                                $plugin->get_lang('ContratingCompanyRUCExists'),
                                'error'
                            );
                            $update = false;
                        }
                    }
                }

                if ($update == true) {
                    $res = $plugin->contratingCompaniesModel()->update($values);

                    if ($res) {
                        $url = api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies.php';
                        header('Location: ' . $url);
                    }
                }
            }

            $tpl->assign('form', $form->returnForm());

            break;
        default:
    }

    $tpl->assign(
        'actions',
        Display::toolbarAction('toolbar', [$actionLinks])
    );
}

$tpl->assign('message', $message);
$tpl->assign('contrating_companies', $contratingCompanies);
$content = $tpl->fetch('proikos/view/proikos_contrating_company.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
