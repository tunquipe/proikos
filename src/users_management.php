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
        var_dump($user);

        $form = new FormValidator(
            'edit',
            'post',
            api_get_self() . '?action=' . Security::remove_XSS($_GET['action'])
        );
        $form->addHeader($plugin->get_lang('EditUser'));
        $form->addText('name_entity', $plugin->get_lang('NameEntity'));
        $form->addText('business_name', $plugin->get_lang('NameBusiness'),false);

        $form->addHidden('id', $idUser);
        $form->addButtonSave($plugin->get_lang('SaveUser'));
        $form->setDefaults($user);

        if ($form->validate()) {
            $values = $form->exportValues();
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
