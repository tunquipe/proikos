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

    $company = $plugin->getListEntity();
    $listCompanies = [
        '-1' => get_lang('SelectAnOption')
    ];
    foreach ($company as $row){
        $listCompanies[$row['id']] = $row['name_entity'];
    }

    $form = new FormValidator(
        'report',
        'post',
        api_get_self() . '?action=report'
    );
    $form->addHeader($plugin->get_lang('GenerateParticipantReport'));
    $form->addSelect('company',$plugin->get_lang('NameEntity'), $listCompanies);
    $form->addDatePicker('star_date',get_lang('DateStart'), ['value'=> $firstDate, 'id' => 'star_date']);
    $form->addDatePicker('end_date',get_lang('DateEnd'),['value'=> $lastDate, 'id' => 'end_date']);
    $form->addButton('generate',get_lang('Generate'));

    if ($form->validate()) {
        $values = $form->getSubmitValues();
        $starDate = $values['star_date'];
        $endDate = $values['end_date'];
        $idCompany = $_REQUEST['company'] ?? null;
        $infoCompany = $plugin->getEntity($idCompany);
        $logoCompany = api_get_path(SYS_UPLOAD_PATH).$infoCompany['picture'];
        $sessions = $plugin->getSessionForDate($starDate, $endDate);
        $mergedStudents = [];
        $combinedArray = $emptyEval = $tmpEvals = [];
        $idCourse = null;
        foreach ($sessions as $session){
            $totalUsers = $plugin->getCountUser($session['id']);
            $idCourse = $plugin->getIdCourseSession($session['id'], $totalUsers);
            if(!is_null($idCourse)){
                $emptyEval[] = $plugin->getGradebookEvaluation($idCourse,$session['id']);
            }
        }
        foreach ($emptyEval as $subArray) {
            $tmpEvals = array_merge($tmpEvals, $subArray);
        }

        foreach ($sessions as $session){
            $students[$session['id']] = $plugin->getStudentForSession($session, $tmpEvals);
            if ($students[$session['id']] !== null) {
                $mergedStudents = array_merge($mergedStudents, $students[$session['id']]);
            }
        }

        foreach ($mergedStudents as $row){
            //var_dump( $row['courses'][0]['evaluations']);
            $combinedArray = array_merge($combinedArray, $row['courses'][0]['evaluations']);
        }
        foreach ($combinedArray as $key => $value) {
            $combinedArray[$key] = 0;
        }
        //var_dump($combinedArray);
        $uniqueKeys = array_unique(array_keys($combinedArray));
        $numUniqueKeys = count($uniqueKeys);
        //exit;
        try {
            $plugin->exportReportXLS($mergedStudents, $logoCompany, $numUniqueKeys, $combinedArray);
        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            print_r($e);
        }
    }
    $tpl->assign('form', $form->returnForm());
}

$content = $tpl->fetch('proikos/view/proikos_fora.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
