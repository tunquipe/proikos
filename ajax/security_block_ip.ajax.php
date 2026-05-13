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

// Rangos protegidos: red local, loopback y Cloudflare (CIDR)
$protectedCidrs = [
    '127.0.0.0/8', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16',
    '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
    '104.16.0.0/13',   '104.24.0.0/14',
    '108.162.192.0/18','131.0.72.0/22',   '141.101.64.0/18',
    '162.158.0.0/15',  '172.64.0.0/13',   '173.245.48.0/20',
    '188.114.96.0/20', '190.93.240.0/20', '197.234.240.0/22',
    '198.41.128.0/17',
];

$ipLong = ip2long($ip);
foreach ($protectedCidrs as $cidr) {
    [$range, $bits] = explode('/', $cidr);
    $mask = ~((1 << (32 - (int)$bits)) - 1);
    if ((ip2long($range) & $mask) === ($ipLong & $mask)) {
        echo json_encode(['success' => false, 'message' => 'No se puede bloquear esta IP (red protegida o Cloudflare)']);
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
