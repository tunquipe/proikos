<?php

use ChamiloSession as Session;

if (!empty($_POST['language'])) {
    $_GET['language'] = $_POST['language'];
}
require_once __DIR__ . '../../../main/inc/global.inc.php';

$plugin = ProikosPlugin::create();
$hideHeaders = isset($_GET['hide_headers']);
$action = isset($_GET['action']);
$tool_name = get_lang('Registration');
$content = $picture = null;
$urlPlugin = api_get_path(WEB_PLUGIN_PATH).'proikos';
$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.api_get_path(
        WEB_PLUGIN_PATH
    ).'proikos/css/style.css"/>';

if($action == 'second'){
            $entitySelect = $_POST['entity'];
            $picture = $plugin->getPictureEntity($entitySelect);
            $form = new FormValidator('registration-two', 'post', api_get_self().'?action='.Security::remove_XSS('register'), '', [], FormValidator::LAYOUT_INLINE);
            $form->addHtml('<div class="panel panel-default">
                    <div class="panel-heading panel-user">
                        <h3 class="panel-title">' . $plugin->get_lang('PersonalInformation') . '</h3>
                    </div>
                    <div class="panel-body">');
            $form->addHtml('<div class="row"><div class="col-md-6">');
            $form->addText('lastname', [get_lang('LastName'), $plugin->get_lang('LastNameHelp')], true);
            $form->addHtml('</div><div class="col-md-6">');
            $form->addText('firstname', [get_lang('FirstName'), $plugin->get_lang('FirstNameHelp')], true);
            $form->addHtml('</div></div>');

            // Language
            if (api_get_setting('registration', 'language') == 'true') {
                $form->addSelectLanguage(
                    'language',
                    get_lang('Language')
                );
            }

            $form->addHtml('<div class="row"><div class="col-md-6">');
            $form->addText('email', [get_lang('Email'), $plugin->get_lang('EmailHelp')], true);
            $form->addHtml('</div><div class="col-md-6">');
            $form->addText('phone', [$plugin->get_lang('Phone'), $plugin->get_lang('PhoneHelp')], true);
            $form->addHtml('</div></div>');
            $typesDocuments = [
                '1' => 'DNI',
                '2' => 'Carnet de Extranjeria',
                '3' => 'Pasaporte',
                '4' => 'RUC',
                '5' => 'Otros',
            ];
            $form->addHtml('<div class="row"><div class="col-md-6">');
            $typeInput = $form->addSelect('type_document', $plugin->get_lang('TypeDocument'), $typesDocuments);
            $form->setRequired($typeInput);
            $form->addHtml('</div><div class="col-md-6">');
            $form->addText('number_document', [$plugin->get_lang('NumberDocument'), $plugin->get_lang('NumberDocumentHelp')], true);
            $form->addHtml('</div></div>');
            $form->addHtml('<div class="row"><div class="col-md-4">');
            $form->addText('age', $plugin->get_lang('Age'), true);
            $form->addHtml('</div><div class="col-md-4">');
            $genders = [
                'M' => 'Masculino',
                'F' => 'Femenino'
            ];
            $genderInput = $form->addSelect('gender', $plugin->get_lang('Gender'), $genders);
            $form->setRequired($genderInput);
            $form->addHtml('</div><div class="col-md-4">');
            $instructions = [
                '1' => 'Primaria',
                '2' => 'Secundaria',
                '3' => 'Técnica superior',
                '4' => 'Universitaria Bachiller',
                '5' => 'Universitaria Titulada',
            ];
            $gradeInput = $form->addSelect('instruction', $plugin->get_lang('GradeInstructions'), $instructions);
            $form->setRequired($gradeInput);
            $form->addHtml('</div></div>');
            $form->addHtml('</div></div>');

            $form->addHtml('<div class="panel panel-default">
                    <div class="panel-heading panel-user">
                        <h3 class="panel-title">' . $plugin->get_lang('CompanyData') . '</h3>
                    </div>
                    <div class="panel-body">');
            $form->addHtml('<div class="row"><div class="col-md-6">');
            $form->addText('name_company', [get_lang('CompanyName'), $plugin->get_lang('CompanyNameHelp')], true);
            $form->addHtml('</div><div class="col-md-6">');
            $form->addText('contact_manager', [$plugin->get_lang('ContactManager'), $plugin->get_lang('ContactManagerHelp')], true);
            $form->addHtml('</div></div>');
            $sectors = $plugin->getSectors();
            $form->addHtml('<div class="row"><div class="col-md-6">');
            $sectorInput = $form->addSelect('sector', $plugin->get_lang('SectorSite'), $sectors);
            $form->setRequired($sectorInput);
            $form->addHtml('</div><div class="col-md-6">');
            $position = [];
            $positionInput = $form->addSelect('position_company', $plugin->get_lang('Position'), $position);
            $form->setRequired($positionInput);
            $form->addHtml('</div></div>');
            $form->addHtml('<div class="row"><div class="col-md-6">');
            $experiences = [
                '1' => 'Menos de 1 año',
                '2' => '1-3 años',
                '3' => '4-6 años',
                '4' => '7-9 años',
                '5' => 'Más de 10 años'
            ];
            $timeInput = $form->addSelect('experience_time', $plugin->get_lang('ExperienceTime'), $experiences);
            $form->setRequired($timeInput);
            $form->addHtml('</div><div class="col-md-6">');
            $categories = [
                '1' => 'Funcionario',
                '2' => 'Empleado',
                '3' => 'Jefe',
                '4' => 'Capataz',
                '5' => 'Técnico',
                '6' => 'Operario',
                '7' => 'Oficial',
                '8' => 'Peón',
                '9' => 'Otros',
            ];
            $categoryInput = $form->addSelect('employment_category', $plugin->get_lang('EmploymentCategory'), $categories);
            $form->setRequired($categoryInput);
            $form->addHtml('</div></div>');

            $form->addHtml('<div class="row"><div class="col-md-6">');
            $stakeholders = [
                'Personal Propio' => 'Personal Propio',
                'Contratista' => 'Contratista',
                'Cliente' => 'Cliente',
                'Otro' => 'Otro',
            ];
            $form->addSelect('stakeholders', $plugin->get_lang('Stakeholder'), $stakeholders);
            $form->addHtml('</div><div class="col-md-6">');
            $form->addText('area', [$plugin->get_lang('Area')], false);
            $form->addHtml('</div></div>');
            $form->addText('department', [$plugin->get_lang('Department')], false);
            $form->addText('headquarters', [$plugin->get_lang('Headquarters')], false);
            $form->addHtml('</div></div>');
            $form->addHidden('code_reference', $entitySelect);
            $form->addButton('register', $plugin->get_lang('RegisterUser'), null, 'primary', 'btn-block');
            $form->addHtml('<div class="form-group row-back"><a href="'.api_get_self().'" class="btn btn-success btn-block">Regresar</a></div>');
            $form->applyFilter('__ALL__', 'Security::remove_XSS');

            if ($form->validate()) {
                $values = $form->getSubmitValues(1);

                $values['username'] = api_substr($values['number_document'], 0, USERNAME_MAX_LENGTH);
                $values['official_code'] = 'PK'.$values['number_document'];
                if (api_get_setting('allow_registration_as_teacher') === 'false') {
                    $values['status'] = STUDENT;
                }
                $status = $values['status'] ?? STUDENT;
                try {
                    $values['language'] = $values['language'] ?? api_get_interface_language();
                } catch (Exception $e) {
                    print_r($e);
                }
                $values['address'] = $values['address'] ?? '';
                $phone = $values['phone'] ?? null;
                $password = $values['number_document'];

                // Creates a new user
                $user_id = UserManager::create_user(
                    $values['firstname'],
                    $values['lastname'],
                    $status,
                    $values['email'],
                    $values['username'],
                    $password,
                    $values['official_code'],
                    $values['language'],
                    $phone,
                    null,
                    PLATFORM_AUTH_SOURCE,
                    null,
                    1,
                    0,
                    [],
                    null,
                    true,
                    false,
                    $values['address'],
                    false,
                    $form
                );
                if ($user_id) {
                    $values['user_id'] = $user_id;
                    $plugin->saveInfoUserProikos($values);
                }

                /* SESSION REGISTERING */
                /* @todo move this in a function */
                $_user['firstName'] = stripslashes($values['firstname']);
                $_user['lastName'] = stripslashes($values['lastname']);
                $_user['mail'] = $values['email'];
                $_user['language'] = $values['language'];
                $_user['user_id'] = $user_id;
                $_user['status'] = $values['status'] ?? STUDENT;
                Session::write('_user', $_user);

                // Stats
                Event::eventLogin($user_id);

                // last user login date is now
                $user_last_login_datetime = 0; // used as a unix timestamp it will correspond to : 1 1 1970
                Session::write('user_last_login_datetime', $user_last_login_datetime);

                $recipient_name = api_get_person_name($values['firstname'], $values['lastname']);

                header('Location: '.api_get_path(WEB_PATH).'user_portal.php');
                exit;
            }
            // Custom pages
            if (CustomPages::enabled() && CustomPages::exists(CustomPages::REGISTRATION)) {
                CustomPages::display(
                    CustomPages::REGISTRATION,
                    [
                        'form' => $form,
                        'content' => $content,
                        'title' => $plugin->get_lang('TitleRegisterTwo'),
                        'url_plugin' => $urlPlugin,
                        'picture' => $picture
                    ]
                );
            } else {
                $tpl = new Template($tool_name);

                $tpl->assign('inscription_header', Display::page_header($tool_name));
                $tpl->assign('inscription_content', $content);
                $tpl->assign('form', $form->returnForm());
                $tpl->assign('hide_header', $hideHeaders);

                $inscription = $tpl->get_template('auth/inscription.tpl');
                $tpl->display($inscription);
            }

}

$entities = $plugin->getListEntity();
$listEntity = [];
foreach ($entities as $entity){
    $listEntity[] = [
        'value' => $entity['code_reference'],
        'display_text' => $entity['name_entity'],
        'display_img' => '<img width="150px" src="'.$entity['picture'].'">'
    ];
}

$form = new FormValidator('registration-one', 'post', api_get_self().'?action='.Security::remove_XSS('second'), '', [], FormValidator::LAYOUT_INLINE);
$group = $plugin->formGenerateElementsGroup($form, $listEntity, 'entity');
// SearchEnabledComment
$form->addGroup(
    $group,
    'entity',
    [get_lang('ChooseTheEntity')],
    null,
    false
);

$form->addButton('next', $plugin->get_lang('Next'), null, 'primary', 'btn-block');
// Custom pages
if (CustomPages::enabled() && CustomPages::exists(CustomPages::REGISTRATION)) {
    CustomPages::display(
        CustomPages::REGISTRATION,
        [
            'form' => $form,
            'content' => $content,
            'title' => $plugin->get_lang('TitleRegisterOne'),
            'url_plugin' => $urlPlugin,
            'picture' => $picture
        ]
    );
} else {

    $tpl = new Template($tool_name);
    $tpl->assign('inscription_header', Display::page_header($tool_name));
    $tpl->assign('inscription_content', $content);
    $tpl->assign('form', $form->returnForm());
    $tpl->assign('hide_header', $hideHeaders);

    $inscription = $tpl->get_template('auth/inscription.tpl');
    $tpl->display($inscription);
}



