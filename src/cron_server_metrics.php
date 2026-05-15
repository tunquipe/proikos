<?php
/**
 * Cron: guarda métricas del servidor cada hora en plugin_proikos_server_metrics.
 * Ejecutar vía crontab: 0 * * * * php /var/www/proikos/plugin/proikos/src/cron_server_metrics.php
 */

// Permitir ejecución CLI o vía URL (solo admin)
if (PHP_SAPI !== 'cli') {
    require_once __DIR__ . '/../config.php';
    api_block_anonymous_users();
    if (!api_is_platform_admin()) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }
} else {
    // CLI: bootstrap mínimo de Chamilo
    $_SERVER['HTTP_HOST']   = 'localhost';
    $_SERVER['REQUEST_URI'] = '/';
    require_once __DIR__ . '/../../../main/inc/global.inc.php';
}

// -----------------------------------------------------------------------
// Asegurar que la tabla exista
// -----------------------------------------------------------------------
$createTable = "
CREATE TABLE IF NOT EXISTS `plugin_proikos_server_metrics` (
    `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `recorded_at`        DATETIME     NOT NULL,
    `hour_of_day`        TINYINT      NOT NULL COMMENT '0-23',
    `day_of_week`        TINYINT      NOT NULL COMMENT '0=Dom 6=Sab',
    `cpu_pct`            DECIMAL(5,2) DEFAULT NULL,
    `cpu_cores`          TINYINT      DEFAULT NULL,
    `ram_pct`            DECIMAL(5,2) DEFAULT NULL,
    `ram_used_gb`        DECIMAL(6,2) DEFAULT NULL,
    `ram_total_gb`       DECIMAL(6,2) DEFAULT NULL,
    `disk_pct`           DECIMAL(5,2) DEFAULT NULL,
    `disk_used_gb`       SMALLINT     DEFAULT NULL,
    `disk_total_gb`      SMALLINT     DEFAULT NULL,
    `load_1min`          DECIMAL(6,2) DEFAULT NULL,
    `load_5min`          DECIMAL(6,2) DEFAULT NULL,
    `load_15min`         DECIMAL(6,2) DEFAULT NULL,
    `load_pct`           DECIMAL(5,2) DEFAULT NULL,
    `apache_active`      TINYINT(1)   DEFAULT NULL,
    `mysql_active`       TINYINT(1)   DEFAULT NULL,
    `active_connections` SMALLINT     DEFAULT NULL,
    INDEX `idx_recorded_at` (`recorded_at`),
    INDEX `idx_hour`        (`hour_of_day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
Database::query($createTable);

// -----------------------------------------------------------------------
// Recolectar métricas (mismos comandos que security_monitor.php)
// -----------------------------------------------------------------------
function sm($cmd): string {
    return trim((string) @shell_exec($cmd . ' 2>/dev/null'));
}

// CPU
$cpuRaw   = sm("top -bn1 | grep 'Cpu(s)' | awk '{print $2}'");
$cpuPct   = (float) str_replace(',', '.', $cpuRaw);
$cpuCores = (int)(sm("nproc") ?: 1);

// RAM
$memInfo  = sm("free | grep Mem");
$memParts = preg_split('/\s+/', $memInfo);
$ramTotal = (int)($memParts[1] ?? 1);
$ramUsed  = (int)($memParts[2] ?? 0);
$ramPct   = $ramTotal > 0 ? round($ramUsed / $ramTotal * 100, 1) : 0;
$ramUsedGb  = round($ramUsed  / 1024 / 1024, 2);
$ramTotalGb = round($ramTotal / 1024 / 1024, 2);

// Disco
$diskRaw   = sm("df -BG / | tail -1 | awk '{print $2, $3, $5}'");
$diskParts = preg_split('/\s+/', $diskRaw);
$diskTotal = (int) str_replace('G', '', $diskParts[0] ?? '1');
$diskUsed  = (int) str_replace('G', '', $diskParts[1] ?? '0');
$diskPct   = (float) str_replace('%', '', $diskParts[2] ?? '0');

// Load average
$loadRaw   = sm("cat /proc/loadavg");
$loadParts = explode(' ', $loadRaw);
$load1     = (float)($loadParts[0] ?? 0);
$load5     = (float)($loadParts[1] ?? 0);
$load15    = (float)($loadParts[2] ?? 0);
$loadPct   = $cpuCores > 0 ? round($load1 / $cpuCores * 100, 1) : 0;

// Servicios
$apacheActive = (sm("systemctl is-active apache2") === 'active') ? 1 : 0;
$mysqlActive  = (sm("systemctl is-active mysql") === 'active' || sm("systemctl is-active mariadb") === 'active') ? 1 : 0;

// Conexiones activas HTTP/S
$activeConns = (int) sm("ss -tn state established | grep -c ':80\|:443'");

// -----------------------------------------------------------------------
// Insertar en la tabla
// -----------------------------------------------------------------------
$now        = new DateTime();
$recordedAt = $now->format('Y-m-d H:i:s');
$hourOfDay  = (int) $now->format('G');
$dayOfWeek  = (int) $now->format('w');

$sql = "INSERT INTO `plugin_proikos_server_metrics`
    (recorded_at, hour_of_day, day_of_week,
     cpu_pct, cpu_cores,
     ram_pct, ram_used_gb, ram_total_gb,
     disk_pct, disk_used_gb, disk_total_gb,
     load_1min, load_5min, load_15min, load_pct,
     apache_active, mysql_active, active_connections)
VALUES
    ('$recordedAt', $hourOfDay, $dayOfWeek,
     $cpuPct, $cpuCores,
     $ramPct, $ramUsedGb, $ramTotalGb,
     $diskPct, $diskUsed, $diskTotal,
     $load1, $load5, $load15, $loadPct,
     $apacheActive, $mysqlActive, $activeConns)";

Database::query($sql);

// Purgar registros con más de 90 días para no crecer indefinidamente
Database::query("DELETE FROM `plugin_proikos_server_metrics` WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");

$result = [
    'ok'          => true,
    'recorded_at' => $recordedAt,
    'cpu_pct'     => $cpuPct,
    'ram_pct'     => $ramPct,
    'disk_pct'    => $diskPct,
    'load_1min'   => $load1,
    'connections' => $activeConns,
];

if (PHP_SAPI === 'cli') {
    echo "[{$recordedAt}] Métricas guardadas: CPU {$cpuPct}% | RAM {$ramPct}% | Disco {$diskPct}% | Load {$load1} | Conn {$activeConns}\n";
} else {
    header('Content-Type: application/json');
    echo json_encode($result);
}
