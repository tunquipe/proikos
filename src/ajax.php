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
        case 'get_status_of_students':
            $start_date = $_GET['star_date'] ?? null;
            $end_date = $_GET['end_date'] ?? null;
            $totalStudents = $plugin->getTotalStudentsPlatform();
            $sessions = $plugin->getSessionForDate($start_date, $end_date);
            $mergedStudents = [];
            foreach ($sessions as $session) {
                $students[$session['id']] = $plugin->getStudentForSession($session);
                if ($students[$session['id']] !== null) {
                    $mergedStudents = array_merge($mergedStudents, $students[$session['id']]);
                }
            }
            $totalCurrent = 0;
            $totalGlobal = 0;
            $approved = 0;
            $disapproved = 0;
            foreach ($mergedStudents as $student) {
                //var_dump($student['has_certificates']);
                if ($student['has_certificates'] == 1) {
                    $approved++;
                }
                if ($student['has_certificates'] == 0) {
                    $disapproved++;
                }
            }
            $totalCurrent = $approved + $disapproved;
            $percentageTotalCurrent = ($totalCurrent / $totalStudents) * 100;
            $percentageApproved = ($approved / $totalCurrent) * 100;
            $percentageDisapproved = ($disapproved / $totalCurrent) * 100;
            $result = [
                'total_global' => intval($totalStudents),
                'total_current' => $totalCurrent,
                'approved' => $approved,
                'disapproved' => $disapproved,
                'percentage_total_current' => round($percentageTotalCurrent, 2),
                'percentage_approved' => round($percentageApproved, 2),
                'percentage_disapproved' => round($percentageDisapproved, 2)
            ];
            //echo json_encode($mergedStudents);
            echo json_encode($result);
            break;
        case 'get_report_students':
            if (isset($_POST)) {
                $start_date = $_POST['star_date'] ?? null;
                $end_date = $_POST['end_date'] ?? null;
                $gender = $_POST['gender'] ?? null;
                $stakeholders = $_POST['stakeholders'] ?? null;
                $name_company = $_POST['name_company'] ?? null;
                $position_company = $_POST['position_company'] ?? null;
                $department = $_POST['department'] ?? null;

                $data = [
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'gender' => $gender,
                    'stakeholders' => $stakeholders,
                    'name_company' => $name_company,
                    'position_company' => $position_company,
                    'department' => $department
                ];

                $totalStudents = $plugin->getTotalStudentsPlatform();
                $sessions = $plugin->getSessionForDate($start_date, $end_date);
                $mergedStudents = [];

                foreach ($sessions as $session) {
                    $students[$session['id']] = $plugin->getStudentForSessionData($session['id'], $data);
                    if ($students[$session['id']] !== null) {
                        $mergedStudents = array_merge($mergedStudents, $students[$session['id']]);
                    }
                }


                $response = array(
                    'status' => 'success',
                    'data' => $mergedStudents,
                    'message' => 'Datos recibidos correctamente.'
                );


            } else {
                $response = array(
                    'status' => 'error',
                    'message' => 'No se recibieron datos.'
                );
            }
            echo json_encode($response);
            break;
    }
}
