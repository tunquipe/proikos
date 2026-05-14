<?php

$cidReset = true;

require_once __DIR__ . '/../config.php';
api_block_anonymous_users();

if (!api_is_platform_admin()) {
    api_not_allowed(true);
}

$htmlHeadXtra[] = api_get_css(api_get_path(WEB_PLUGIN_PATH) . 'proikos/css/style.css');
$plugin = ProikosPlugin::create();
$tool_name = 'Monitor de Seguridad';

$ajaxUrl = api_get_path(WEB_PLUGIN_PATH) . 'proikos/ajax/security_block_ip.ajax.php';
$backUrl = api_get_path(WEB_PLUGIN_PATH) . 'proikos/start.php';

// -----------------------------------------------------------------------
// Fuentes de logs Apache
// -----------------------------------------------------------------------
$logFiles = array_filter([
    '/var/log/apache2/hseq-proikos-access.log',
], fn($f) => is_readable($f) && filesize($f) > 0);

// Patrones de URLs que delatan escaneo/ataque
$suspiciousPatterns = [
    '/\.env\b/', '/xmlrpc\.php/', '/wp-admin/', '/wp-login\.php/',
    '/\.git\//', '/phpmyadmin/', '/pma\//', '/adminer/',
    '/\.sql\b/', '/\.bak\b/', '/shell\.php/', '/cmd\.php/',
    '/eval\(/', '/base64_decode/', '/eval-stdin\.php/',
    '/etc\/passwd/', '/proc\/self/', '/\.\.\//',
    '/setup\.php/', '/phpinfo/', '/\.(asp|aspx|jsp|cfm)\b/i',
    '/vendor\/phpunit/', '/vendor\/laravel/', '/vendor\/guzzle/',
    '/alfacgiapi/', '/cgi-bin\//', '/\.DS_Store/',
];

// IPs/rangos en whitelist — nunca se marcan como sospechosas
// Formato: CIDR o IP exacta
$whitelist = [
    '127.0.0.1',
    // Cloudflare
    '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
    '104.16.0.0/13',   '104.24.0.0/14',
    '108.162.192.0/18','131.0.72.0/22',   '141.101.64.0/18',
    '162.158.0.0/15',  '172.64.0.0/13',   '173.245.48.0/20',
    '188.114.96.0/20', '190.93.240.0/20', '197.234.240.0/22',
    '198.41.128.0/17',
];

function ipInWhitelist(string $ip, array $whitelist): bool {
    $ipLong = ip2long($ip);
    if ($ipLong === false) return false;
    foreach ($whitelist as $entry) {
        if (strpos($entry, '/') === false) {
            if ($ip === $entry) return true;
        } else {
            [$range, $bits] = explode('/', $entry);
            $mask = ~((1 << (32 - (int)$bits)) - 1);
            if ((ip2long($range) & $mask) === ($ipLong & $mask)) return true;
        }
    }
    return false;
}

// Umbrales para detección automática
$threshold404     = 10;   // IPs con 10+ errores 404
$thresholdRequests = 50;  // IPs con 50+ requests en el período

// -----------------------------------------------------------------------
// Parsear logs de Apache — un solo paso completo
// -----------------------------------------------------------------------
// Estructura: [ ip => [
//   'total'   => N,      total de requests
//   'errors'  => N,      errores 4xx/5xx
//   'e404'    => N,      errores 404 específicos
//   'paths'   => [],     rutas visitadas (sospechosas o frecuentes)
//   'reasons' => [],     por qué es sospechosa
//   'last'    => ts,
//   'sources' => [],
// ]]
$rawData = [];

// Leer solo las últimas 50.000 líneas de cada log
foreach ($logFiles as $logFile) {
    $source  = basename($logFile);
    $handle  = popen("tail -n 50000 " . escapeshellarg($logFile) . " 2>/dev/null", 'r');
    if (!$handle) {
        continue;
    }
    while (($line = fgets($handle)) !== false) {
        // Combined log: IP - - [date] "METHOD URI proto" STATUS bytes ...
        if (!preg_match(
            '/^(\d{1,3}(?:\.\d{1,3}){3})\s+-\s+-\s+\[([^\]]+)\]\s+"(?:[A-Z]+\s+)?([^\s"]+)[^"]*"\s+(\d{3})/',
            $line, $m
        )) {
            continue;
        }
        [, $ip, $dateStr, $uri, $status] = $m;
        $status = (int) $status;

        if (ipInWhitelist($ip, $whitelist)) {
            continue;
        }

        // "14/May/2026:16:10:04 -0500" → "14 May 2026 16:10:04 -0500"
        $ts   = strtotime(str_replace('/', ' ', substr($dateStr, 0, 11)) . ' ' . substr($dateStr, 12, 8) . ' ' . substr($dateStr, 21));
        $path = strtok($uri, '?');

        if (!isset($rawData[$ip])) {
            $rawData[$ip] = ['total' => 0, 'errors' => 0, 'e404' => 0, 'paths' => [], 'reasons' => [], 'last' => 0, 'sources' => []];
        }

        $rawData[$ip]['total']++;
        if ($status >= 400) {
            $rawData[$ip]['errors']++;
        }
        if ($status === 404) {
            $rawData[$ip]['e404']++;
        }
        if ($ts > $rawData[$ip]['last']) {
            $rawData[$ip]['last'] = $ts;
        }
        if (!in_array($source, $rawData[$ip]['sources'])) {
            $rawData[$ip]['sources'][] = $source;
        }

        // Registrar si la URL es sospechosa
        $isSuspiciousUrl = false;
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $uri)) {
                $isSuspiciousUrl = true;
                break;
            }
        }
        if ($isSuspiciousUrl && !in_array($path, $rawData[$ip]['paths'])) {
            $rawData[$ip]['paths'][] = $path;
        }
    }
    pclose($handle);
}

