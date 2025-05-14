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
$title = isset($_GET['action']) && $_GET['action'] === 'register' ?
    $plugin->get_lang('TitleRegister') : $plugin->get_lang('TitleRegisterTwo');

if($action == 'second'){
            $entitySelect = $_POST['entity'];
            $picture = $plugin->getPictureEntity($entitySelect);
            $formAction = api_get_self().'?action='.Security::remove_XSS('register');
            $formAttributes = [];
            $form = new FormValidator('registration-two', 'post', $formAction, '', $formAttributes, FormValidator::LAYOUT_INLINE);
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
            $phoneInput = $form->addNumeric('phone', [$plugin->get_lang('Phone'), $plugin->get_lang('PhoneHelp')], ['class' => 'form-control']);
            $form->setRequired($phoneInput);
            $form->addHtml('</div></div>');
            $typesDocuments = [
                '0' => 'Seleccione una opción',
                '1' => 'DNI',
                '2' => 'Carnet de Extranjeria',
                '3' => 'Pasaporte',
                '5' => 'Otros',
            ];
            $form->addHtml('<div class="row"><div class="col-md-6">');
            $typeInput = $form->addSelect('type_document', $plugin->get_lang('TypeDocument'), $typesDocuments);
            $form->setRequired($typeInput);
            $form->addHtml('</div><div class="col-md-6">');
            $form->addText('number_document', [$plugin->get_lang('NumberDocument'), $plugin->get_lang('NumberDocumentHelp')], true);
            $form->addHtml('</div></div>');
            $form->addHtml('<div class="row"><div class="col-md-4">');
            $ageInput = $form->addNumeric('age', $plugin->get_lang('Age'), ['class' => 'form-control']);
            $form->setRequired($ageInput);
            $form->addHtml('</div><div class="col-md-4">');
            $genders = [
                '0' => 'Seleccione una opción',
                'M' => 'Masculino',
                'F' => 'Femenino'
            ];
            $genderInput = $form->addSelect('gender', $plugin->get_lang('Gender'), $genders);
            $form->setRequired($genderInput);
            $form->addHtml('</div><div class="col-md-4">');
            $instructions = [
                '0' => 'Seleccione una opción',
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

            $form->addHtml('<div class="row"><div class="col-md-12">');
            $stakeholders = [
                '0' => 'Seleccione una opción',
                '1' => 'Petroperu',
                '2' => 'Contratista',
                '3' => 'Cliente',
                '99' => 'Otros',
            ];
            $stakeholdersSelect = $form->addSelect('stakeholders', $plugin->get_lang('Stakeholder'), $stakeholders);
            $form->setRequired($stakeholdersSelect);
            $form->addHtml('</div></div>');

            //contratistas
            $form->addHtml('<div id="option-builder">');

            $contratingCompanies = $plugin->contratingCompaniesModel()->getData(null, true);
            $contratingCompaniesSelect = $form->addSelect('contrating_companies', $plugin->get_lang('Company_RUC'), $contratingCompanies);
            $form->setRequired($contratingCompaniesSelect);
            // end contratistas

            $form->addHtml('<div id="options-column">');
            $form->addHtml('<div id="option-number" style="display: none;">');
            $form->addNumeric('record_number',$plugin->get_lang('RecordNumber'),['value'=>'0'],true);
            $form->addHtml('</div>');
            $position = $plugin->getPositions(2);
            $positionInput = $form->addSelect('position_company', $plugin->get_lang('Position'), $position);
            $form->setRequired($positionInput);
            $area = $plugin->getPetroArea();
            $areaSelect = $form->addSelect('area', $plugin->get_lang('Sede'), $area);
            $form->setRequired($areaSelect);
            $form->addHtml('</div>');

            //$departments = $plugin->getPetroManagement();
            //$departmentsSelect = $form->addSelect('department', [$plugin->get_lang('Department')], $departments);
            //$form->setRequired($departmentsSelect);
            //$headquarters = $plugin->getHeadquarters(true);
            //$headquartersSelect = $form->addSelect('headquarters', [$plugin->get_lang('Headquarters')], $headquarters);
            //$form->setRequired($headquartersSelect);
            $form->addHtml('</div></div>');
            $form->addHidden('code_reference', $entitySelect);

    $termsAndConditionsAccepted = isset($_POST['check_terms_and_conditions']) && $_POST['check_terms_and_conditions'] == 1;
    $form->addHtml('<div class="form-check terms_conditions_container">
                  <input name="check_terms_and_conditions" class="form-check-input" type="checkbox" value="1" id="check_terms_and_conditions" ' . ($termsAndConditionsAccepted ? 'checked' : '') . '>
                  <label class="form-check-label" for="check_terms_and_conditions">
                    <a href="' . api_get_path(WEB_PLUGIN_PATH) . 'proikos/files/proikos_terminos_y_condiciones.pdf" target="_blank" id="link_term_and_conditions">
                        ' . $plugin->get_lang('TermsAndConditions') . '
                    </a>
                  </label>
                </div>
                <script>
                    // Check if the checkbox is checked to enable or disable the button with name "register"
                    document.getElementById("check_terms_and_conditions").addEventListener("change", function() {
                        let registerButton = document.querySelector("button[name=\'register\']");
                        if (this.checked) {
                            registerButton.removeAttribute("disabled");
                        } else {
                            registerButton.setAttribute("disabled", "disabled");
                        }
                    });

                    document.getElementById("link_term_and_conditions").addEventListener("click", function() {
                        // attach event click to check_terms_and_conditions
                        let checkbox = document.getElementById("check_terms_and_conditions");
                        if (!checkbox.checked) {
                            checkbox.click();
                        }
                    });
                </script>
                ');

            $buttonAttr = [];
            if (!$termsAndConditionsAccepted) {
                $buttonAttr = [
                    'disabled' => 'disabled'
                ];
            }

            $form->addButton('register', $plugin->get_lang('RegisterUser'), null, 'primary', 'btn-block', null, $buttonAttr);
            $form->addHtml('<div class="form-group row-back"><a href="'.api_get_self().'" class="btn btn-success btn-block">Regresar</a></div>');
            $form->applyFilter('__ALL__', 'Security::remove_XSS');

            if ($form->validate()) {
                $values = $form->getSubmitValues(1);

                $emailValidation = $plugin->validEmail($values['email']);
                if (true !== $emailValidation) {
                    $form->setElementError('email', $emailValidation);
                    goto init_form;
                }

                $numDocValidation = $plugin->validUserDNI($values['number_document']);
                if (true !== $numDocValidation) {
                    $form->setElementError('number_document', $numDocValidation);
                    goto init_form;
                }

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

                $selectedContratingCompany = $plugin->contratingCompaniesModel()->getData($_POST['contrating_companies']);
                $values['ruc_company'] = $values['ruc_company'] ?? '';
                $values['name_company'] = $values['name_company'] ?? '';
                if (!empty($selectedContratingCompany)) {
                    $values['ruc_company'] = $selectedContratingCompany['ruc'];
                    $values['name_company'] = $selectedContratingCompany['name'];
                }

                $values['address'] = $values['address'] ?? '';
                $values['record_number'] = $values['record_number'] ?? '-';
                $phone = $values['phone'] ?? null;
                $password = $values['number_document'];
                $codeReference = $values['code_reference'];
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
                    $form,
                    0,[],
                    '',
                    $codeReference
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

                header('Location: '.api_get_path(WEB_PATH).'main/auth/courses.php');
                exit;
            }
            // Custom pages
            if (CustomPages::enabled() && CustomPages::exists(CustomPages::REGISTRATION)) {
                init_form:
                CustomPages::display(
                    CustomPages::REGISTRATION,
                    [
                        'form' => $form,
                        'content' => $content,
                        'title' => $title,
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
$form = new FormValidator('registration-one', 'post', api_get_self().'?action='.Security::remove_XSS('second'), '', [], FormValidator::LAYOUT_INLINE);

if(count($entities) == 1){
    $company = $entities[0];
    $logoPicture = Display::img($company['picture'],$company['business_name']);
    $html = "<div class='logo-company text-center' style='
    border: 1px solid #cdcdcd;
    border-radius: 10px;
    margin-bottom: 2rem;
    background: #e9fffa;
'>".$logoPicture."</div>";
    $html .= "<p>Esta a punto de registrarte en la plataforma virtual de <strong>".$company['business_name']."</strong> con <strong>RUC ".$company['ruc']."</strong></p>";
    $form->addHtml($html);
    $form->addHidden('entity', $company['code_reference']);
} else {
    $listEntity = [];
    foreach ($entities as $entity){
        $listEntity[] = [
            'value' => $entity['code_reference'],
            'display_text' => $entity['name_entity'],
            'display_img' => '<img width="150px" src="'.$entity['picture'].'">'
        ];
    }
    $group = $plugin->formGenerateElementsGroup($form, $listEntity, 'entity',true);
// SearchEnabledComment
    $groupEntity = $form->addGroup(
        $group,
        'entity',
        [$plugin->get_lang('ChooseTheEntity')],
        null,
        false
    );

}

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



