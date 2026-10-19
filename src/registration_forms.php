<?php

require_once __DIR__.'/../config.php';
require_once __DIR__.'/RegistrationFormManager.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use src\RegistrationFormManager;

api_protect_admin_script();

$plugin = ProikosPlugin::create();
RegistrationFormManager::ensureTables();
$manager = new RegistrationFormManager($plugin);

$action = $_GET['action'] ?? 'list';
$formId = (int) ($_GET['id'] ?? 0);
$baseUrl = api_get_path(WEB_PLUGIN_PATH).'proikos/src/registration_forms.php';
$publicUrl = api_get_path(WEB_PLUGIN_PATH).'proikos/registration.php';
$tokenPrefix = 'pk_reg_admin';

$htmlHeadXtra[] = api_get_css(api_get_path(WEB_PLUGIN_PATH).'proikos/css/style.css');
$htmlHeadXtra[] = api_get_css(api_get_path(WEB_PLUGIN_PATH).'proikos/css/registration_forms.css?v='.filemtime(__DIR__.'/../css/registration_forms.css'));

$redirect = function ($url) {
    header('Location: '.$url);
    exit;
};

$loadForm = function () use ($manager, $formId, $redirect, $baseUrl) {
    $form = $manager->getForm($formId);
    if (!$form) {
        Display::addFlash(Display::return_message('El formulario no existe.', 'error'));
        $redirect($baseUrl);
    }

    return $form;
};

// Acciones que modifican datos desde un enlace: requieren token.
$linkActions = ['delete', 'duplicate', 'publish', 'unpublish', 'set_registration', 'unset_registration'];
if (in_array($action, $linkActions, true)) {
    if (!Security::check_token('get', null, $tokenPrefix)) {
        Display::addFlash(Display::return_message(get_lang('NotAllowed'), 'error'));
        $redirect($baseUrl);
    }
    $form = 'unset_registration' === $action ? null : $loadForm();
    switch ($action) {
        case 'delete':
            $manager->deleteForm($formId);
            Display::addFlash(Display::return_message('Formulario eliminado.', 'confirmation'));
            break;
        case 'duplicate':
            $newId = $manager->duplicateForm($formId);
            Display::addFlash(Display::return_message('Formulario duplicado como borrador.', 'confirmation'));
            $redirect($baseUrl.'?action=builder&id='.$newId);
            break;
        case 'publish':
        case 'unpublish':
            if ('publish' === $action) {
                $warnings = $manager->checkFormConsistency($form, $manager->getFields($formId));
                if (!empty($warnings)) {
                    Display::addFlash(Display::return_message('No se puede publicar todavía:<br>'.implode('<br>', $warnings), 'error', false));
                    break;
                }
            }
            $manager->setStatus($formId, 'publish' === $action);
            Display::addFlash(Display::return_message('publish' === $action ? 'Formulario publicado.' : 'Formulario pasado a borrador.', 'confirmation'));
            break;
        case 'set_registration':
            $warnings = $manager->checkFormConsistency($form, $manager->getFields($formId));
            if (!empty($warnings)) {
                Display::addFlash(Display::return_message(implode('<br>', $warnings), 'error', false));
            } elseif ($manager->setRegistrationForm($formId)) {
                Display::addFlash(Display::return_message('"'.Security::remove_XSS($form['title']).'" es ahora el formulario de registro de la plataforma.', 'confirmation'));
            } else {
                Display::addFlash(Display::return_message('Solo un formulario publicado puede ser el formulario de registro.', 'warning'));
            }
            break;
        case 'unset_registration':
            $manager->unsetRegistrationForm();
            Display::addFlash(Display::return_message('La plataforma vuelve a usar el formulario de registro original.', 'confirmation'));
            break;
    }
    $redirect($baseUrl);
}

$tpl = new Template('Formularios de inscripción');
$actionLinks = '';
$content = '';
$token = Security::get_existing_token($tokenPrefix);

