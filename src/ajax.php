<?php

require_once __DIR__ . '/../config.php';
$action = $_GET['action'] ?? null;
$idSector = $_GET['id_sector'] ?? null;
$plugin = ProikosPlugin::create();

if ($action) {
    if ($action == 'get_position') {
        $res = $plugin->getPositions($idSector);
        header('Content-Type: application/json');
        echo json_encode($res);
    }
}