// -----------------------------------------------------------------------
// Parsear proikos_ip_history.log  — formato: "IP - YYYY-MM-DD HH:MM:SS"
// -----------------------------------------------------------------------
$ipHistoryLog = '/var/log/proikos_ip_history.log';
if (is_readable($ipHistoryLog) && filesize($ipHistoryLog) > 0) {
    $handle = fopen($ipHistoryLog, 'r');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            if (!preg_match('/^(\d{1,3}(?:\.\d{1,3}){3})\s+-\s+(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})/', $line, $m)) {
                continue;
            }
            [, $ip, $dateStr] = $m;

            if (ipInWhitelist($ip, $whitelist)) {
                continue;
            }

            $ts = strtotime($dateStr);

            if (!isset($rawData[$ip])) {
                $rawData[$ip] = ['total' => 0, 'errors' => 0, 'e404' => 0, 'paths' => [], 'reasons' => [], 'last' => 0, 'sources' => []];
            }
            $rawData[$ip]['total']++;
            if ($ts > $rawData[$ip]['last']) {
                $rawData[$ip]['last'] = $ts;
            }
            if (!in_array('ip_history', $rawData[$ip]['sources'])) {
                $rawData[$ip]['sources'][] = 'ip_history';
            }
        }
        fclose($handle);
    }
}

// Incluir todas las IPs — marcar las sospechosas
$ipData = [];
foreach ($rawData as $ip => $data) {
    $reasons = [];

    if (!empty($data['paths'])) {
        $reasons[] = 'URL sospechosa';
    }
    if ($data['e404'] >= $threshold404) {
        $reasons[] = $data['e404'] . ' errores 404';
    }
    if ($data['total'] >= $thresholdRequests) {
        $reasons[] = $data['total'] . ' requests';
    }

    $ipData[$ip] = [
        'count'      => $data['total'],
        'errors'     => $data['errors'],
        'e404'       => $data['e404'],
        'paths'      => $data['paths'],
        'reasons'    => $reasons,
        'suspicious' => !empty($reasons),
        'last'       => $data['last'],
        'sources'    => $data['sources'],
    ];
}

// -----------------------------------------------------------------------
// Parsear auth.log para intentos SSH fallidos
// -----------------------------------------------------------------------
$authLog = '/var/log/auth.log';
if (is_readable($authLog)) {
    $handle = fopen($authLog, 'r');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            if (!preg_match('/Failed password.*from (\d{1,3}(?:\.\d{1,3}){3})/', $line, $m) &&
                !preg_match('/Invalid user .* from (\d{1,3}(?:\.\d{1,3}){3})/', $line, $m) &&
                !preg_match('/Connection closed by invalid user .* (\d{1,3}(?:\.\d{1,3}){3})/', $line, $m)) {
                continue;
            }
            $ip = $m[1];
            if (!isset($ipData[$ip])) {
                $ipData[$ip] = ['count' => 0, 'paths' => [], 'last' => 0, 'sources' => []];
            }
            $ipData[$ip]['count']++;
            if (!in_array('[SSH]', $ipData[$ip]['paths'])) {
                $ipData[$ip]['paths'][] = '[SSH brute-force]';
            }
            if (!in_array('auth.log', $ipData[$ip]['sources'])) {
                $ipData[$ip]['sources'][] = 'auth.log';
            }
        }
        fclose($handle);
    }
}

// -----------------------------------------------------------------------
// Métricas del servidor para el panel de salud
// -----------------------------------------------------------------------
function serverMetric(string $cmd): string {
    return trim((string) @shell_exec($cmd . ' 2>/dev/null'));
}

function semaforo(float $val, float $warn, float $crit, bool $invertir = false): string {
    if (!$invertir) {
        if ($val >= $crit) return 'danger';
        if ($val >= $warn) return 'warning';
        return 'success';
    } else {
        if ($val <= $crit) return 'danger';
        if ($val <= $warn) return 'warning';
        return 'success';
    }
}

function dot(string $color): string {
    $colors = ['success' => '#27ae60', 'warning' => '#f39c12', 'danger' => '#e74c3c'];
    $c = $colors[$color] ?? '#999';
    return "<span style=\"display:inline-block;width:14px;height:14px;border-radius:50%;background:{$c};vertical-align:middle;margin-right:6px;box-shadow:0 0 6px {$c};\"></span>";
}

// CPU
$cpuRaw   = serverMetric("top -bn1 | grep 'Cpu(s)' | awk '{print $2}'");
$cpuPct   = (float) str_replace(',', '.', $cpuRaw);
$cpuCores = (int)(serverMetric("nproc") ?: 1);
$cpuModel = serverMetric("grep -m1 'model name' /proc/cpuinfo | cut -d: -f2 | xargs");
$cpuColor = semaforo($cpuPct, 60, 85);

// RAM
$memInfo  = serverMetric("free | grep Mem");
$memParts = preg_split('/\s+/', $memInfo);
$ramTotal = (int)($memParts[1] ?? 1);
$ramUsed  = (int)($memParts[2] ?? 0);
$ramPct   = $ramTotal > 0 ? round($ramUsed / $ramTotal * 100, 1) : 0;
$ramTotalGb = round($ramTotal / 1024 / 1024, 1);
$ramUsedGb  = round($ramUsed  / 1024 / 1024, 1);
$ramColor = semaforo($ramPct, 70, 90);

