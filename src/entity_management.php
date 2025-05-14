<?php

require_once __DIR__ . '/../config.php';
api_block_anonymous_users();
$action = $_GET['action'] ?? null;
$plugin = ProikosPlugin::create();
$tool_name = $plugin->get_lang('ManageEntities');
$message = null;
$actionLinks = null;
$htmlHeadXtra[] = api_get_css_asset('cropper/dist/cropper.min.css');
$htmlHeadXtra[] = api_get_asset('cropper/dist/cropper.min.js');

$tpl = new Template($tool_name);
$isAdmin = api_is_platform_admin();
$entities = null;

$allow = api_is_platform_admin() || api_is_teacher();
if (!$allow) {
    api_not_allowed(true);
}

if($isAdmin){
    $actionLinks .= Display::url(
        Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_PLUGIN_PATH) . 'proikos/start.php'
    );
    $actionLinks .= Display::url(
        Display::return_icon('new_class.png', get_lang('Add'), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_PLUGIN_PATH).'proikos/src/entity_management.php?action=create'
    );
    $entities = $plugin->getListEntity(true);
    $tpl->assign('entities', $entities);

    switch ($action) {
        case 'delete':
            $idEntity = $_GET['id'] ?? null;
            $res = $plugin->deleteEntity($idEntity);
            if ($res) {
                $url = api_get_path(WEB_PLUGIN_PATH).'proikos/src/entity_management.php';
                header('Location: '.$url);
            }
            break;
        case 'create':
            $actionLinks = Display::url(
                Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
                api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/entity_management.php'
            );
            $form = new FormValidator(
                'add_entity',
                'post',
                api_get_self() . '?action=' . Security::remove_XSS($_GET['action'])
            );
            $form->addHeader($plugin->get_lang('AddEntity'));
            $form->addText('name_entity', $plugin->get_lang('NameEntity'));
            $form->addText('business_name', $plugin->get_lang('NameBusiness'),false);
            $form->addText('ruc', $plugin->get_lang('RUC'),false);
            $form->addText('tax_residence', $plugin->get_lang('TaxResidence'), false);
            $form->addText('economic_activity', $plugin->get_lang('EconomicActivity'),false);
            $form->addNumeric('number_of_workers', $plugin->get_lang('NumberOfWorkers'));
            $form->addText('code_reference', $plugin->get_lang('CodeReference'));

            $form->addFile(
                'picture',
                [
                    $plugin->get_lang('Picture'),
                    $plugin->get_lang('PictureHelp')
                ]
            );

            $allowed_picture_types = api_get_supported_image_extensions(false);
            $form->addRule(
                'picture',
                get_lang('OnlyImagesAllowed').' ('.implode(', ', $allowed_picture_types).')',
                'filetype',
                $allowed_picture_types
            );

            $group = [];
            $group[] = $form->createElement('radio', 'status', null, get_lang('Active'), 1);
            $group[] = $form->createElement('radio', 'status', null, get_lang('Inactive'), 0);
            $form->addGroup($group, 'status', get_lang('Status'), null, false);
            $form->addButtonSave($plugin->get_lang('SaveEntity'));

            if ($form->validate()) {
                $values = $form->getSubmitValues();
                $res = $plugin->createEntity($values);
                if (isset($_FILES['picture'])) {
                    $plugin->saveImage($res, $_FILES['picture']);
                }
                if ($res) {
                    $url = api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/entity_management.php';
                    header('Location: ' . $url);
                }
            }
            $tpl->assign('form', $form->returnForm());
            break;
        case 'edit':
            $actionLinks = Display::url(
                Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
                api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/entity_management.php'
            );
            $idEntity = $_GET['id'] ?? null;
            $entity = $plugin->getEntity($idEntity);

            //edit form
            $form = new FormValidator(
                'edit_entity',
                'post',
                api_get_self() . '?action=' . Security::remove_XSS($_GET['action'])
            );

            $form->addHeader($plugin->get_lang('AddEntity'));
            $form->addText('name_entity', $plugin->get_lang('NameEntity'));
            $form->addText('business_name', $plugin->get_lang('NameBusiness'),false);
            $form->addText('ruc', $plugin->get_lang('RUC'),false);
            $form->addText('tax_residence', $plugin->get_lang('TaxResidence'), false);
            $form->addText('economic_activity', $plugin->get_lang('EconomicActivity'),false);
            $form->addNumeric('number_of_workers', $plugin->get_lang('NumberOfWorkers'));
            $form->addText('code_reference', $plugin->get_lang('CodeReference'));

            $form->addFile(
                'picture',
                [
                    $plugin->get_lang('Picture'),
                    $plugin->get_lang('PictureHelp')
                ]
            );

            $allowed_picture_types = api_get_supported_image_extensions(false);
            $form->addRule(
                'picture',
                get_lang('OnlyImagesAllowed').' ('.implode(', ', $allowed_picture_types).')',
                'filetype',
                $allowed_picture_types
            );

            if (!empty($entity['picture'])) {
                $form->addHtml('
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-8">'.
                    Display::img(
                        api_get_path(WEB_UPLOAD_PATH).$entity['picture'],
                        get_lang('Image'),
                        ['width' => 256]
                    ).'</div>
                </div>
            ');
            }

            $group = [];
            $group[] = $form->createElement('radio', 'status', null, get_lang('Active'), 1);
            $group[] = $form->createElement('radio', 'status', null, get_lang('Inactive'), 0);
            $form->addGroup($group, 'status', get_lang('Status'), null, false);
            $form->addHidden('id', $entity['id']);
            $form->addButtonSave($plugin->get_lang('SaveEntity'));

            $form->setDefaults($entity);

            if ($form->validate()) {
                $values = $form->exportValues();
                $res = $plugin->updateEntity($values);

                if (isset($_FILES['picture'])) {
                    $plugin->saveImage($values['id'], $_FILES['picture']);
                }

                if ($res) {
                    $url = api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/entity_management.php';
                    header('Location: ' . $url);
                }

            }

            $tpl->assign('form', $form->returnForm());

            break;
        default:
    }
}
if ($isAdmin) {
    $tpl->assign(
        'actions',
        Display::toolbarAction('toolbar', [$actionLinks])
    );
}
$tpl->assign('message', $message);
$tpl->assign('entities', $entities);
$content = $tpl->fetch('proikos/view/proikos_entity.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
