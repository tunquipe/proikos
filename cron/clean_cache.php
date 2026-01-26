<?php
/**
 * Limpia el caché expirado del plugin Proikos
 * Ejecutar cada hora: 0 * * * * php /path/to/chamilo/plugin/proikos/cron/clean_cache.php
 */

require_once __DIR__ . '/../../main/inc/global.inc.php';
require_once __DIR__ . '/../src/cache_manager.php';

$cacheManager = new ProikosCacheManager();
$deleted = $cacheManager->clearExpired();

$logMessage = date('Y-m-d H:i:s') . " - Proikos Cache: Se eliminaron $deleted archivos expirados\n";
error_log($logMessage);

// También puedes escribir en un log específico
$logFile = __DIR__ . '/../logs/cache_clean.log';
file_put_contents($logFile, $logMessage, FILE_APPEND);

echo $logMessage;
