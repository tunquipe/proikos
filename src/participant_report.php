<?php
require_once __DIR__ . '/../config.php';
api_block_anonymous_users();
$action = $_GET['action'] ?? null;
$plugin = ProikosPlugin::create();
$tool_name = $plugin->get_lang('ParticipantReport');
$actionLinks = null;
$htmlHeadXtra[] = api_get_js_simple(api_get_path(WEB_PLUGIN_PATH).'proikos/js/apexcharts/dist/apexcharts.min.js');
$htmlHeadXtra[] = api_get_js_simple(api_get_path(WEB_PLUGIN_PATH).'proikos/js/circle-progress.js');
$htmlHeadXtra[] = api_get_js_simple(api_get_path(WEB_PLUGIN_PATH).'proikos/js/progress.js');
$htmlHeadXtra[] = api_get_css(api_get_path(WEB_PLUGIN_PATH).'proikos/js/apexcharts/dist/apexcharts.css');
$htmlHeadXtra[] = api_get_css(api_get_path(WEB_PLUGIN_PATH).'proikos/css/style.css');
$tpl = new Template($tool_name);
$isAdmin = api_is_platform_admin();

if($isAdmin){
    $actionLinks .= Display::url(
        Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_PLUGIN_PATH) . 'proikos/start.php'
    );
    $actionLinks .= Display::url(
        Display::return_icon('excel.png', get_lang('ExportAsXLS'), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/participant_report.php?action=export_xls'
    );
}
$firstDate = date('Y-m-01').' 00:00:00';
$lastDate = date('Y-m-t').' 00:00:00';
$company = $plugin->getListEntity();
$listCompanies = [
    '-1' => get_lang('SelectAnOption')
];
foreach ($company as $row){
    $listCompanies[$row['id']] = $row['name_entity'];
}

switch ($action){
    case 'export_xls';
        if ($isAdmin) {
            $actionLinks = Display::url(
                Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
                api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/participant_report.php'
            );

            $form = new FormValidator(
                'report',
                'post',
                api_get_self().'?action=export_xls'
            );
            $form->addHeader($plugin->get_lang('GenerateParticipantReportXLS'));
            $form->addSelect('company',$plugin->get_lang('NameEntity'), $listCompanies);
            $form->addDatePicker('star_date',get_lang('DateStart'), ['value'=> $firstDate, 'id' => 'star_date']);
            $form->addDatePicker('end_date',get_lang('DateEnd'),['value'=> $lastDate, 'id' => 'end_date']);
            $form->addButton('generate',get_lang('Generate'),'table','primary');

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
                    $idCourse = $plugin->getTotalCourseSession($session['id'], $totalUsers);
                    if(!is_null($idCourse)){
                        $emptyEval[] = $plugin->getGradebookEvaluation($idCourse,$session['id']);
                    }
                }

                foreach ($emptyEval as $subArray) {
                    if (is_array($subArray)) {
                        $tmpEvals = array_merge($tmpEvals, $subArray);
                    }
                }

                foreach ($sessions as $session){
                    $students[$session['id']] = $plugin->getStudentForSession($session, $tmpEvals);
                    if ($students[$session['id']] !== null) {
                        $mergedStudents = array_merge($mergedStudents, $students[$session['id']]);
                    }
                }

                foreach ($mergedStudents as $row){
                    $combinedArray = array_merge($combinedArray, $row['courses'][0]['evaluations']);
                }

                foreach ($combinedArray as $key => $value) {
                    $combinedArray[$key] = 0;
                }

                $uniqueKeys = array_unique(array_keys($combinedArray));
                $numUniqueKeys = count($uniqueKeys);

                try {
                    $plugin->exportReportXLS($mergedStudents, $logoCompany, $numUniqueKeys, $combinedArray);
                    exit;
                } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
                    print_r($e);
                }
            }
            $tpl->assign('form', $form->returnForm());
            $content = $tpl->fetch('proikos/view/proikos_fora.tpl');
        }
    break;
    default:


        $form = new FormValidator(
            'report',
            'post'
        );
        $form->addHeader($plugin->get_lang('ParticipantReport'));
        //$form->addSelect('company',$plugin->get_lang('NameEntity'), $listCompanies);
        $form->addDatePicker('star_date',get_lang('DateStart'), ['value'=> $firstDate, 'id' => 'star_date']);
        $form->addDatePicker('end_date',get_lang('DateEnd'),['value'=> $lastDate, 'id' => 'end_date']);
        $genders = [
            '0' => 'Seleccione una opción',
            'M' => 'Masculino',
            'F' => 'Femenino'
        ];
        $genderInput = $form->addSelect('gender', $plugin->get_lang('Gender'), $genders);
        $stakeholders = [
            '0' => 'Seleccione una opción',
            '1' => 'Petroperu',
            '2' => 'Contratista',
            '3' => 'Cliente',
            '99' => 'Otros',
        ];
        $stakeholdersSelect = $form->addSelect('stakeholders', $plugin->get_lang('Stakeholder'), $stakeholders);
        $form->addHtml('<div id="option-builder" style="display: none;">');
        $companies = $plugin->getCompanies();
        $companiesInput = $form->addSelect('name_company', $plugin->get_lang('CompanyName'), $companies);
        $form->addHtml('</div>');

        $position = $plugin->getPositions(2);
        $positionInput = $form->addSelect('position_company', $plugin->get_lang('Position'), $position);
        $departments = $plugin->getPetroManagement();
        $departmentsSelect = $form->addSelect('department', [$plugin->get_lang('Department')], $departments);

        /*$courses = $plugin->getListCourses();
        $listCourses = [
            '0' => 'Todos los cursos'
        ];
        foreach ($courses as $course){
            $listCourses[$course['code']] = $course['title'];
        }

        $courseSelect = $form->addSelect('course', [$plugin->get_lang('Course')], $listCourses);*/
        $form->addRadio(
            'show_data',
            get_lang('Show'),
            [
                '1' => $plugin->get_lang('NumberOfUsers'),
                '2' => $plugin->get_lang('PercentageOfUsers')
            ]
        );
        $form->addButton('generate',$plugin->get_lang('ViewReport'),'refresh','primary');
        $tpl->assign('form', $form->returnForm());
        $urlPluginImages = api_get_path(WEB_PLUGIN_PATH).'proikos/images';
        $tpl->assign('url_plugin_image', $urlPluginImages);
        $content = $tpl->fetch('proikos/view/proikos_report.tpl');
        break;
}

$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$actionLinks])
);

$tpl->assign('content', $content);
$tpl->display_one_col_template();
