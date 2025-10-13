<?php

require_once __DIR__ . '/../config.php';
$action = $_GET['action'] ?? null;

$plugin = ProikosPlugin::create();
// ────── helper interno para nombres seguros ──────────────────────
function sanitize_filename(string $name): string
{
    // Convierte espacios y acentos, luego solo deja letras, números, _ y -
    $name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
    $name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $name);
    return trim($name, '_');
}

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
                $list = $plugin->getStudentsApprovedDisapproved($start_date, $end_date);

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
                $list = $plugin->getStudentsApprovedDisapproved($start_date, $end_date);
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
                foreach ($users as $user) {
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
                    if ($item['nbr_users'] >= 1) {
                        $totalCurrent += intval($item['nbr_users']);
                    }
                }
                $result = [
                    'total_global' => $totalStudents - $totalCurrent,
                    'total_current' => $totalCurrent,
                ];
                echo json_encode($result);
            }
            break;
        case 'validate_company_code':
            if (isset($_POST)) {
                $companyCode = $_POST['company_code'] ?? null;
                $companyId = $_POST['id_company'] ?? null;
                $compare = $plugin->contratingCompaniesModel()->getValidateCodeCompany($companyCode, $companyId);
                $message = $plugin->get_lang('CodeIsCorrect');
                if (!$compare) {
                    $message = $plugin->get_lang('InvalidCodeCompany');
                }
                $json = [
                    'success' => $compare,
                    'message' => $message,
                ];
                echo json_encode($json);
            }

            break;
        case 'get_exams_students':
            if (isset($_POST)) {
                $start_date = $_POST['start_date'] ?? null;
                $end_date = $_POST['end_date'] ?? null;
                $list = $plugin->getExamsSession($start_date, $end_date);
                $result = [];
                foreach ($list as $row) {
                    $result[] = $plugin->processStudentList(70, $row['exercises_id'], $row['course_code'], $row['session_id'], $row['title']);
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

                foreach ($unifiedArray as $element) {
                    if ($element['exam_taken'] >= 1) {
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
        case 'download_session_uploaded_documents':

            $sessionId = (int)($_GET['session_id'] ?? 0);
            if ($sessionId === 0) {
                header("HTTP/1.0 400 Bad Request");
                exit('Parámetro session_id inválido.');
            }
            $basePath = api_get_path(SYS_APP_PATH) . 'upload/proikos_user_documents/';
            $users = SessionManager::get_users_by_session($sessionId); // ← tu helper
            if (empty($users)) {
                header("HTTP/1.0 404 Not Found");
                exit('No hay usuarios en esta sesión.');
            }

            $zip = new ZipArchive();
            $zipFileName = 'session_' . $sessionId . '_user_documents.zip';
            $zipFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipFileName;

            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                header("HTTP/1.0 500 Internal Server Error");
                exit('No se pudo crear el archivo ZIP.');
            }

            $totalFilesAdded = 0;

            foreach ($users as $u) {
                $userId = (int)$u['user_id'];
                $userFolderPath = $basePath . $userId . '/' . $sessionId;

                if (!is_dir($userFolderPath)) {
                    continue;
                }

                $zipInnerFolder = $userId . '_' . sanitize_filename($u['lastname'] . '_' . $u['firstname']);

                foreach (glob($userFolderPath . '/*') as $file) {
                    if (is_file($file)) {
                        $zip->addFile($file, $zipInnerFolder . '/' . basename($file));
                        $totalFilesAdded++;
                    }
                }
            }

            $zip->close();

            if ($totalFilesAdded === 0) {
                @unlink($zipFilePath);
                header("HTTP/1.0 404 Not Found");
                exit('No se encontraron documentos para ningún usuario.');
            }

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
            header('Content-Length: ' . filesize($zipFilePath));
            readfile($zipFilePath);
            @unlink($zipFilePath);
            exit;
        case 'download_user_uploaded_documents':
            $userId = (int)$_GET['user_id'];
            $sessionId = $_GET['session_id'];
            $basePath = api_get_path(SYS_APP_PATH) . 'upload/proikos_user_documents/';
            $dirPath = $basePath . $userId . '/' . $sessionId;

            if (!is_dir($dirPath)) {
                header("HTTP/1.0 404 Not Found");
                echo "Directorio no encontrado.";
                exit;
            }

            // Generate zip with all files in the directory
            $zip = new ZipArchive();
            $zipFileName = ($_GET['user_full_name'] ?? ('user_documents_' . $userId . '_' . $sessionId)) . '.zip';
            $zipFilePath = $basePath . $zipFileName;
            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                header("HTTP/1.0 500 Internal Server Error");
                echo "Error al crear el archivo zip.";
                exit;
            }

            $files = glob($dirPath . '/*'); // Get all files in the directory
            foreach ($files as $file) {
                if (is_file($file)) {
                    $zip->addFile($file, basename($file)); // Add file to zip
                }
            }
            $zip->close();

            // Set headers for download
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
            header('Content-Length: ' . filesize($zipFilePath));
            header('Content-Transfer-Encoding: binary');
            readfile($zipFilePath);

            exit;
            break;
        case 'upload_user_certificates':
            $userId = api_get_user_id();
            $sessionId = $_GET['session_id'] ?? null;

            if (empty($sessionId)) {
                echo json_encode(['error' => 'Session ID is required']);
                exit;
            }

            $baseUploadDir = api_get_path(SYS_APP_PATH) . 'upload/proikos_user_documents/';
            $userCourseDir = $baseUploadDir . $userId . '/' . $sessionId . '/';

            if (!file_exists($userCourseDir)) {
                mkdir($userCourseDir, 0775, true);
            }

            $userData = [
                'attachments' => [],
            ];
            $documentMapAttachCertificates = $plugin::ATTACH_CERTIFICATES_FILE_MODE;
            foreach ($documentMapAttachCertificates as $inputName => $documentName) {
                $uploadedFile = $_FILES['certificate_' . $inputName];
                if (!empty($uploadedFile) && !empty($uploadedFile['tmp_name']) && $uploadedFile['error'] === UPLOAD_ERR_OK) {
                    $originalName = basename($uploadedFile['name']);
                    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                    $fileName = $documentName . '.' . $extension;
                    $destination = $userCourseDir . $fileName;

                    // remove all files with the same name
                    $files = glob($userCourseDir . $documentName . '.*');
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            unlink($file);
                        }
                    }

                    $userData['attachments'][] = [
                        'session_id' => $sessionId,
                        'request_attach_certificates' => [
                            $inputName => $plugin::ATTACH_CERTIFICATES[$inputName],
                        ],
                    ];

                    move_uploaded_file($uploadedFile['tmp_name'], $destination);
                }
            }

            $documentMapAttachCertificatesAltoRiesgo = $plugin::ATTACH_CERTIFICATES_ALTO_RIESGO_FILE_MODE;
            foreach ($documentMapAttachCertificatesAltoRiesgo as $inputName => $documentName) {
                $uploadedFile = $_FILES['optional_certificate_' . $inputName];
                if (!empty($uploadedFile) && !empty($uploadedFile['tmp_name']) && $uploadedFile['error'] === UPLOAD_ERR_OK) {
                    $originalName = basename($uploadedFile['name']);
                    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                    $fileName = $documentName . '.' . $extension;
                    $destination = $userCourseDir . $fileName;

                    // remove all files with the same name
                    $files = glob($userCourseDir . $documentName . '.*');
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            unlink($file);
                        }
                    }

                    $userData['attachments'][] = [
                        'session_id' => $sessionId,
                        'optional_request_attach_certificates' => [
                            $inputName => $plugin::ATTACH_CERTIFICATES_ALTO_RIESGO[$inputName],
                        ],
                    ];

                    move_uploaded_file($uploadedFile['tmp_name'], $destination);
                }
            }

            $plugin->updateUserMetadata($userId, $userData);

            $response = [
                'success' => true,
                'message' => 'Archivos subidos correctamente.',
            ];

            break;
        case 'get_session_exercises':
            $sessionId = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;
            $response = ['success' => false, 'exercises' => []];

            if ($sessionId > 0) {
                try {
                    $exercises = $plugin->getSessionExercises($sessionId);
                    $response = [
                        'success' => true,
                        'exercises' => $exercises
                    ];
                } catch (Exception $e) {
                    error_log('Error getting session exercises: ' . $e->getMessage());
                    $response = [
                        'success' => false,
                        'error' => 'Error interno del servidor'
                    ];
                }
            }

            header('Content-Type: application/json');
            echo json_encode($response);
            break;
        case 'verify_check':
            header('Content-Type: application/json');

            if (!isset($_POST['user_id']) || !isset($_POST['session_id'])) {
                echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                exit;
            }
            $userId = intval($_POST['user_id']);
            $sessionId = intval($_POST['session_id']);

            $tableCheck = Database::get_main_table($plugin::TABLE_PROIKOS_CHECK_DOCS);

            $sql = "SELECT check_document FROM $tableCheck WHERE user_id = $userId AND session_id = $sessionId";
            $result = Database::query($sql);
            $documentCheck = 0;
            if (Database::num_rows($result) > 0) {
                while ($row = Database::fetch_array($result)) {
                    $documentCheck = $row['check_document'];
                }
                echo json_encode(['success' => true, 'check_document' => intval($documentCheck)]);
            }

            break;
        case 'update_check':
            if (!isset($_POST['user_id']) || !isset($_POST['session_id']) || !isset($_POST['check_document'])) {
                echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                exit;
            }

            $userId = intval($_POST['user_id']);
            $sessionId = intval($_POST['session_id']);
            $checkDocument = intval($_POST['check_document']);
            $userIDCheck = api_get_user_id();

            $tableCheck = Database::get_main_table($plugin::TABLE_PROIKOS_CHECK_DOCS);

            $sql = "SELECT id FROM $tableCheck WHERE user_id = $userId AND session_id = $sessionId";
            $result = Database::query($sql);

            $params = [
                'user_id' => $userId,
                'session_id' => $sessionId,
                'check_document' => $checkDocument,
                'user_id_check' => $userIDCheck,
            ];

            if (Database::num_rows($result) > 0) {
                Database::update(
                    $tableCheck,
                    $params,
                    [
                        'user_id = ? AND session_id = ?' => [$userId, $sessionId],
                    ]
                );
                echo json_encode(['success' => true, 'message' => 'Actualizado correctamente', 'action' => 'update']);
            } else {
                $id = Database::insert($tableCheck, $params);
                header('Content-Type: application/json');
                if ($id > 0) {
                    echo json_encode(['success' => true, 'message' => 'Creado correctamente', 'action' => 'insert']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al crear el registro']);
                }
            }
            break;
        case 'save_sustenance':
            header('Content-Type: application/json');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type');

            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                http_response_code(200);
                exit;
            }
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                exit;
            }
            $data = $_POST;

            // Obtener y validar parámetros
            $user_id = intval($data['user_id'] ?? 0);
            $course_id = intval($data['course_id'] ?? 0);
            $session_id = intval($data['session_id'] ?? 0);
            $record_id = intval($data['record_id'] ?? 0);

            $sustenance_codes = $data['sustenance_codes'] ?? array();
            if (is_string($sustenance_codes)) {
                $sustenance_codes = explode(',', $sustenance_codes);
            }
            $sustenance_codes = array_filter(array_map('trim', (array)$sustenance_codes));

            $comment = trim($data['comment'] ?? '');

            if ($user_id === 0 || $course_id === 0 || $session_id === 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error: Faltan datos requeridos (user_id, course_id, session_id)',
                    'received' => ['user_id' => $user_id, 'course_id' => $course_id, 'session_id' => $session_id]
                ]);
                exit;
            }

            // Validación: al menos un sustento seleccionado
            if (empty($sustenance_codes)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Debes seleccionar al menos un tipo de incidencia'
                ]);
                exit;
            }

            // Validar que todos los códigos sean números válidos (0-11)
            $sustenance_codes = array_filter(
                array_map('intval', $sustenance_codes),
                function ($code) {
                    return $code >= 0 && $code <= 11;
                }
            );

            if (empty($sustenance_codes)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Códigos de incidencia inválidos. Deben estar entre 0 y 11'
                ]);
                exit;
            }

            // Incluir la clase SustenanceManager
            require_once '../src/SustenanceManager.php';

            $result = false;
            $message = '';

            if ($record_id > 0) {
                // update
                $result = \src\SustenanceManager::updateSustenance(
                    $record_id,
                    $sustenance_codes,
                    $comment
                );

                $message = 'Incidencia actualizada exitosamente';
                $action = 'updated';
            } else {
                // register
                $result = \src\SustenanceManager::saveSustenance(
                    $user_id,
                    $course_id,
                    $session_id,
                    $sustenance_codes,
                    $comment
                );

                $message = 'Incidencia registrada exitosamente';
                $action = 'created';
            }

            if ($result) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'action' => $action,
                    'id' => $result,
                    'data' => [
                        'user_id' => $user_id,
                        'course_id' => $course_id,
                        'session_id' => $session_id,
                        'sustenance_codes' => implode(',', $sustenance_codes),
                        'comment' => $comment
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al guardar los datos en la base de datos'
                ]);
            }

            break;
        case 'get_sustenance':
            header('Content-Type: application/json');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                exit;
            }
            // Obtener datos POST
            $user_id = intval($_POST['user_id'] ?? 0);
            $course_id = intval($_POST['course_id'] ?? 0);
            $session_id = intval($_POST['session_id'] ?? 0);

            if ($user_id === 0 || $course_id === 0 || $session_id === 0) {
                echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                exit;
            }

            // Incluir la clase SustenanceManager
            require_once '../src/SustenanceManager.php';

            try {
                // Obtener datos existentes
                $existingSustenances = \src\SustenanceManager::getSustenance($user_id, $course_id, $session_id);

                if (!empty($existingSustenances)) {
                    echo json_encode([
                        'success' => true,
                        'data' => $existingSustenances
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'data' => null
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }

            break;
        case 'get_sustenance_by_id':
            header('Content-Type: application/json');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                exit;
            }
            $data = $_POST;
            $sustenance_id = intval($data['sustenance_id'] ?? 0);

            if ($sustenance_id === 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de incidencia no válido']);
                exit;
            }

            try {
                $tableSustenance = Database::get_main_table('plugin_proikos_sustenance');
                $tableUsers = Database::get_main_table('user');

                $sql = "SELECT
                            ps.id,
                            ps.user_id,
                            ps.course_id,
                            ps.session_id,
                            ps.sustenance_codes,
                            ps.comment,
                            ps.created_at,
                            ps.updated_at,
                            u.firstname,
                            u.lastname
                        FROM $tableSustenance ps
                        LEFT JOIN $tableUsers u ON ps.user_id = u.id
                        WHERE ps.id = $sustenance_id
                        LIMIT 1; ";

                $result = Database::query($sql);

                if (Database::num_rows($result) === 0) {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Incidencia no encontrada'
                    ]);
                    exit;
                }

                // Obtener datos
                $incidencia = Database::fetch_assoc($result);

                // Preparar respuesta
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'id' => $incidencia['id'],
                        'user_id' => $incidencia['user_id'],
                        'user_name' => $incidencia['firstname'] . ' ' . $incidencia['lastname'],
                        'course_id' => $incidencia['course_id'],
                        'session_id' => $incidencia['session_id'],
                        'sustenance_codes' => $incidencia['sustenance_codes'],
                        'comment' => $incidencia['comment'],
                        'created_at' => $incidencia['created_at'],
                        'updated_at' => $incidencia['updated_at']
                    ]
                ]);

            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }

            break;
        case 'get_course_quizzes':

            header('Content-Type: application/json');

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                exit;
            }

            $user_id = intval($_POST['user_id'] ?? 0);
            $course_id = intval($_POST['course_id'] ?? 0);
            $session_id = intval($_POST['session_id'] ?? 0);

            if ($user_id === 0 || $course_id === 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Datos incompletos (user_id o course_id)']);
                exit;
            }
            require_once api_get_path(SYS_PLUGIN_PATH) . 'proikos/src/QuizBlockManager.php';
            try {
                // Obtener todos los ejercicios del curso
                $quizzes = \src\QuizBlockManager::getCourseQuizzes($course_id, $session_id);

                // Obtener bloqueos existentes del usuario
                $existingBlocks = \src\QuizBlockManager::getQuizBlocks($user_id, $course_id, $session_id);

                $blocked_ids = [];
                $record_id = null;

                if (!empty($existingBlocks)) {
                    $blocked_ids = explode(',', $existingBlocks['exam_ids']);
                    $blocked_ids = array_map('trim', $blocked_ids);
                    $record_id = $existingBlocks['id'];
                }

                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'quizzes' => $quizzes,
                    'blocked_ids' => $blocked_ids,
                    'record_id' => $record_id,
                    'message' => 'Exámenes cargados correctamente'
                ]);

            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }

            break;
    }
}
