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
    '/var/log/apache2/access.log',
    '/var/log/apache2/proikos-access.log',
    '/var/log/apache2/hseq-proikos-access.log',
    '/var/log/proikos_connections.log',
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

foreach ($logFiles as $logFile) {
    $source = basename($logFile);
    $handle = fopen($logFile, 'r');
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

        $ts   = strtotime(str_replace('/', ' ', substr($dateStr, 0, 11)) . ' ' . substr($dateStr, 12, 8));
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
    fclose($handle);
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

// Filtrar solo IPs sospechosas por cualquiera de los 3 criterios
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

    if (empty($reasons)) {
        continue;
    }

    $ipData[$ip] = [
        'count'   => $data['total'],
        'errors'  => $data['errors'],
        'e404'    => $data['e404'],
        'paths'   => $data['paths'],
        'reasons' => $reasons,
        'last'    => $data['last'],
        'sources' => $data['sources'],
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
arsort($ipData);  // arsort no funciona bien con arrays anidados — usamos uasort
uasort($ipData, fn($a, $b) => $b['count'] <=> $a['count']);

// -----------------------------------------------------------------------
// Render
// -----------------------------------------------------------------------
$tpl = new Template($tool_name);

$actionLinks = Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    $backUrl
);

$content = '<div class="panel-proikos"><h3>Monitor de Seguridad</h3></div>';

// Panel UFW status
$content .= '<div class="panel panel-default" style="margin-bottom:20px;">
    <div class="panel-heading"><strong>Estado del Firewall (UFW)</strong></div>
    <div class="panel-body">';

if ($ufwInactive) {
    $content .= '<div class="alert alert-danger" style="margin-bottom:10px;">
        <strong><i class="fa fa-exclamation-triangle"></i> UFW está INACTIVO.</strong>
        Las reglas de bloqueo se guardarán pero <u>no tendrán efecto</u> hasta activarlo.<br>
        Ejecuta en el servidor:
        <pre style="background:#222;color:#f90;padding:8px;margin-top:8px;border-radius:4px;">sudo ufw enable</pre>
    </div>';
    $content .= '<pre style="max-height:100px;overflow:auto;font-size:12px;">' . htmlspecialchars($ufwOutput) . '</pre>';
} elseif ($ufwOutput) {
    $content .= '<div class="alert alert-success" style="padding:6px 12px;margin-bottom:8px;">
        <i class="fa fa-shield"></i> <strong>UFW activo</strong>
    </div>';
    $content .= '<pre style="max-height:150px;overflow:auto;font-size:12px;">' . htmlspecialchars($ufwOutput) . '</pre>';
} else {
    $content .= '<div class="alert alert-warning">No se pudo leer el estado de UFW. Verifique los permisos de sudo (ver instrucciones abajo).</div>';
}
$content .= '</div></div>';

// Panel: IPs actualmente bloqueadas en UFW
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
                <th style="width:160px;text-align:center;">Acciones</th>
            </tr>
        </thead>
        <tbody>';
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

// Barra de herramientas: bloquear IP manual
$content .= '<div class="panel panel-default" style="margin-bottom:20px;">
    <div class="panel-heading"><strong>Bloquear IP manualmente</strong></div>
    <div class="panel-body">
        <div class="input-group" style="max-width:400px;">
            <input type="text" id="manual-ip" class="form-control" placeholder="Ej: 192.168.1.100" pattern="\d{1,3}(\.\d{1,3}){3}">
            <span class="input-group-btn">
                <button class="btn btn-danger" onclick="blockIp(document.getElementById(\'manual-ip\').value)">
                    <i class="fa fa-ban"></i> Bloquear con UFW
                </button>
            </span>
        </div>
    </div>
</div>';

// Tabla de IPs sospechosas
$content .= '<div class="panel panel-default">
    <div class="panel-heading" style="background:#333;color:#fff;">
        <strong><i class="fa fa-exclamation-triangle"></i> IPs Sospechosas Detectadas</strong>
        <span class="badge" style="margin-left:8px;">' . count($ipData) . '</span>
        <small style="margin-left:12px;opacity:.8;">Criterios: URL maliciosa · +' . $threshold404 . ' errores 404 · +' . $thresholdRequests . ' requests</small>
    </div>
    <div class="panel-body" style="padding:0;">';

if (empty($ipData)) {
    $content .= '<div class="alert alert-info" style="margin:15px;">No se encontraron conexiones sospechosas en los logs disponibles.</div>';
} else {
    $content .= '<div class="table-responsive">
        <table class="table table-striped table-hover table-bordered" style="margin:0;">
            <thead style="background:#444;color:#fff;">
                <tr>
                    <th>IP</th>
                    <th style="text-align:center;">Total<br>Requests</th>
                    <th style="text-align:center;">Errores<br>404</th>
                    <th>Motivo</th>
                    <th>Rutas escaneadas</th>
                    <th>Última vez</th>
                    <th style="text-align:center;">Estado</th>
                    <th style="text-align:center;">Acción</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($ipData as $ip => $data) {
        $isBlocked   = in_array($ip, $blockedIps);
        $statusBadge = $isBlocked
            ? '<span class="label label-success"><i class="fa fa-lock"></i> Bloqueado</span>'
            : '<span class="label label-danger"><i class="fa fa-circle"></i> Activo</span>';

        $paths     = array_slice($data['paths'], 0, 6);
        $pathsHtml = empty($paths)
            ? '<span class="text-muted">—</span>'
            : implode('<br>', array_map(fn($p) => '<code style="font-size:10px;word-break:break-all;">' . htmlspecialchars($p) . '</code>', $paths));
        if (count($data['paths']) > 6) {
            $pathsHtml .= '<br><small class="text-muted">+' . (count($data['paths']) - 6) . ' más</small>';
        }

        $reasonsHtml = implode(' &nbsp;', array_map(
            fn($r) => '<span class="label label-warning">' . htmlspecialchars($r) . '</span>',
            $data['reasons']
        ));

        $lastSeen = $data['last'] ? date('d/m/Y H:i', $data['last']) : '—';

        $blockBtn = $isBlocked
            ? '<button class="btn btn-xs btn-success" onclick="unblockIp(\'' . htmlspecialchars($ip, ENT_QUOTES) . '\')">
                   <i class="fa fa-unlock"></i> Desbloquear
               </button>'
            : '<button class="btn btn-xs btn-danger" onclick="blockIp(\'' . htmlspecialchars($ip, ENT_QUOTES) . '\')">
                   <i class="fa fa-ban"></i> Bloquear
               </button>';

        // Color de fila: rojo si activa y alta prioridad, verde si bloqueada
        if ($isBlocked) {
            $rowStyle = 'background:#dff0d8;';
        } elseif ($data['e404'] >= $threshold404 || !empty($data['paths'])) {
            $rowStyle = 'background:#f2dede;';
        } else {
            $rowStyle = 'background:#fcf8e3;';
        }

        $e404Display = $data['e404'] > 0
            ? '<span class="badge" style="background:#e74c3c;">' . $data['e404'] . '</span>'
            : '<span class="text-muted">0</span>';

        $content .= "<tr style=\"{$rowStyle}\">
            <td><strong>{$ip}</strong><br><small class=\"text-muted\">" . htmlspecialchars(implode(', ', $data['sources'])) . "</small></td>
            <td style=\"text-align:center;\"><span class=\"badge\" style=\"background:#555;\">{$data['count']}</span></td>
            <td style=\"text-align:center;\">{$e404Display}</td>
            <td>{$reasonsHtml}</td>
            <td>{$pathsHtml}</td>
            <td style=\"white-space:nowrap;\">{$lastSeen}</td>
            <td style=\"text-align:center;\">{$statusBadge}</td>
            <td style=\"text-align:center;\">{$blockBtn}</td>
        </tr>";
    }

    $content .= '</tbody></table></div>';
}

$content .= '</div></div>';

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
