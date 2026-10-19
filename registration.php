<?php

/**
 * Formulario de inscripción dinámico (público).
 *
 * registration.php            -> formulario elegido como registro de la plataforma
 * registration.php?form=ID    -> un formulario publicado específico
 * registration.php?form=ID&preview=1 -> vista previa para administradores (no crea usuarios)
 */

if (!empty($_POST['language'])) {
    $_GET['language'] = $_POST['language'];
}
require_once __DIR__.'/config.php';
require_once __DIR__.'/src/RegistrationFormManager.php';

use src\RegistrationFormManager;

$plugin = ProikosPlugin::create();
if ('true' !== $plugin->get('tool_enable')) {
    api_not_allowed(true);
}

$manager = new RegistrationFormManager($plugin);
$formId = (int) ($_GET['form'] ?? 0);
$isPreview = !empty($_GET['preview']) && api_is_platform_admin();
$urlPlugin = api_get_path(WEB_PLUGIN_PATH).'proikos';

if ($formId > 0) {
    $form = $manager->getForm($formId);
    if ($form && !$isPreview && RegistrationFormManager::STATUS_PUBLISHED !== $form['status']) {
        $form = null;
    }
} else {
    $form = $manager->getRegistrationForm();
}

if ($form && !$isPreview && !empty($manager->checkFormConsistency($form, $manager->getFields($form['id'])))) {
    // Le faltan datos para crear la cuenta (ej. sin correo): no se ofrece.
    $form = null;
}

if (!$form) {
    api_not_allowed(true, Display::return_message('El formulario de inscripción no está disponible.', 'warning'));
}

if (!$isPreview && !api_is_anonymous()) {
    // Ya tiene una sesión iniciada.
    header('Location: '.api_get_path(WEB_PATH).'user_portal.php');
    exit;
}

$htmlHeadXtra[] = api_get_css($urlPlugin.'/css/style.css');
$htmlHeadXtra[] = api_get_css($urlPlugin.'/css/registration_forms.css?v='.filemtime(__DIR__.'/css/registration_forms.css'));

$fields = $manager->getFields($form['id']);
$settings = $form['settings'];
$title = Security::remove_XSS($form['title']).($isPreview ? ' <small>(vista previa)</small>' : '');
$query = ['form' => $form['id']];
if ($isPreview) {
    $query['preview'] = 1;
}
$action = api_get_self().'?'.http_build_query($query);

$formValidator = $manager->buildPublicForm($form, $fields, $action);
$resultForm = null;

if ($formValidator->validate()) {
    $values = $formValidator->getSubmitValues();

    if ($isPreview) {
        $resultForm = new FormValidator('proikos_reg_form_result');
        $resultForm->addHtml(Display::return_message('Vista previa: todos los datos son válidos. No se creó ningún usuario.', 'success'));
        $resultForm->addHtml('<a href="'.$action.'" class="btn btn-default btn-block">Volver al formulario</a>');
    } else {
        $userId = $manager->register($form, $fields, $values, $_FILES);
        if ($userId) {
            Security::clear_token('pk_reg_form');
            $redirectUrl = $settings['redirect_url'] ?: api_get_path(WEB_CODE_PATH).'auth/courses.php';

            if (!empty($settings['auto_login'])) {
                $manager->loginUser($userId);
                header('Location: '.$redirectUrl);
                exit;
            }

            $message = $settings['success_message']
                ? nl2br(Security::remove_XSS($settings['success_message']))
                : 'Su registro se completó correctamente.'.(!empty($settings['send_credentials_email']) ? ' Le enviamos un correo con sus datos de acceso.' : '');
            $resultForm = new FormValidator('proikos_reg_form_result');
            $resultForm->addHtml(Display::return_message($message, 'success', false));
            $resultForm->addHtml('<a href="'.api_get_path(WEB_PATH).'" class="btn btn-primary btn-block">Iniciar sesión</a>');
        } else {
            $formValidator->setElementError('pk_submit', 'No se pudo completar el registro. Verifique sus datos e intente nuevamente.');
        }
    }
}

$displayForm = $resultForm ?: $formValidator;

if (CustomPages::enabled() && CustomPages::exists(CustomPages::REGISTRATION)) {
    CustomPages::display(
        CustomPages::REGISTRATION,
        [
            'form' => $displayForm,
            'content' => null,
            'title' => $title,
            'url_plugin' => $urlPlugin,
            'picture' => null,
        ]
    );
} else {
    $tpl = new Template(get_lang('Registration'));
    $tpl->assign('inscription_header', Display::page_header($title));
    $tpl->assign('inscription_content', '');
    $tpl->assign('form', $displayForm->returnForm());
    $tpl->assign('hide_header', false);
    $tpl->display($tpl->get_template('auth/inscription.tpl'));
}