// Disco raíz
$diskRaw   = serverMetric("df -BG / | tail -1 | awk '{print $2, $3, $5}'");
$diskParts = preg_split('/\s+/', $diskRaw);
$diskTotal = (int) str_replace('G', '', $diskParts[0] ?? '1');
$diskUsed  = (int) str_replace('G', '', $diskParts[1] ?? '0');
$diskPct   = (float) str_replace('%', '', $diskParts[2] ?? '0');
$diskColor = semaforo($diskPct, 75, 90);

// Load average
$loadRaw   = serverMetric("cat /proc/loadavg");
$loadParts = explode(' ', $loadRaw);
$load1     = (float)($loadParts[0] ?? 0);
$load5     = (float)($loadParts[1] ?? 0);
$load15    = (float)($loadParts[2] ?? 0);
$loadPct   = $cpuCores > 0 ? round($load1 / $cpuCores * 100, 1) : 0;
$loadColor = semaforo($loadPct, 70, 100);

// Apache
$apacheStatus  = serverMetric("systemctl is-active apache2");
$apacheVersion = serverMetric("apache2 -v 2>/dev/null | grep 'Server version' | awk '{print $3}'");
$apacheColor   = ($apacheStatus === 'active') ? 'success' : 'danger';

// MySQL / MariaDB
$mysqlStatus  = serverMetric("systemctl is-active mysql") ?: serverMetric("systemctl is-active mariadb");
$mysqlVersion = serverMetric("mysql --version 2>/dev/null | awk '{print $5}' | tr -d ','");
$mysqlColor   = ($mysqlStatus === 'active') ? 'success' : 'danger';

// Conexiones activas
$activeConns = (int) serverMetric("ss -tn state established | grep -c ':80\|:443'");
$connsColor  = semaforo($activeConns, 200, 500);

// Uptime del servidor
$uptimeRaw = serverMetric("uptime -p");
$uptimeRaw = str_replace(['up ', 'weeks', 'week', 'days', 'day', 'hours', 'hour', 'minutes', 'minute'],
                         ['', 'sem', 'sem', 'd', 'd', 'h', 'h', 'min', 'min'], $uptimeRaw);
$uptimeRaw = trim(preg_replace('/\s+/', ' ', $uptimeRaw));

// -----------------------------------------------------------------------
// Estado UFW actual — reglas DENY existentes
// -----------------------------------------------------------------------
$ufwOutput   = @shell_exec('sudo ufw status numbered 2>/dev/null');
$ufwInactive = $ufwOutput && strpos($ufwOutput, 'inactive') !== false;
// $blockedRules: [ ip => ruleNumber ]
$blockedRules = [];
$blockedIps   = [];
if ($ufwOutput && !$ufwInactive) {
    // Línea ejemplo: [ 1] Anywhere   DENY IN   74.249.238.26
    preg_match_all('/\[\s*(\d+)\].*?DENY\s+(?:IN\s+)?(\d{1,3}(?:\.\d{1,3}){3})/i', $ufwOutput, $ufwMatches, PREG_SET_ORDER);
    foreach ($ufwMatches as $m) {
        $blockedRules[$m[2]] = (int) $m[1];
        $blockedIps[] = $m[2];
    }
    $blockedIps = array_unique($blockedIps);
}

// Ordenar por cantidad de intentos desc
uasort($ipData, fn($a, $b) => $b['last'] <=> $a['last']);

// -----------------------------------------------------------------------
// Render
// -----------------------------------------------------------------------
$tpl = new Template($tool_name);

$actionLinks = Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    $backUrl
);

// -----------------------------------------------------------------------
// Panel salud del servidor
// -----------------------------------------------------------------------
$ufwLabel = $ufwInactive ? 'Inactivo' : ($ufwOutput ? 'Activo' : 'Sin permisos');
$ufwColor = $ufwInactive ? 'danger' : ($ufwOutput ? 'success' : 'warning');

$metrics = [
    ['icon' => 'fa-microchip',  'label' => 'CPU',        'value' => "{$cpuPct}%",            'sub' => "{$cpuCores} núcleos · {$cpuModel}",                    'color' => $cpuColor],
    ['icon' => 'fa-server',     'label' => 'RAM',        'value' => "{$ramPct}%",             'sub' => "{$ramUsedGb} GB usados de {$ramTotalGb} GB",          'color' => $ramColor],
    ['icon' => 'fa-hdd-o',      'label' => 'Disco',      'value' => "{$diskPct}%",            'sub' => "{$diskUsed} GB usados de {$diskTotal} GB",            'color' => $diskColor],
    ['icon' => 'fa-tachometer', 'label' => 'Load avg',   'value' => "{$load1} ({$loadPct}%)", 'sub' => "5min: {$load5} · 15min: {$load15} · {$cpuCores} CPU", 'color' => $loadColor],
    ['icon' => 'fa-globe',      'label' => 'Apache',     'value' => ucfirst($apacheStatus),   'sub' => $apacheVersion ?: 'Apache2',                          'color' => $apacheColor],
    ['icon' => 'fa-database',   'label' => 'MySQL',      'value' => ucfirst($mysqlStatus),    'sub' => $mysqlVersion ?: 'MySQL/MariaDB',                      'color' => $mysqlColor],
    ['icon' => 'fa-exchange',   'label' => 'Conexiones', 'value' => (string)$activeConns,     'sub' => 'Activas en :80 y :443',                               'color' => $connsColor],
    ['icon' => 'fa-shield',     'label' => 'Firewall',   'value' => $ufwLabel,                'sub' => count($blockedIps) . ' IPs bloqueadas',                'color' => $ufwColor],
];