switch ($action) {
    case 'add':
    case 'edit':
        $form = 'edit' === $action ? $loadForm() : null;
        $fields = $form ? $manager->getFields($formId) : [];
        $actionLinks .= Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            $baseUrl
        );

        $formValidator = new FormValidator('reg_form_settings', 'post', $baseUrl.'?action='.$action.($form ? '&id='.$formId : ''));
        $formValidator->addHeader($form ? 'Configuración del formulario' : 'Nuevo formulario de inscripción');
        $formValidator->addText('title', 'Título', true, ['maxlength' => 255]);
        $formValidator->addTextarea('description', ['Descripción', 'Se muestra encima del formulario.'], ['rows' => 3]);
        $formValidator->addCheckBox('status', 'Estado', 'Publicado (visible para el público)');
        $formValidator->addText('submit_label', 'Texto del botón de envío', false, ['maxlength' => 60]);

        $formValidator->addHeader('Cuenta de usuario');
        $passwordOptions = [
            RegistrationFormManager::PASSWORD_MANUAL => 'El usuario escribe su contraseña (con confirmación y reglas de seguridad de Chamilo)',
            RegistrationFormManager::PASSWORD_AUTO => 'Chamilo genera la contraseña y la envía por correo',
            RegistrationFormManager::PASSWORD_FIELD => 'Asignar el valor de un campo del formulario (ej. número de documento)',
        ];
        $formValidator->addRadio('password_mode', 'Contraseña', $passwordOptions);

        $passwordFieldOptions = ['' => 'Seleccione un campo'];
        foreach ($fields as $field) {
            if (!$field['is_core'] && in_array($field['type'], ['text', 'number', 'tel', 'email'], true)) {
                $passwordFieldOptions[$field['field_key']] = $field['label'].' ('.$field['field_key'].')';
            }
        }
        $formValidator->addSelect(
            'password_field',
            ['Campo usado como contraseña', $form ? 'Solo campos de texto, número o teléfono. Debe ser obligatorio.' : 'Disponible después de crear los campos del formulario.'],
            $passwordFieldOptions
        );
        $formValidator->addCheckBox('send_credentials_email', 'Correo de bienvenida', 'Enviar al usuario un correo con sus datos de acceso');
        $formValidator->addCheckBox('auto_login', 'Después del registro', 'Iniciar sesión automáticamente');
        $formValidator->addText('redirect_url', ['URL de redirección', 'Opcional. Por defecto: catálogo de cursos.'], false);
        $formValidator->addTextarea('success_message', ['Mensaje de confirmación', 'Se muestra si no se inicia sesión automáticamente.'], ['rows' => 3]);

        $entities = ['' => 'Ninguna'];
        foreach ($plugin->getListEntity() as $entity) {
            $entities[$entity['code_reference']] = $entity['name_entity'];
        }
        $formValidator->addSelect('code_reference', ['Entidad asociada', 'Código de referencia que se asignará al usuario.'], $entities);

        $formValidator->addButtonSave($form ? get_lang('Save') : 'Crear y diseñar campos');
        $formValidator->addRule('redirect_url', 'Ingrese una URL válida.', 'callback', function ($value) {
            return '' === trim($value) || false !== filter_var($value, FILTER_VALIDATE_URL);
        });
        $formValidator->addFormRule(function ($values) {
            if (RegistrationFormManager::PASSWORD_FIELD === ($values['password_mode'] ?? '') && empty($values['password_field'])) {
                return ['password_field' => 'Seleccione el campo que se usará como contraseña.'];
            }

            return true;
        });

        $defaults = $form
            ? array_merge($form['settings'], ['title' => $form['title'], 'description' => $form['description'], 'status' => $form['status']])
            : array_merge(RegistrationFormManager::defaultSettings(), ['status' => 0]);
        $formValidator->setDefaults($defaults);

        if ($formValidator->validate()) {
            $values = $formValidator->exportValues();
            $savedId = $manager->saveForm($values, $form ? $formId : 0);
            Display::addFlash(Display::return_message('Formulario guardado.', 'confirmation'));
            if (!empty($values['status'])) {
                // Solo se publica si ya tiene lo necesario para crear la cuenta.
                $warnings = $manager->checkFormConsistency($manager->getForm($savedId), $manager->getFields($savedId));
                if (!empty($warnings)) {
                    $manager->setStatus($savedId, false);
                    Display::addFlash(Display::return_message('Se guardó como borrador:<br>'.implode('<br>', $warnings), 'warning', false));
                }
            }
            $redirect($baseUrl.'?action='.($form ? 'list' : 'builder&id='.$savedId));
        }
        $content = $formValidator->returnForm();
        break;

    case 'builder':
        $form = $loadForm();
        $fields = $manager->getFields($formId);
        $errors = [];

        if ('POST' === $_SERVER['REQUEST_METHOD']) {
            $input = json_decode($_POST['fields_json'] ?? '', true);
            if (!Security::check_token('post', null, $tokenPrefix)) {
                $errors[] = get_lang('NotAllowed');
            } elseif (!is_array($input)) {
                $errors[] = 'No se recibió la definición de campos.';
            } else {
                [$normalized, $errors] = $manager->normalizeFieldsDefinition($input, $fields);
                if (empty($errors) && RegistrationFormManager::STATUS_PUBLISHED === $form['status']) {
                    // Publicado: no se puede dejar sin los datos que crean la cuenta.
                    $errors = $manager->checkFormConsistency($form, $normalized);
                    if (!empty($errors)) {
                        $errors[] = 'El formulario está publicado. Páselo a borrador si necesita quitar esos campos.';
                    }
                }
                if (empty($errors)) {
                    $manager->saveFields($formId, $normalized);
                    $savedFields = $manager->getFields($formId);
                    $warnings = $manager->checkFormConsistency($form, $savedFields);
                    Display::addFlash(Display::return_message('Campos guardados.', 'confirmation'));
                    if (!empty($warnings)) {
                        Display::addFlash(Display::return_message(implode('<br>', $warnings), 'warning', false));
                    }
                    $redirect($baseUrl.'?action=builder&id='.$formId);
                }
                // Conservar lo que el administrador estaba editando.
                $fields = $input;
            }
        }

        $actionLinks .= Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            $baseUrl
        );
        $actionLinks .= Display::url(
            Display::return_icon('settings.png', 'Configuración del formulario', [], ICON_SIZE_MEDIUM),
            $baseUrl.'?action=edit&id='.$formId
        );
        $actionLinks .= Display::url(
            Display::return_icon('preview_view.png', 'Vista previa', [], ICON_SIZE_MEDIUM),
            $publicUrl.'?form='.$formId.'&preview=1',
            ['target' => '_blank']
        );

        $tpl->assign('builder_js', api_get_path(WEB_PLUGIN_PATH).'proikos/js/registration_builder.js?v='.filemtime(__DIR__.'/../js/registration_builder.js'));
        $tpl->assign('form', $form);
        $tpl->assign('errors', $errors);
        $tpl->assign('token', $token);
        $tpl->assign('total_submissions', $manager->countSubmissions($formId));
        $tpl->assign('builder_data', json_encode([
            'fields' => array_values($fields),
            'catalog' => RegistrationFormManager::builderCatalog(),
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
        $tpl->assign('action_url', $baseUrl.'?action=builder&id='.$formId);
        $content = $tpl->fetch('proikos/view/proikos_registration_builder.tpl');
        break;

    case 'responses':
    case 'export':
        $form = $loadForm();
        $fields = array_values(array_filter($manager->getFields($formId), function ($field) {
            return !$field['is_core'] && 'section' !== $field['type'];
        }));
        $search = trim($_GET['search'] ?? '');

        if ('export' === $action) {
            [$submissions] = $manager->getSubmissions($formId, $search, 0, 0);
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Respuestas');
            $headers = array_merge(['#', 'Fecha de registro', 'Apellidos', 'Nombres', 'Correo', 'Usuario'], array_column($fields, 'label'), ['IP']);
            $sheet->fromArray($headers, null, 'A1');
            $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->getFont()->setBold(true);
            $row = 2;
            foreach ($submissions as $index => $submission) {
                $line = [
                    $index + 1,
                    api_get_local_time($submission['created_at']),
                    $submission['lastname'],
                    $submission['firstname'],
                    $submission['email'],
                    $submission['username'],
                ];
                foreach ($fields as $field) {
                    $line[] = RegistrationFormManager::formatAnswer($field, $submission['answers'][$field['id']] ?? null);
                }
                $line[] = $submission['ip'];
                // Como texto explícito para no perder ceros iniciales (DNI, teléfonos).
                foreach ($line as $col => $value) {
                    $sheet->setCellValueExplicitByColumnAndRow($col + 1, $row, (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
                $row++;
            }
            foreach ($sheet->getColumnIterator() as $column) {
                $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            }
            $filename = 'respuestas_'.RegistrationFormManager::slugify($form['title']).'_'.date('Ymd_His').'.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="'.$filename.'"');
            header('Cache-Control: max-age=0');
            (new Xlsx($spreadsheet))->save('php://output');
            exit;
        }

        $perPage = 50;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        [$submissions, $total] = $manager->getSubmissions($formId, $search, ($page - 1) * $perPage, $perPage);
        $totalPages = max(1, (int) ceil($total / $perPage));

        $rows = [];
        foreach ($submissions as $submission) {
            $cells = [];
            foreach ($fields as $field) {
                $raw = $submission['answers'][$field['id']] ?? null;
                $text = Security::remove_XSS(RegistrationFormManager::formatAnswer($field, $raw));
                if ('file' === $field['type'] && $raw) {
                    $text = Display::url(
                        '<em class="fa fa-paperclip"></em> '.$text,
                        $baseUrl.'?action=download&id='.$formId.'&submission='.$submission['id'].'&field='.$field['id']
                    );
                }
                $cells[] = $text;
            }
            $rows[] = [
                'submission' => $submission,
                'date' => api_get_local_time($submission['created_at']),
                'cells' => $cells,
                'user_url' => api_get_path(WEB_CODE_PATH).'admin/user_information.php?user_id='.$submission['user_id'],
            ];
        }

        $actionLinks .= Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            $baseUrl
        );
        $actionLinks .= Display::url(
            Display::return_icon('export_excel.png', 'Exportar a Excel', [], ICON_SIZE_MEDIUM),
            $baseUrl.'?action=export&id='.$formId.'&search='.urlencode($search)
        );

        $tpl->assign('form', $form);
        $tpl->assign('fields', $fields);
        $tpl->assign('rows', $rows);
        $tpl->assign('total', $total);
        $tpl->assign('search', Security::remove_XSS($search));
        $tpl->assign('page', $page);
        $tpl->assign('total_pages', $totalPages);
        $tpl->assign('base_url', $baseUrl);
        $content = $tpl->fetch('proikos/view/proikos_registration_responses.tpl');
        break;

    case 'download':
        $form = $loadForm();
        $submissionId = (int) ($_GET['submission'] ?? 0);
        $answer = $manager->getAnswer($submissionId, (int) ($_GET['field'] ?? 0));
        $file = $answer ? json_decode($answer['value'], true) : null;
        if (!$file || (int) $answer['form_id'] !== $formId || empty($file['path'])) {
            api_not_allowed(true);
        }
        $baseDir = realpath(RegistrationFormManager::getUploadDir());
        $path = realpath(RegistrationFormManager::getUploadDir().$file['path']);
        if (!$baseDir || !$path || 0 !== strpos($path, $baseDir.DIRECTORY_SEPARATOR) || !is_file($path)) {
            api_not_allowed(true);
        }
        DocumentManager::file_send_for_download($path, true, $file['name']);
        exit;

    case 'list':
    default:
        $forms = $manager->getForms();
        $hasRegistration = false;
        foreach ($forms as &$item) {
            $item['public_url'] = $publicUrl.'?form='.$item['id'];
            $item['preview_url'] = $publicUrl.'?form='.$item['id'].'&preview=1';
            $item['updated_local'] = $item['updated_at'] ? api_get_local_time($item['updated_at']) : '';
            if ($item['is_registration'] && RegistrationFormManager::STATUS_PUBLISHED === $item['status']) {
                $hasRegistration = true;
            }
        }
        unset($item);

        $actionLinks .= Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_PLUGIN_PATH).'proikos/start.php'
        );
        $actionLinks .= Display::url(
            Display::return_icon('add.png', 'Nuevo formulario', [], ICON_SIZE_MEDIUM),
            $baseUrl.'?action=add'
        );

        $tpl->assign('forms', $forms);
        $tpl->assign('has_registration', $hasRegistration);
        $tpl->assign('base_url', $baseUrl);
        $tpl->assign('token', $token);
        $tpl->assign('registration_url', $publicUrl);
        $content = $tpl->fetch('proikos/view/proikos_registration_forms.tpl');
        break;
}

$tpl->assign('actions', Display::toolbarAction('toolbar', [$actionLinks]));
$tpl->assign('content', $content);
$tpl->display_one_col_template();
