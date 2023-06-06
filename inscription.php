<?php

if (!empty($_POST['language'])) {
    $_GET['language'] = $_POST['language'];
}
require_once __DIR__ . '../../../main/inc/global.inc.php';

$plugin = ProikosPlugin::create();

$hideHeaders = isset($_GET['hide_headers']);

$content = null;
$form = new FormValidator('registration', 'post', '', '', [], FormValidator::LAYOUT_INLINE);

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
$form->addSelect('type_document', $plugin->get_lang('TypeDocument'), $typesDocuments);
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
$form->addSelect('sex', $plugin->get_lang('Gender'), $genders);
$form->addHtml('</div><div class="col-md-4">');
$instructions = [
    '1' => 'Primaria',
    '2' => 'Secundaria',
    '3' => 'Técnica superior',
    '4' => 'Universitaria Bachiller',
    '5' => 'Universitaria Titulada',
];
$form->addSelect('instruction', $plugin->get_lang('GradeInstructions'), $instructions);
$form->addHtml('</div></div>');
$form->addHtml('</div></div>');

$form->addHtml('<div class="panel panel-default">
                    <div class="panel-heading panel-user">
                        <h3 class="panel-title">' . $plugin->get_lang('CompanyData') . '</h3>
                    </div>
                    <div class="panel-body">');
$form->addHtml('<div class="row"><div class="col-md-6">');
$form->addText('company', [get_lang('CompanyName'), $plugin->get_lang('CompanyNameHelp')], true);
$form->addHtml('</div><div class="col-md-6">');
$form->addText('contact_manager', [$plugin->get_lang('ContactManager'), $plugin->get_lang('ContactManagerHelp')], true);
$form->addHtml('</div></div>');
$form->addHtml('<div class="row"><div class="col-md-6">');
$form->addText('position', [$plugin->get_lang('Position')], false);
$form->addHtml('</div><div class="col-md-6">');
$form->addText('experience_time', [$plugin->get_lang('ExperienceTime')], false);
$form->addHtml('</div></div>');
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
$form->addHtml('<div class="row"><div class="col-md-6">');
$form->addSelect('employment_category', $plugin->get_lang('EmploymentCategory'), $categories);
$form->addHtml('</div><div class="col-md-6">');
$form->addText('stakeholder', [$plugin->get_lang('Stakeholder'), $plugin->get_lang('StakeholderHelp')], false);
$form->addHtml('</div></div>');
$form->addText('area', [$plugin->get_lang('Area')], false);
$form->addText('department', [$plugin->get_lang('Department')], false);
$form->addText('headquarters', [$plugin->get_lang('Headquarters')], false);

$form->addHtml('</div></div>');


$form->addButton('register', $plugin->get_lang('RegisterUser'), null, 'primary', 'btn-block');
$form->applyFilter('__ALL__', 'Security::remove_XSS');

if ($form->validate()) {
    $values = $form->getSubmitValues(1);
}


$tool_name = get_lang('Registration');

// Custom pages
if (CustomPages::enabled() && CustomPages::exists(CustomPages::REGISTRATION)) {
    CustomPages::display(
        CustomPages::REGISTRATION,
        ['form' => $form, 'content' => $content]
    );
} else {
    if (!api_is_anonymous()) {
        // Saving user to course if it was set.
        if (!empty($course_code_redirect)) {
            $course_info = api_get_course_info($course_code_redirect);
        }
        CourseManager::redirectToCourse([]);
    }

    $tpl = new Template($tool_name);

    $tpl->assign('inscription_header', Display::page_header($tool_name));
    $tpl->assign('inscription_content', $content);
    $tpl->assign('form', $form->returnForm());
    $tpl->assign('hide_header', $hideHeaders);

    $inscription = $tpl->get_template('auth/inscription.tpl');
    $tpl->display($inscription);
}
