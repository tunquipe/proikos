<?php

/**
 * Vista previa de las páginas públicas (login y registro) para que el administrador,
 * que ya tiene la sesión iniciada, pueda personalizarlas con la barra lateral.
 * Los formularios mostrados no envían datos.
 */

require_once __DIR__.'/config.php';
require_once __DIR__.'/src/RegistrationFormManager.php';

use src\RegistrationFormManager;

api_protect_admin_script();

$page = $_GET['page'] ?? 'login';
$urlPlugin = api_get_path(WEB_PLUGIN_PATH).'proikos';

if ('register' === $page) {
    // Si hay un formulario de inscripción dinámico activo, se previsualiza ese.
    $formId = RegistrationFormManager::getActiveRegistrationFormId();
    if ($formId > 0) {
        header('Location: '.$urlPlugin.'/registration.php?form='.$formId.'&preview=1');
        exit;
    }

    $form = new FormValidator('registration-preview', 'post', '#', '', ['onsubmit' => 'return false;'], FormValidator::LAYOUT_INLINE);
    $form->addHtml('<div class="alert alert-info">Vista previa del formulario de registro original. Este formulario no envía datos.</div>');
    $form->addHtml('<div class="panel panel-default"><div class="panel-heading panel-user"><h3 class="panel-title">Información personal</h3></div><div class="panel-body">');
    $form->addHtml('<div class="row"><div class="col-md-6">');
    $form->addText('lastname', get_lang('LastName'), true);
    $form->addHtml('</div><div class="col-md-6">');
    $form->addText('firstname', get_lang('FirstName'), true);
    $form->addHtml('</div></div><div class="row"><div class="col-md-6">');
    $form->addText('email', get_lang('Email'), true);
    $form->addHtml('</div><div class="col-md-6">');
    $form->addSelect('gender', 'Género', ['' => 'Seleccione una opción', 'M' => 'Masculino', 'F' => 'Femenino']);
    $form->addHtml('</div></div></div></div>');
    $form->addButton('register', 'Registrarme', null, 'primary', 'btn-block');

    $template = new Template(get_lang('Registration'), false, false, false, false, true, true);
    $template->assign('url_plugin', $urlPlugin);
    $template->assign('picture', null);
    $template->assign('title', 'Registro de usuario');
    $template->assign('form', $form->returnForm());
    $content = $template->fetch($template->get_template('custompage/registration.tpl'));
    $template->assign('content', $content);
    $template->display_blank_template();
    exit;
}

$template = new Template(get_lang('SignIn'), false, false, false, false, true, true);
$template->assign('url_register', '#');
$template->assign('url_lost_password', '#');
$template->assign('mgs_flash', '');
$content = $template->fetch($template->get_template('custompage/login.tpl'));
// La vista previa no debe iniciar sesión ni enviar el formulario.
$content = str_replace('<form class="login100-form validate-form"', '<form class="login100-form validate-form" onsubmit="return false;"', $content);
$template->assign('content', $content);
$template->display_blank_template();