$bgColors = ['success' => '#eafaf1', 'warning' => '#fef9e7', 'danger' => '#fdedec'];
$bdColors = ['success' => '#27ae60', 'warning' => '#f39c12', 'danger' => '#e74c3c'];

// -----------------------------------------------------------------------
// Queries Chamilo — deben ejecutarse antes del render del nav
// -----------------------------------------------------------------------
$isSuperAdmin = (api_get_user_id() === 1);

$dbOnline  = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
$dbUser    = Database::get_main_table(TABLE_MAIN_USER);
$dbDefault = Database::get_main_table(TABLE_STATISTIC_TRACK_E_DEFAULT);
$dbAdmin   = Database::get_main_table(TABLE_MAIN_ADMIN);

$sqlOnline = "SELECT teo.login_user_id, teo.user_ip, teo.login_date,
                     u.firstname, u.lastname, u.username, u.status,
                     IF(a.user_id IS NOT NULL, 1, 0) AS is_admin
              FROM {$dbOnline} teo
              INNER JOIN {$dbUser} u ON u.id = teo.login_user_id
              LEFT JOIN {$dbAdmin} a ON a.user_id = u.id
              WHERE teo.login_date >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
              ORDER BY teo.login_date DESC";
$resOnline   = Database::query($sqlOnline);
$onlineUsers = Database::store_result($resOnline, 'ASSOC');
$onlineCount = count($onlineUsers);

$auditRows   = [];
$processList = [];
if ($isSuperAdmin) {
    $sqlAudit = "SELECT ted.default_user_id, ted.default_date, ted.default_event_type,
                        ted.default_value_type, ted.default_value, ted.c_id,
                        u.firstname, u.lastname, u.username
                 FROM {$dbDefault} ted
                 LEFT JOIN {$dbUser} u ON u.id = ted.default_user_id
                 WHERE ted.default_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 ORDER BY ted.default_date DESC
                 LIMIT 200";
    $resAudit  = Database::query($sqlAudit);
    $auditRows = Database::store_result($resAudit, 'ASSOC');

    $resProc = Database::query("SHOW FULL PROCESSLIST");
    while ($row = Database::fetch_assoc($resProc)) {
        if ($row['Time'] >= 2 && $row['Command'] !== 'Sleep') {
            $processList[] = $row;
        }
    }
    usort($processList, fn($a, $b) => $b['Time'] <=> $a['Time']);
}

// -----------------------------------------------------------------------
// Render con pestañas
// -----------------------------------------------------------------------
$content = '<div class="panel-proikos"><h3>Monitor de Seguridad</h3></div>';

// Nav tabs
$content .= '
<ul class="nav nav-tabs" style="margin-bottom:0;">
    <li class="active">
        <a href="#tab-seguridad" data-toggle="tab">
            <i class="fa fa-shield"></i> Seguridad / IPs
            <span class="badge" style="background:#e74c3c;margin-left:4px;">' . count(array_filter($ipData, fn($d) => $d['suspicious'])) . '</span>
        </a>
    </li>
    <li>
        <a href="#tab-usuarios" data-toggle="tab">
            <i class="fa fa-users"></i> Usuarios Conectados
            <span class="badge" style="background:#2980b9;margin-left:4px;">' . $onlineCount . '</span>
        </a>
    </li>
    ' . ($isSuperAdmin ? '
    <li>
        <a href="#tab-auditoria" data-toggle="tab">
            <i class="fa fa-history"></i> Auditoría Chamilo
        </a>
    </li>' : '') . '
    <li>
        <a href="#tab-servidor" data-toggle="tab">
            <i class="fa fa-heartbeat"></i> Servidor
        </a>
    </li>
</ul>
<div class="tab-content" style="border:1px solid #ddd;border-top:none;padding:20px;background:#fff;margin-bottom:20px;">';

// -----------------------------------------------------------------------
// TAB 1 — Monitor del servidor
// -----------------------------------------------------------------------
$content .= '<div class="tab-pane" id="tab-servidor">';

// Uptime banner
$content .= '<div style="background:#f4f6f9;border:1px solid #dce1ea;border-radius:8px;padding:10px 16px;margin-bottom:16px;display:flex;align-items:center;gap:10px;">
    <i class="fa fa-clock-o" style="color:#2980b9;font-size:18px;"></i>
    <span style="font-size:13px;color:#555;"><strong>Uptime del servidor:</strong> ' . htmlspecialchars($uptimeRaw) . '</span>
</div>';

$content .= '<div style="display:flex;flex-wrap:wrap;gap:14px;margin-bottom:8px;">';
foreach ($metrics as $m) {
    $bg  = $bgColors[$m['color']];
    $bd  = $bdColors[$m['color']];
    $sub = htmlspecialchars($m['sub'] ?? '');

    $barHtml = '';
    if (preg_match('/^([\d.]+)%$/', $m['value'], $bm)) {
        $pct    = min(100, (float)$bm[1]);
        $barClr = $bdColors[$m['color']];
        $barHtml = '<div style="margin-top:8px;background:#ddd;border-radius:4px;height:6px;overflow:hidden;">
            <div style="width:' . $pct . '%;background:' . $barClr . ';height:6px;border-radius:4px;"></div>
        </div>';
    }

    $content .= '<div style="flex:1 1 calc(25% - 14px);min-width:200px;max-width:calc(25% - 14px);box-sizing:border-box;">
        <div style="background:' . $bg . ';border:2px solid ' . $bd . ';border-radius:8px;padding:16px;height:100%;box-sizing:border-box;">
            <div style="display:flex;align-items:center;gap:10px;">
                ' . dot($m['color']) . '
                <div style="flex:1;min-width:0;">
                    <div style="font-size:11px;color:#666;text-transform:uppercase;letter-spacing:.5px;">' . $m['label'] . '</div>
                    <div style="font-size:22px;font-weight:700;color:#222;line-height:1.2;">' . $m['value'] . '</div>
                </div>
                <i class="fa ' . $m['icon'] . '" style="font-size:26px;color:' . $bd . ';opacity:.3;flex-shrink:0;"></i>
            </div>
            ' . $barHtml . '
            <div style="margin-top:6px;font-size:11px;color:#777;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' . $sub . '">' . $sub . '</div>
        </div>
    </div>';
}
$content .= '</div>';
$content .= '<p class="text-muted" style="margin-top:4px;font-size:12px;"><i class="fa fa-refresh"></i> Datos al momento de cargar la página. Recarga para actualizar.</p>';
$content .= '</div>'; // end tab-servidor

