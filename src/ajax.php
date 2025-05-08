<?php

require_once __DIR__ . '/../config.php';
$action = $_GET['action'] ?? null;

$plugin = ProikosPlugin::create();

if ($action) {

    switch ($action) {
        case 'get_position':
            $idStakeholders = $_GET['id_stakeholders'] ?? null;
            $res = $plugin->getPositions($idStakeholders);
            header('Content-Type: application/json');
            echo json_encode($res);
            break;
        case 'get_administrator':
            $idCompany = $_GET['id_company'] ?? null;
            $res = $plugin->getCompaniesAdministrator($idCompany);
            header('Content-Type: application/json');
            echo json_encode($res);
            break;
        case 'get_management':
            $idArea = $_GET['id_area'] ?? null;
            $res = $plugin->getManagementArea($idArea);
            header('Content-Type: application/json');
            echo json_encode($res);
            break;
        case 'get_headquarters':
            $idManagement = $_GET['id_management'] ?? null;
            $res = $plugin->getHeadquarters(false, $idManagement);
            header('Content-Type: application/json');
            echo json_encode($res);
            break;
        case 'get_students_approved_disapproved':
            if (isset($_POST)) {
                $start_date = $_POST['start_date'] ?? null;
                $end_date = $_POST['end_date'] ?? null;
                $list = $plugin->getStudentsApprovedDisapproved($start_date,$end_date);

                $totalApproved = 0;
                $totalDisapproved = 0;

                foreach ($list as $item) {
                    $totalApproved += $item['approved'];
                    $totalDisapproved += $item['disapproved'];
                }

                $totalArray = [
                    'total' => $totalApproved + $totalDisapproved,
                    'totalApproved' => $totalApproved,
                    'totalDisapproved' => $totalDisapproved,
                ];
                echo json_encode($totalArray);
            }
            break;

        case 'get_course_approved':
            if (isset($_POST)) {
                $start_date = $_POST['start_date'] ?? null;
                $end_date = $_POST['end_date'] ?? null;
                $list = $plugin->getStudentsApprovedDisapproved($start_date,$end_date);
                echo json_encode($list);
            }
            break;
        case 'get_certificate_users':
            if (isset($_POST)) {
                $start_date = $_POST['start_date'] ?? null;
                $end_date = $_POST['end_date'] ?? null;
                $users = $plugin->getParticipatingUsersCertificate($start_date, $end_date);
                echo json_encode($users);
            }
            break;
        case 'get_report_session':
            if (isset($_POST)) {
                $start_date = $_POST['start_date'] ?? null;
                $end_date = $_POST['end_date'] ?? null;
                $sessions = $plugin->getSessionRelCourseUsers($start_date, $end_date);
                echo json_encode($sessions);
            }
            break;
        case 'get_participating_users':
            if (isset($_POST)) {
                $start_date = $_POST['start_date'] ?? null;
                $end_date = $_POST['end_date'] ?? null;
                $users = $plugin->getParticipatingUsers($start_date, $end_date);
                echo json_encode($users);
            }
            break;
        case 'get_participating_stakeholders':
            if (isset($_POST)) {
                $start_date = $_POST['start_date'] ?? null;
                $end_date = $_POST['end_date'] ?? null;
                $users = $plugin->getUserParticipatesExam($start_date, $end_date);
                $list = [];
                foreach ($users as $user){
                    $idStakeholder = $plugin->getStakeholderForUserId($user['user_id']);
                    $list[$user['user_id']] = [
                        'stakeholder_id' => $idStakeholder,
                        'stakeholder_name' => $plugin->getStakeholderTypeText($idStakeholder)
                    ];
                }
                $conteoPorTipo = [];
                foreach ($list as $item) {
                    $stakeholderName = $item['stakeholder_name'];
                    if (!isset($conteoPorTipo[$stakeholderName])) {
                        $conteoPorTipo[$stakeholderName] = 1;
                    } else {
                        $conteoPorTipo[$stakeholderName]++;
                    }
                }
                echo json_encode($conteoPorTipo);
            }
            break;
        case 'get_report_students':
            if (isset($_POST)) {
                $start_date = $_POST['start_date'] ?? null;
                $end_date = $_POST['end_date'] ?? null;
                $gender = $_POST['gender'] ?? null;

                $data = [
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                ];

                $totalStudents = intval($plugin->getTotalStudentsPlatform());
                $sessions = $plugin->getSessionRelCourseUsers($start_date, $end_date);
                $totalCurrent = 0;
                $totalDisapproved = 0;
                foreach ($sessions as $item) {
                    //var_dump($item);
                    if($item['nbr_users']>=1){
                        $totalCurrent+= intval($item['nbr_users']);
                    }
                }
                $result = [
                    'total_global' => $totalStudents-$totalCurrent,
                    'total_current' => $totalCurrent,
                ];
                echo json_encode($result);
            }
            break;
        case 'get_exams_students':
            if (isset($_POST)) {
                $start_date = $_POST['start_date'] ?? null;
                $end_date = $_POST['end_date'] ?? null;
                $list = $plugin->getExamsSession($start_date, $end_date);
                $result = [];
                foreach ($list as $row){
                    $result[] = $plugin->processStudentList(70,$row['exercises_id'],$row['course_code'],$row['session_id'], $row['title']);
                }
                $unifiedArray = [];
                $finalArray = [];

                // Recorre el array original
                foreach ($result as $item) {
                    $title = $item['title'];

                    if (!isset($unifiedArray[$title])) {
                        // Si el título no existe en el array unificado, lo agrega como un nuevo elemento
                        $unifiedArray[$title] = $item;
                    } else {
                        // Si el título ya existe, suma los valores correspondientes
                        $unifiedArray[$title]['exam_taken'] += $item['exam_taken'];
                        $unifiedArray[$title]['exam_not_taken'] += $item['exam_not_taken'];
                        $unifiedArray[$title]['total_students'] += $item['total_students'];
                    }
                }

                // Convierte el array unificado en un array indexado
                $unifiedArray = array_values($unifiedArray);

                foreach ($unifiedArray as $element){
                    if($element['exam_taken'] >= 1){
                        $finalArray[] = $element;
                    }
                }
                echo json_encode($finalArray);
            }
            break;
        case 'get_company_by_ruc':
            if (isset($_POST)) {
                $ruc = $_POST['ruc'] ?? null;

                if (empty($ruc)) {
                    echo json_encode(['error' => 'RUC is required']);
                    exit;
                }

                $nameCompany = $plugin->contratingCompaniesModel()->getDataByRUC($ruc);
                echo json_encode([
                    'name_company' => $nameCompany
                ]);
                exit;
            }
            break;
        case 'download_user_uploaded_documents':
            $userId = (int) $_GET['user_id'];
            $courseCode = basename($_GET['course_code']);
            $filename = basename($_GET['filename']);
            $basePath = api_get_path(SYS_APP_PATH) . 'upload/proikos_user_documents/';
            $filePath = $basePath . $userId . '/' . $courseCode . '/' . $filename;

            if (!file_exists($filePath)) {
                header("HTTP/1.0 404 Not Found");
                echo "Archivo no encontrado.";
                exit;
            }

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
            break;
    }
}
