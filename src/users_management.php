<?php

require_once __DIR__ . '/../config.php';
api_block_anonymous_users();
$action = $_GET['action'] ?? null;
$plugin = ProikosPlugin::create();
$tool_name = 'Gestionar usuarios';
$message = null;
$actionLinks = null;

$tpl = new Template($tool_name);
$isAdmin = api_is_platform_admin();

switch ($action){
    case 'edit':
        $actionLinks = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/users_management.php?action=list'
        );
        $idUser = $_GET['user_id'] ?? null;
        $user = $plugin->getInfoUserProikos($idUser);

        $form = new FormValidator(
            'edit',
            'post',
            api_get_self() . '?action=' . Security::remove_XSS($_GET['action'])
        );
        $form->addHeader($plugin->get_lang('EditUser'));
        $form->addText('lastname', get_lang('LastName'), false, ['disabled' => true]);
        $form->addText('firstname', get_lang('FirstName'), false, ['disabled' => true]);
        $form->addText('email', get_lang('Email'), false, ['disabled' => true]);
        $form->addText('phone', $plugin->get_lang('Phone'), false);
        $typesDocuments = [
            '0' => 'Seleccione una opción',
            '1' => 'DNI',
            '2' => 'Carnet de Extranjeria',
            '3' => 'Pasaporte',
            '5' => 'Otros',
        ];
        $form->addSelect('type_document', $plugin->get_lang('TypeDocument'), $typesDocuments);
        $form->addText('number_document', $plugin->get_lang('NumberDocument'), false);
        $form->addNumeric('age', $plugin->get_lang('Age'), ['class' => 'form-control']);
        $genders = [
            '0' => 'Seleccione una opción',
            'M' => 'Masculino',
            'F' => 'Femenino'
        ];
        $form->addSelect('gender', $plugin->get_lang('Gender'), $genders);

        $instructions = [
            '0' => 'Seleccione una opción',
            '1' => 'Primaria',
            '2' => 'Secundaria',
            '3' => 'Técnica superior',
            '4' => 'Universitaria Bachiller',
            '5' => 'Universitaria Titulada',
        ];
        $form->addSelect('instruction', $plugin->get_lang('GradeInstructions'), $instructions);
        $stakeholders = [
            '0' => 'Seleccione una opción',
            '1' => 'Petroperu',
            '2' => 'Contratista',
            '3' => 'Cliente',
            '99' => 'Otros',
        ];
        $form->addSelect('stakeholders', $plugin->get_lang('Stakeholder'), $stakeholders);
        $contratingCompanies = $plugin->contratingCompaniesModel()->getDataCompanies();
        $form->addSelect('name_company', $plugin->get_lang('Company_RUC'), $contratingCompanies);
        $position = $plugin->getPositions(2, true);
        $form->addSelect('position_company', $plugin->get_lang('Position'), $position);

        $area = $plugin->getPetroArea(true);
        $form->addSelect('area', $plugin->get_lang('Sede'), $area);
        $form->addText('code_reference', $plugin->get_lang('CodeReference'));
        $form->addHidden('id', $idUser);
        $form->addButtonSave($plugin->get_lang('SaveUserExtra'));
        $form->setDefaults($user);

        if ($form->validate()) {
            $values = $form->exportValues();
            $ruc = $plugin->getRUC($values['name_company']);
            $values['ruc'] = $ruc;
            $plugin->saveProikosUser($values);
        }
        $tpl->assign('form_edit', $form->returnForm());

        break;
    case 'list':
        $actionLinks .= Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_PLUGIN_PATH) . 'proikos/start.php'
        );
        $users = $plugin->getUsers();

        $tpl->assign('users', $users);
        default;
}
$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$actionLinks])
);
$tpl->assign('message', $message);
$content = $tpl->fetch('proikos/view/proikos_users.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