// -----------------------------------------------------------------------
// TAB 2 — Seguridad / IPs
// -----------------------------------------------------------------------
$content .= '<div class="tab-pane active" id="tab-seguridad">';

// UFW status
$content .= '<div class="panel panel-default" style="margin-bottom:20px;">
    <div class="panel-heading"><strong><i class="fa fa-fire"></i> Estado del Firewall (UFW)</strong></div>
    <div class="panel-body">';
if ($ufwInactive) {
    $content .= '<div class="alert alert-danger" style="margin-bottom:10px;">
        <strong><i class="fa fa-exclamation-triangle"></i> UFW está INACTIVO.</strong>
        Las reglas no tendrán efecto hasta activarlo.<br>
        <pre style="background:#222;color:#f90;padding:8px;margin-top:8px;border-radius:4px;">sudo ufw enable</pre>
    </div>';
} elseif ($ufwOutput) {
    $content .= '<div class="alert alert-success" style="padding:6px 12px;margin-bottom:8px;">
        <i class="fa fa-shield"></i> <strong>UFW activo</strong>
    </div>';
    $content .= '<pre style="max-height:130px;overflow:auto;font-size:12px;">' . htmlspecialchars($ufwOutput) . '</pre>';
} else {
    $content .= '<div class="alert alert-warning">No se pudo leer UFW. Verifique permisos sudo.</div>';
}
$content .= '</div></div>';

// IPs bloqueadas
$content .= '<div class="panel panel-danger" style="margin-bottom:20px;">
    <div class="panel-heading" style="background:#c0392b;color:#fff;border-color:#c0392b;">
        <strong><i class="fa fa-ban"></i> IPs Bloqueadas en UFW</strong>
        <span class="badge" style="margin-left:8px;background:#fff;color:#c0392b;">' . count($blockedIps) . '</span>
    </div>
    <div class="panel-body" style="padding:0;">';
if (empty($blockedIps)) {
    $content .= '<p class="text-muted" style="padding:15px;margin:0;">No hay IPs bloqueadas actualmente.</p>';
} else {
    $content .= '<table class="table table-striped table-hover table-bordered" style="margin:0;">
        <thead style="background:#f2f2f2;">
            <tr>
                <th style="width:50px;text-align:center;"># Regla</th>
                <th>IP Bloqueada</th>
                <th style="width:160px;text-align:center;">Acción</th>
            </tr>
        </thead><tbody>';
    foreach ($blockedIps as $bip) {
        $ruleNum = $blockedRules[$bip] ?? '?';
        $content .= '<tr>
            <td style="text-align:center;"><span class="label label-default">' . $ruleNum . '</span></td>
            <td><i class="fa fa-ban" style="color:#c0392b;margin-right:6px;"></i><strong>' . htmlspecialchars($bip) . '</strong></td>
            <td style="text-align:center;">
                <button class="btn btn-xs btn-success" onclick="unblockIp(\'' . htmlspecialchars($bip, ENT_QUOTES) . '\')">
                    <i class="fa fa-unlock"></i> Desbloquear
                </button>
            </td>
        </tr>';
    }
    $content .= '</tbody></table>';
}
$content .= '</div></div>';

// Bloquear IP manual
$content .= '<div class="panel panel-default" style="margin-bottom:20px;">
    <div class="panel-heading"><strong><i class="fa fa-lock"></i> Bloquear IP manualmente</strong></div>
    <div class="panel-body">
        <div class="input-group" style="max-width:400px;">
            <input type="text" id="manual-ip" class="form-control" placeholder="Ej: 45.33.32.156">
            <span class="input-group-btn">
                <button class="btn btn-danger" onclick="blockIp(document.getElementById(\'manual-ip\').value)">
                    <i class="fa fa-ban"></i> Bloquear con UFW
                </button>
            </span>
        </div>
    </div>
</div>';

// Mapa IP -> usuario Chamilo conectado
$ipToUser = [];
foreach ($onlineUsers as $u) {
    $uip = $u['user_ip'] ?? '';
    if ($uip && !isset($ipToUser[$uip])) {
        $ipToUser[$uip] = htmlspecialchars($u['username']) . '<br><small class="text-muted">' . htmlspecialchars(trim($u['firstname'] . ' ' . $u['lastname'])) . '</small>';
    }
}

