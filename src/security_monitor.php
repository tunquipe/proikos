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
    '/var/log/proikos_connections.log',
], fn($f) => is_readable($f) && filesize($f) > 0);

// Patrones que delatan escaneo/ataque
$suspiciousPatterns = [
    '/\.env\b/', '/xmlrpc\.php/', '/wp-admin/', '/wp-login\.php/',
    '/\.git\//', '/phpmyadmin/', '/pma\//', '/adminer/',
    '/config\.php/', '/backup/', '/\.sql\b/', '/\.bak\b/',
    '/shell\.php/', '/cmd\.php/', '/eval\(/', '/base64_decode/',
    '/etc\/passwd/', '/proc\/self/', '/\.\.\//','/../',
    '/setup\.php/', '/install\.php/', '/phpinfo/',
    '/\.(asp|aspx|jsp|cfm)\b/i',
];

// -----------------------------------------------------------------------
// Parsear logs de Apache
// -----------------------------------------------------------------------
$ipData = [];   // [ ip => ['count'=>N, 'paths'=>[], 'last'=>timestamp, 'sources'=>[]] ]

foreach ($logFiles as $logFile) {
    $source = basename($logFile);
    $handle = fopen($logFile, 'r');
    if (!$handle) {
        continue;
    }
    while (($line = fgets($handle)) !== false) {
        // Formato Combined Log: IP - - [date] "METHOD URI proto" status bytes ...
        if (!preg_match('/^(\d{1,3}(?:\.\d{1,3}){3})\s+-\s+-\s+\[([^\]]+)\]\s+"[A-Z]+\s+([^\s"]+)/', $line, $m)) {
            continue;
        }
        [, $ip, $dateStr, $uri] = $m;

        $isSuspicious = false;
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $uri)) {
                $isSuspicious = true;
                break;
            }
        }
        if (!$isSuspicious) {
            continue;
        }

        // Parsear fecha: "13/May/2026:00:20:14 -0500"
        $ts = strtotime(str_replace('/', ' ', substr($dateStr, 0, 11)) . ' ' . substr($dateStr, 12, 8));

        if (!isset($ipData[$ip])) {
            $ipData[$ip] = ['count' => 0, 'paths' => [], 'last' => 0, 'sources' => []];
        }
        $ipData[$ip]['count']++;
        $path = strtok($uri, '?');
        if (!in_array($path, $ipData[$ip]['paths'])) {
            $ipData[$ip]['paths'][] = $path;
        }
        if ($ts > $ipData[$ip]['last']) {
            $ipData[$ip]['last'] = $ts;
        }
        if (!in_array($source, $ipData[$ip]['sources'])) {
            $ipData[$ip]['sources'][] = $source;
        }
    }
    fclose($handle);
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
$blockedIps  = [];
if ($ufwOutput && !$ufwInactive) {
    preg_match_all('/DENY\s+(?:IN\s+)?(\d{1,3}(?:\.\d{1,3}){3})/i', $ufwOutput, $ufwMatches);
    $blockedIps = array_unique($ufwMatches[1] ?? []);
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
    <div class="panel-heading">
        <strong>IPs Sospechosas Detectadas</strong>
        <span class="badge" style="margin-left:8px;">' . count($ipData) . '</span>
    </div>
    <div class="panel-body">';

if (empty($ipData)) {
    $content .= '<div class="alert alert-info">No se encontraron conexiones sospechosas en los logs disponibles.</div>';
} else {
    $content .= '<div class="table-responsive">
        <table class="table table-striped table-hover table-bordered" id="security-table">
            <thead class="thead-dark" style="background:#333;color:#fff;">
                <tr>
                    <th>IP</th>
                    <th>Intentos</th>
                    <th>Archivos / Rutas escaneadas</th>
                    <th>Última vez</th>
                    <th>Fuente de log</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($ipData as $ip => $data) {
        $isBlocked = in_array($ip, $blockedIps);
        $statusBadge = $isBlocked
            ? '<span class="label label-success">Bloqueado</span>'
            : '<span class="label label-danger">Activo</span>';

        $paths = array_slice($data['paths'], 0, 8);
        $pathsHtml = implode('<br>', array_map(fn($p) => '<code style="font-size:11px;">' . htmlspecialchars($p) . '</code>', $paths));
        if (count($data['paths']) > 8) {
            $pathsHtml .= '<br><small>... y ' . (count($data['paths']) - 8) . ' más</small>';
        }

        $lastSeen = $data['last'] ? date('d/m/Y H:i', $data['last']) : '-';
        $sources  = htmlspecialchars(implode(', ', $data['sources']));

        $blockBtn = $isBlocked
            ? '<button class="btn btn-xs btn-success" onclick="unblockIp(\'' . htmlspecialchars($ip, ENT_QUOTES) . '\')">
                    <i class="fa fa-unlock"></i> Desbloquear
               </button>'
            : '<button class="btn btn-xs btn-danger" onclick="blockIp(\'' . htmlspecialchars($ip, ENT_QUOTES) . '\')">
                    <i class="fa fa-ban"></i> Bloquear
               </button>';

        $rowClass = $isBlocked ? 'success' : ($data['count'] >= 10 ? 'danger' : 'warning');

        $content .= "<tr class=\"{$rowClass}\">
            <td><strong>{$ip}</strong></td>
            <td><span class=\"badge\">{$data['count']}</span></td>
            <td>{$pathsHtml}</td>
            <td>{$lastSeen}</td>
            <td><small>{$sources}</small></td>
            <td>{$statusBadge}</td>
            <td>{$blockBtn}</td>
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
