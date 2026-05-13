<?php

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Solo peticiones AJAX autenticadas
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    echo json_encode(['success' => false, 'message' => 'Petición inválida']);
    exit;
}

api_block_anonymous_users();

if (!api_is_platform_admin()) {
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit;
}

$action = $_POST['action'] ?? '';
$ip     = trim($_POST['ip'] ?? '');

// Validación estricta de IP (evita inyección de comandos)
if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    echo json_encode(['success' => false, 'message' => 'IP inválida: ' . htmlspecialchars($ip)]);
    exit;
}

// Proteger IPs de red local y loopback
$privateRanges = [
    ['10.0.0.0', '10.255.255.255'],
    ['172.16.0.0', '172.31.255.255'],
    ['192.168.0.0', '192.168.255.255'],
    ['127.0.0.0', '127.255.255.255'],
];

$ipLong = ip2long($ip);
foreach ($privateRanges as [$start, $end]) {
    if ($ipLong >= ip2long($start) && $ipLong <= ip2long($end)) {
        echo json_encode(['success' => false, 'message' => 'No se puede bloquear una IP de red local o loopback']);
        exit;
    }
}

switch ($action) {
    case 'block':
        $cmd    = 'sudo /usr/sbin/ufw deny from ' . escapeshellarg($ip) . ' 2>&1';
        $output = shell_exec($cmd);
        if ($output === null) {
            echo json_encode(['success' => false, 'message' => 'Error ejecutando UFW. Verifique los permisos sudo.']);
            exit;
        }
        $success = (strpos($output, 'Rule added') !== false || strpos($output, 'Skipping') !== false || strpos($output, 'added') !== false);
        echo json_encode(['success' => $success, 'output' => trim($output), 'message' => trim($output)]);
        break;

    case 'unblock':
        // UFW delete requiere confirmar con "yes"; usamos --force equivalente pasando "yes" al pipe
        $cmd    = 'echo "y" | sudo /usr/sbin/ufw delete deny from ' . escapeshellarg($ip) . ' 2>&1';
        $output = shell_exec($cmd);
        if ($output === null) {
            echo json_encode(['success' => false, 'message' => 'Error ejecutando UFW.']);
            exit;
        }
        $success = strpos($output, 'deleted') !== false || strpos($output, 'Rule deleted') !== false;
        echo json_encode(['success' => $success, 'output' => trim($output), 'message' => trim($output)]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción desconocida']);
}