// Todas las IPs del log
$suspiciousCount = count(array_filter($ipData, fn($d) => $d['suspicious']));
$totalIps        = count($ipData);
$content .= '<div class="panel panel-default">
    <div class="panel-heading" style="background:#333;color:#fff;display:flex;align-items:center;flex-wrap:wrap;gap:6px;">
        <strong><i class="fa fa-list"></i> IPs detectadas en el log</strong>
        <span class="badge" style="background:#aaa;">' . $totalIps . ' total</span>
        <span class="badge" style="background:#e74c3c;">' . $suspiciousCount . ' sospechosas</span>
        <span style="margin-left:auto;font-size:11px;opacity:.8;">
            <span style="display:inline-block;width:10px;height:10px;background:#f2dede;border:1px solid #e74c3c;border-radius:2px;"></span> Sospechosa &nbsp;
            <span style="display:inline-block;width:10px;height:10px;background:#fcf8e3;border:1px solid #f0ad4e;border-radius:2px;"></span> Vigilar &nbsp;
            <span style="display:inline-block;width:10px;height:10px;background:#fff;border:1px solid #ddd;border-radius:2px;"></span> Normal
        </span>
    </div>
    <div class="panel-body" style="padding:0;">';

if (empty($ipData)) {
    $content .= '<div class="alert alert-info" style="margin:15px;">No se encontraron IPs en el log. Verifique que el archivo sea legible.</div>';
} else {
    // Construir filas HTML como array para paginación JS
    $rows = [];
    foreach ($ipData as $ip => $data) {
        $isBlocked = in_array($ip, $blockedIps);

        if ($isBlocked) {
            $rowStyle    = 'background:#dff0d8;';
            $statusBadge = '<span class="label label-success"><i class="fa fa-lock"></i> Bloqueado</span>';
        } elseif ($data['suspicious']) {
            $rowStyle    = 'background:#f2dede;';
            $statusBadge = '<span class="label label-danger"><i class="fa fa-exclamation-triangle"></i> Sospechosa</span>';
        } elseif ($data['e404'] > 0 || $data['errors'] > 0) {
            $rowStyle    = 'background:#fcf8e3;';
            $statusBadge = '<span class="label label-warning"><i class="fa fa-eye"></i> Vigilar</span>';
        } else {
            $rowStyle    = '';
            $statusBadge = '<span class="label label-default"><i class="fa fa-circle-o"></i> Normal</span>';
        }

        $paths     = array_slice($data['paths'], 0, 4);
        $pathsHtml = empty($paths)
            ? '<span class="text-muted">—</span>'
            : implode('<br>', array_map(fn($p) => '<code style="font-size:10px;word-break:break-all;">' . htmlspecialchars($p) . '</code>', $paths));
        if (count($data['paths']) > 4) {
            $pathsHtml .= '<br><small class="text-muted">+' . (count($data['paths']) - 4) . ' más</small>';
        }

        $reasonsHtml = !empty($data['reasons'])
            ? implode(' ', array_map(fn($r) => '<span class="label label-warning" style="font-size:10px;">' . htmlspecialchars($r) . '</span>', $data['reasons']))
            : '<span class="text-muted">—</span>';

        $lastSeen = $data['last'] ? date('d/m/Y H:i', $data['last']) : '—';

        $e404Display   = $data['e404'] > 0
            ? '<span class="badge" style="background:#e74c3c;">' . $data['e404'] . '</span>'
            : '<span class="text-muted">0</span>';
        $errorsDisplay = ($data['errors'] - $data['e404']) > 0
            ? '<span class="badge" style="background:#e67e22;">' . ($data['errors'] - $data['e404']) . '</span>'
            : '<span class="text-muted">0</span>';

        $userCell = isset($ipToUser[$ip])
            ? '<span class="label" style="background:#2980b9;font-size:10px;"><i class="fa fa-user"></i></span> ' . $ipToUser[$ip]
            : '<span class="text-muted" style="font-size:11px;">—</span>';

        $blockBtn = $isBlocked
            ? '<button class="btn btn-xs btn-success" onclick="unblockIp(\'' . htmlspecialchars($ip, ENT_QUOTES) . '\')"><i class="fa fa-unlock"></i></button>'
            : '<button class="btn btn-xs btn-danger" onclick="blockIp(\'' . htmlspecialchars($ip, ENT_QUOTES) . '\')"><i class="fa fa-ban"></i> Bloquear</button>';

        $rows[] = "<tr style=\"{$rowStyle}\">
            <td><strong>{$ip}</strong></td>
            <td style=\"text-align:center;\"><span class=\"badge\" style=\"background:#555;\">{$data['count']}</span></td>
            <td style=\"text-align:center;\">{$e404Display}</td>
            <td style=\"text-align:center;\">{$errorsDisplay}</td>
            <td>{$reasonsHtml}</td>
            <td>{$pathsHtml}</td>
            <td style=\"white-space:nowrap;font-size:11px;\">{$lastSeen}</td>
            <td style=\"font-size:11px;\">{$userCell}</td>
            <td style=\"text-align:center;\">{$statusBadge}</td>
            <td style=\"text-align:center;\">{$blockBtn}</td>
        </tr>";
    }

    $rowsJson   = json_encode($rows);
    $content .= <<<HTML
    <div style="padding:8px 12px;background:#f9f9f9;border-bottom:1px solid #ddd;display:flex;align-items:center;gap:10px;">
        <span style="font-size:12px;color:#555;">Página <strong id="ip-page-cur">1</strong> de <strong id="ip-page-total">1</strong></span>
        <button class="btn btn-xs btn-default" onclick="ipPageNav(-1)"><i class="fa fa-chevron-left"></i> Anterior</button>
        <button class="btn btn-xs btn-default" onclick="ipPageNav(1)">Siguiente <i class="fa fa-chevron-right"></i></button>
        <span style="margin-left:auto;font-size:12px;color:#888;">{$totalIps} IPs en total</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-bordered" id="ip-log-table" style="margin:0;font-size:13px;">
            <thead style="background:#444;color:#fff;">
                <tr>
                    <th>IP</th>
                    <th style="text-align:center;">Requests</th>
                    <th style="text-align:center;">Errores<br>404</th>
                    <th style="text-align:center;">Otros<br>errores</th>
                    <th>Alerta</th>
                    <th>Rutas sospechosas</th>
                    <th>Última vez</th>
                    <th>Usuario Chamilo</th>
                    <th style="text-align:center;">Estado</th>
                    <th style="text-align:center;">Acción</th>
                </tr>
            </thead>
            <tbody id="ip-log-tbody"></tbody>
        </table>
    </div>
    <script>
    (function(){
        var rows     = {$rowsJson};
        var pageSize = 100;
        var curPage  = 1;
        var total    = Math.ceil(rows.length / pageSize);
        document.getElementById('ip-page-total').textContent = total;
        function render(page) {
            curPage = Math.max(1, Math.min(page, total));
            document.getElementById('ip-page-cur').textContent = curPage;
            var start = (curPage - 1) * pageSize;
            var html  = rows.slice(start, start + pageSize).join('');
            document.getElementById('ip-log-tbody').innerHTML = html;
        }
        window.ipPageNav = function(dir) { render(curPage + dir); };
        render(1);
    })();
    </script>
HTML;
}
$content .= '</div></div>';
$content .= '</div>'; // end tab-seguridad

