<?php
require_once __DIR__ . '/../config.php';
api_block_anonymous_users();
$action = $_GET['action'] ?? null;
$plugin = ProikosPlugin::create();
$tool_name = $plugin->get_lang('ParticipantReport');
$actionLinks = null;
$tpl = new Template($tool_name);
$isAdmin = api_is_platform_admin();

if($isAdmin){
    $actionLinks .= Display::url(
        Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_PLUGIN_PATH) . 'proikos/start.php'
    );
}

if ($isAdmin) {
    $tpl->assign(
        'actions',
        Display::toolbarAction('toolbar', [$actionLinks])
    );
    $firstDate = date('Y-m-01').' 00:00:00';
    $lastDate = date('Y-m-t').' 00:00:00';

    $form = new FormValidator(
        'report',
        'post',
        api_get_self() . '?action=report'
    );
    $form->addHeader($plugin->get_lang('GenerateParticipantReport'));
    $form->addDatePicker('star_date',get_lang('DateStart'), ['value'=> $firstDate, 'id' => 'star_date']);
    $form->addDatePicker('end_date',get_lang('DateEnd'),['value'=> $lastDate, 'id' => 'end_date']);
    $form->addButton('generate',get_lang('Generate'));

    if ($form->validate()) {
        $values = $form->getSubmitValues();
        $starDate = $values['star_date'];
        $endDate = $values['end_date'];
        $sessions = $plugin->getSessionForDate($starDate, $endDate);
        $mergedStudents = [];
        foreach ($sessions as $session){
            $students[$session['id']] = $plugin->getStudentForSession($session);
            if ($students[$session['id']] !== null) {
                $mergedStudents = array_merge($mergedStudents, $students[$session['id']]);
            }
        }
        $plugin->exportReportXLS($mergedStudents);
    }
    $tpl->assign('form', $form->returnForm());
}

$content = $tpl->fetch('proikos/view/proikos_fora.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