// -----------------------------------------------------------------------
// TAB 3 — Usuarios conectados
// -----------------------------------------------------------------------

$content .= '<div class="tab-pane" id="tab-usuarios">';
$content .= '<div class="panel panel-default" style="margin-bottom:0;">
    <div class="panel-heading" style="background:#2980b9;color:#fff;">
        <strong><i class="fa fa-users"></i> Usuarios activos en Chamilo</strong>
        <span class="badge" style="margin-left:8px;background:#fff;color:#2980b9;">' . $onlineCount . '</span>
        <small style="margin-left:12px;opacity:.85;">Actividad en los últimos 15 minutos</small>
    </div>
    <div class="panel-body" style="padding:0;">';

if (empty($onlineUsers)) {
    $content .= '<div class="alert alert-info" style="margin:15px;">No hay usuarios activos en este momento.</div>';
} else {
    $roleLabels = [1 => 'Docente', 4 => 'Administrador Petro Peru', 5 => 'Estudiante', 6 => 'Anónimo', 30 => 'Gestor de cupos'];
    $roleColors = [1 => '#8e44ad', 4 => '#e67e22', 5 => '#27ae60', 6 => '#95a5a6', 30 => '#16a085'];
    $content .= '<div class="table-responsive">
        <table class="table table-striped table-hover table-bordered" style="margin:0;">
            <thead style="background:#2980b9;color:#fff;">
                <tr>
                    <th>#</th>
                    <th>Usuario</th>
                    <th>Nombre</th>
                    <th>Rol</th>
                    <th>IP</th>
                    <th>Última actividad</th>
                    <th>Hace</th>
                </tr>
            </thead><tbody>';
    $i = 1;
    foreach ($onlineUsers as $u) {
        $lastDate = $u['login_date'];
        $loginTs  = (new DateTime($lastDate, new DateTimeZone('UTC')))->getTimestamp();
        $diffSecs = max(0, time() - $loginTs);
        $mins = floor($diffSecs / 60);
        $secs = $diffSecs % 60;
        if ($diffSecs < 60) {
            $hace = $diffSecs . 's';
            $rowCls = 'success';
        } elseif ($diffSecs < 300) {
            $hace = $mins . 'm ' . $secs . 's';
            $rowCls = '';
        } else {
            $hace = $mins . 'm ' . $secs . 's';
            $rowCls = 'warning';
        }
        $status = (int)$u['status'];
        if (!empty($u['is_admin'])) {
            $role      = 'Admin';
            $roleColor = '#c0392b';
        } else {
            $role      = $roleLabels[$status] ?? 'Usuario';
            $roleColor = $roleColors[$status] ?? '#2980b9';
        }
        $ip    = htmlspecialchars($u['user_ip'] ?? '—');
        $name  = htmlspecialchars(trim($u['firstname'] . ' ' . $u['lastname']));
        $uname = htmlspecialchars($u['username']);
        $content .= "<tr class=\"{$rowCls}\">
            <td>{$i}</td>
            <td><strong>{$uname}</strong></td>
            <td>{$name}</td>
            <td><span class=\"label\" style=\"background:{$roleColor};color:#fff;\">{$role}</span></td>
            <td><code style=\"color:#c0392b;\">{$ip}</code></td>
            <td style=\"white-space:nowrap;\">" . api_get_local_time($lastDate) . "</td>
            <td><span class=\"badge\" style=\"background:#2980b9;\">{$hace}</span></td>
        </tr>";
        $i++;
    }
    $content .= '</tbody></table></div>';
}
$content .= '</div></div>';
$content .= '</div>'; // end tab-usuarios

// -----------------------------------------------------------------------
// TAB 4 — Auditoría de Chamilo (solo usuario ID 1)
// -----------------------------------------------------------------------
if ($isSuperAdmin):

$content .= '<div class="tab-pane" id="tab-auditoria">';

// Queries lentas activas
if (!empty($processList)) {
    $content .= '<div class="alert alert-danger">
        <strong><i class="fa fa-exclamation-triangle"></i> Queries lentas en ejecución ahora</strong>
    </div>
    <div class="table-responsive" style="margin-bottom:20px;">
        <table class="table table-bordered table-condensed" style="font-size:12px;">
            <thead style="background:#c0392b;color:#fff;">
                <tr><th>ID</th><th>Usuario DB</th><th>Tiempo (s)</th><th>Estado</th><th>Query</th></tr>
            </thead><tbody>';
    foreach ($processList as $p) {
        $q = htmlspecialchars(mb_substr($p['Info'] ?? '', 0, 120));
        $content .= "<tr class=\"danger\">
            <td>{$p['Id']}</td>
            <td>{$p['User']}</td>
            <td><strong>{$p['Time']}</strong></td>
            <td>{$p['State']}</td>
            <td><code style=\"font-size:11px;\">{$q}…</code></td>
        </tr>";
    }
    $content .= '</tbody></table></div>';
}

// Historial de eventos
$content .= '<div class="panel panel-default">
    <div class="panel-heading" style="background:#8e44ad;color:#fff;">
        <strong><i class="fa fa-history"></i> Historial de eventos (últimos 7 días)</strong>
        <span class="badge" style="margin-left:8px;background:#fff;color:#8e44ad;">' . count($auditRows) . '</span>
    </div>
    <div class="panel-body" style="padding:0;">';

if (empty($auditRows)) {
    $content .= '<div class="alert alert-info" style="margin:15px;">No hay eventos registrados en los últimos 7 días.</div>';
} else {
    $eventIcons = [
        'user_export' => 'fa-download', 'user_import' => 'fa-upload',
        'certificate_generated' => 'fa-certificate', 'report_generated' => 'fa-bar-chart',
        'user_delete' => 'fa-trash', 'course_delete' => 'fa-trash',
        'backup_course' => 'fa-archive', 'quiz_export' => 'fa-file',
    ];
    $content .= '<div class="table-responsive">
        <table class="table table-striped table-hover table-bordered" style="margin:0;">
            <thead style="background:#8e44ad;color:#fff;">
                <tr>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Evento</th>
                    <th>Tipo de valor</th>
                    <th>Detalle</th>
                </tr>
            </thead><tbody>';
    foreach ($auditRows as $row) {
        $evType  = htmlspecialchars($row['default_event_type']);
        $icon    = $eventIcons[$row['default_event_type']] ?? 'fa-info-circle';
        $uname   = $row['username'] ? htmlspecialchars($row['username']) : '<span class="text-muted">sistema</span>';
        $name    = htmlspecialchars(trim($row['firstname'] . ' ' . $row['lastname']));
        $valType = htmlspecialchars($row['default_value_type'] ?? '');
        $val     = htmlspecialchars(mb_substr($row['default_value'] ?? '', 0, 80));
        $fecha   = api_get_local_time($row['default_date']);
        $content .= "<tr>
            <td style=\"white-space:nowrap;\"><small>{$fecha}</small></td>
            <td><strong>{$uname}</strong><br><small class=\"text-muted\">{$name}</small></td>
            <td><i class=\"fa {$icon}\"></i> <span class=\"label label-default\">{$evType}</span></td>
            <td><small class=\"text-muted\">{$valType}</small></td>
            <td><small><code>{$val}</code></small></td>
        </tr>";
    }
    $content .= '</tbody></table></div>';
}
$content .= '</div></div>';
$content .= '</div>'; // end tab-auditoria
endif; // end isSuperAdmin

$content .= '</div>'; // end tab-content

// JS
$content .= <<<JS
<script>
var ajaxUrl = '{$ajaxUrl}';

function blockIp(ip) {
    ip = ip.trim();
    if (!ip.match(/^\d{1,3}(\.\d{1,3}){3}$/)) {
        alert('IP inválida: ' + ip);
        return;
    }
    if (!confirm('¿Bloquear la IP ' + ip + ' con UFW?')) return;

    fetch(ajaxUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        body: 'action=block&ip=' + encodeURIComponent(ip)
    })
    .then(r => r.json())
    .then(function(data) {
        if (data.success) {
            alert('✓ IP ' + ip + ' bloqueada exitosamente.\\n' + (data.output || ''));
            location.reload();
        } else {
            alert('Error al bloquear: ' + (data.message || 'sin detalle'));
        }
    })
    .catch(function(e) { alert('Error de red: ' + e); });
}

function unblockIp(ip) {
    if (!confirm('¿Desbloquear la IP ' + ip + '?')) return;
    fetch(ajaxUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        body: 'action=unblock&ip=' + encodeURIComponent(ip)
    })
    .then(r => r.json())
    .then(function(data) {
        if (data.success) { alert('✓ IP ' + ip + ' desbloqueada.'); location.reload(); }
        else { alert('Error: ' + (data.message || '')); }
    });
}
</script>
JS;

// Instrucciones sudo
$content .= '<div class="panel panel-warning" style="margin-top:20px;">
    <div class="panel-heading"><strong>Configuración requerida: permisos sudo para www-data</strong></div>
    <div class="panel-body">
        <p>Para que el bloqueo de IPs funcione desde PHP, ejecuta <strong>una sola vez</strong> en el servidor:</p>
        <pre style="background:#222;color:#0f0;padding:12px;border-radius:4px;">sudo tee /etc/sudoers.d/www-data-ufw &lt;&lt;\'EOF\'
www-data ALL=(root) NOPASSWD: /usr/sbin/ufw deny from *
www-data ALL=(root) NOPASSWD: /usr/sbin/ufw delete *
www-data ALL=(root) NOPASSWD: /usr/sbin/ufw status numbered
EOF
sudo chmod 440 /etc/sudoers.d/www-data-ufw</pre>
        <p class="text-muted"><small>Esto limita sudo exclusivamente al comando ufw, sin exponer otras capacidades de root.</small></p>
    </div>
</div>';

$tpl->assign('content', $content);
$tpl->assign('actions', $actionLinks);
$tpl->display_one_col_template();
