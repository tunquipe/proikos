<?php

/**
 * Gestor de caché para Proikos Plugin
 * Almacena resultados de consultas en archivos JSON
 */
class ProikosCacheManager
{
    private $cacheDir;
    private $cacheLifetime = 3600; // 1 hora en segundos
    private $enabled = true;

    public function __construct()
    {
        // Directorio de caché dentro del plugin
        $this->cacheDir = __DIR__ . '/../cache/';

        // Verificar y crear directorio con manejo de errores
        $this->initializeCacheDirectory();
    }

    /**
     * Inicializa el directorio de caché
     */
    private function initializeCacheDirectory()
    {
        try {
            // Verificar si el directorio existe
            if (!is_dir($this->cacheDir)) {
                // Intentar crear el directorio
                if (!@mkdir($this->cacheDir, 0777, true)) {
                    $this->enabled = false;
                    error_log("ProikosCacheManager: No se pudo crear el directorio de caché: {$this->cacheDir}");
                    return;
                }

                // Cambiar permisos explícitamente
                @chmod($this->cacheDir, 0777);
            }

            // Verificar permisos de escritura
            if (!is_writable($this->cacheDir)) {
                // Intentar cambiar permisos
                if (!@chmod($this->cacheDir, 0777)) {
                    $this->enabled = false;
                    error_log("ProikosCacheManager: El directorio de caché no tiene permisos de escritura: {$this->cacheDir}");
                    return;
                }
            }

            // Crear .htaccess para proteger el directorio
            $this->createHtaccess();

            // Crear index.html vacío
            $this->createIndexHtml();

        } catch (Exception $e) {
            $this->enabled = false;
            error_log("ProikosCacheManager Error: " . $e->getMessage());
        }
    }

    /**
     * Crea el archivo .htaccess
     */
    private function createHtaccess()
    {
        $htaccessFile = $this->cacheDir . '.htaccess';

        if (!file_exists($htaccessFile)) {
            $htaccessContent = "Order Deny,Allow\nDeny from all";

            if (@file_put_contents($htaccessFile, $htaccessContent) === false) {
                error_log("ProikosCacheManager: No se pudo crear .htaccess");
            } else {
                @chmod($htaccessFile, 0644);
            }
        }
    }

    /**
     * Crea el archivo index.html vacío
     */
    private function createIndexHtml()
    {
        $indexFile = $this->cacheDir . 'index.html';

        if (!file_exists($indexFile)) {
            if (@file_put_contents($indexFile, '') === false) {
                error_log("ProikosCacheManager: No se pudo crear index.html");
            } else {
                @chmod($indexFile, 0644);
            }
        }
    }

    /**
     * Verifica si el caché está habilitado
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Obtiene el identificador del usuario actual para el caché
     */
    private function getUserCacheIdentifier()
    {
        $identifier = [
            'user_id' => api_get_user_id(),
            'is_admin' => api_is_platform_admin(),
            'is_contractor' => api_is_contractor_admin(),
        ];

        // Si es gestor de cupo (contractor admin), agregar su RUC
        if (api_is_contractor_admin()) {
            // Obtener el RUC del usuario actual
            $plugin = ProikosPlugin::create();
            $rucCompany = $plugin::getUserRucCompany();
            $identifier['ruc'] = $rucCompany;
        }

        return $identifier;
    }

    /**
     * Genera una clave única basada en los parámetros de búsqueda
     * Ahora incluye información del usuario/rol
     */
    private function getCacheKey($params)
    {
        // Obtener identificador del usuario
        $userIdentifier = $this->getUserCacheIdentifier();

        // Normalizar parámetros
        $normalized = [
            // Información del usuario/rol
            'user_cache_id' => md5(json_encode($userIdentifier)),

            // Parámetros de búsqueda
            'keyword' => $params['keyword'] ?? '',
            'courseId' => $params['courseId'] ?? '%',
            'sessionId' => $params['sessionId'] ?? '%',
            'ruc' => $params['ruc'] ?? '0',
            'page' => $params['page'] ?? 1,
            'perPage' => $params['perPage'] ?? 25,
            'export' => $params['export'] ?? false
        ];

        ksort($normalized);
        return md5(json_encode($normalized));
    }

    /**
     * Obtiene el nombre del archivo de caché
     */
    private function getCacheFilename($key)
    {
        return $this->cacheDir . 'data_' . $key . '.json';
    }

    /**
     * Verifica si existe caché válido
     */
    public function has($params)
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            $key = $this->getCacheKey($params);
            $filename = $this->getCacheFilename($key);

            if (!file_exists($filename)) {
                return false;
            }

            // Verificar si el archivo es legible
            if (!is_readable($filename)) {
                return false;
            }

            // Verificar si el caché ha expirado
            $fileTime = @filemtime($filename);
            if ($fileTime === false) {
                return false;
            }

            $currentTime = time();

            if (($currentTime - $fileTime) > $this->cacheLifetime) {
                // Caché expirado, eliminar archivo
                @unlink($filename);
                return false;
            }

            return true;
        } catch (Exception $e) {
            error_log("ProikosCacheManager::has() Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene datos del caché
     */
    public function get($params)
    {
        if (!$this->enabled) {
            return null;
        }

        try {
            if (!$this->has($params)) {
                return null;
            }

            $key = $this->getCacheKey($params);
            $filename = $this->getCacheFilename($key);

            $content = @file_get_contents($filename);
            if ($content === false) {
                return null;
            }

            $decoded = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // JSON inválido, eliminar archivo
                @unlink($filename);
                return null;
            }

            return $decoded;
        } catch (Exception $e) {
            error_log("ProikosCacheManager::get() Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Guarda datos en caché
     */
    public function set($params, $data)
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            $key = $this->getCacheKey($params);
            $filename = $this->getCacheFilename($key);

            $userIdentifier = $this->getUserCacheIdentifier();

            $cacheData = [
                'timestamp' => time(),
                'user_identifier' => $userIdentifier,
                'params' => $params,
                'data' => $data
            ];

            $jsonData = json_encode($cacheData, JSON_UNESCAPED_UNICODE);
            if ($jsonData === false) {
                error_log("ProikosCacheManager::set() Error: No se pudo codificar JSON");
                return false;
            }

            $result = @file_put_contents($filename, $jsonData);
            if ($result === false) {
                error_log("ProikosCacheManager::set() Error: No se pudo escribir archivo: $filename");
                return false;
            }

            // Establecer permisos
            @chmod($filename, 0666);

            return true;
        } catch (Exception $e) {
            error_log("ProikosCacheManager::set() Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Limpia todo el caché
     */
    public function clear()
    {
        if (!$this->enabled) {
            return 0;
        }

        try {
            $files = glob($this->cacheDir . 'data_*.json');
            if ($files === false) {
                return 0;
            }

            $count = 0;
            foreach ($files as $file) {
                if (@unlink($file)) {
                    $count++;
                }
            }

            return $count;
        } catch (Exception $e) {
            error_log("ProikosCacheManager::clear() Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Limpia solo el caché del usuario actual
     */
    public function clearUserCache()
    {
        if (!$this->enabled) {
            return 0;
        }

        try {
            $files = glob($this->cacheDir . 'data_*.json');
            if ($files === false) {
                return 0;
            }

            $userIdentifier = $this->getUserCacheIdentifier();
            $userCacheId = md5(json_encode($userIdentifier));
            $count = 0;

            foreach ($files as $file) {
                $content = @file_get_contents($file);
                if ($content !== false) {
                    $decoded = json_decode($content, true);
                    if (isset($decoded['user_identifier'])) {
                        $fileCacheId = md5(json_encode($decoded['user_identifier']));
                        if ($fileCacheId === $userCacheId) {
                            if (@unlink($file)) {
                                $count++;
                            }
                        }
                    }
                }
            }

            return $count;
        } catch (Exception $e) {
            error_log("ProikosCacheManager::clearUserCache() Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Limpia caché de un RUC específico
     */
    public function clearRucCache($ruc)
    {
        if (!$this->enabled) {
            return 0;
        }

        try {
            $files = glob($this->cacheDir . 'data_*.json');
            if ($files === false) {
                return 0;
            }

            $count = 0;

            foreach ($files as $file) {
                $content = @file_get_contents($file);
                if ($content !== false) {
                    $decoded = json_decode($content, true);
                    if (isset($decoded['user_identifier']['ruc']) &&
                        $decoded['user_identifier']['ruc'] == $ruc) {
                        if (@unlink($file)) {
                            $count++;
                        }
                    }
                }
            }

            return $count;
        } catch (Exception $e) {
            error_log("ProikosCacheManager::clearRucCache() Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Limpia caché expirado
     */
    public function clearExpired()
    {
        if (!$this->enabled) {
            return 0;
        }

        try {
            $files = glob($this->cacheDir . 'data_*.json');
            if ($files === false) {
                return 0;
            }

            $currentTime = time();
            $count = 0;

            foreach ($files as $file) {
                $fileTime = @filemtime($file);
                if ($fileTime && ($currentTime - $fileTime) > $this->cacheLifetime) {
                    if (@unlink($file)) {
                        $count++;
                    }
                }
            }

            return $count;
        } catch (Exception $e) {
            error_log("ProikosCacheManager::clearExpired() Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene información del caché
     */
    public function getInfo()
    {
        $info = [
            'enabled' => $this->enabled,
            'total_files' => 0,
            'valid_files' => 0,
            'expired_files' => 0,
            'user_files' => 0, // Archivos del usuario actual
            'total_size' => 0,
            'total_size_mb' => 0,
            'cache_lifetime' => $this->cacheLifetime,
            'cache_lifetime_formatted' => $this->formatSeconds($this->cacheLifetime),
            'cache_dir' => $this->cacheDir,
            'writable' => is_writable($this->cacheDir),
            'exists' => is_dir($this->cacheDir),
            'current_user_role' => $this->getUserRoleDescription()
        ];

        if (!$this->enabled) {
            return $info;
        }

        try {
            $files = glob($this->cacheDir . 'data_*.json');
            if ($files === false) {
                return $info;
            }

            $currentTime = time();
            $userIdentifier = $this->getUserCacheIdentifier();
            $userCacheId = md5(json_encode($userIdentifier));

            foreach ($files as $file) {
                $info['total_files']++;

                $fileSize = @filesize($file);
                $fileTime = @filemtime($file);

                if ($fileSize !== false) {
                    $info['total_size'] += $fileSize;
                }

                // Verificar si es del usuario actual
                $content = @file_get_contents($file);
                if ($content !== false) {
                    $decoded = json_decode($content, true);
                    if (isset($decoded['user_identifier'])) {
                        $fileCacheId = md5(json_encode($decoded['user_identifier']));
                        if ($fileCacheId === $userCacheId) {
                            $info['user_files']++;
                        }
                    }
                }

                if ($fileTime && ($currentTime - $fileTime) > $this->cacheLifetime) {
                    $info['expired_files']++;
                } else {
                    $info['valid_files']++;
                }
            }

            $info['total_size_mb'] = round($info['total_size'] / 1024 / 1024, 2);

        } catch (Exception $e) {
            error_log("ProikosCacheManager::getInfo() Error: " . $e->getMessage());
        }

        return $info;
    }

    /**
     * Obtiene descripción del rol del usuario actual
     */
    private function getUserRoleDescription()
    {
        if (api_is_platform_admin()) {
            return 'Administrador de Plataforma';
        }

        if (api_is_contractor_admin()) {
            $plugin = ProikosPlugin::create();
            $ruc = $plugin::getUserRucCompany();
            return 'Gestor de Cupo (RUC: ' . $ruc . ')';
        }

        return 'Usuario Regular';
    }

    /**
     * Establece el tiempo de vida del caché en segundos
     */
    public function setCacheLifetime($seconds)
    {
        $this->cacheLifetime = (int)$seconds;
    }

    /**
     * Formatea segundos a formato legible
     */
    private function formatSeconds($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }

        return $minutes . 'm';
    }

    /**
     * Obtiene la fecha de última actualización del caché para los parámetros dados
     */
    public function getLastUpdate($params)
    {
        if (!$this->enabled) {
            return null;
        }

        try {
            $key = $this->getCacheKey($params);
            $filename = $this->getCacheFilename($key);

            if (!file_exists($filename)) {
                return null;
            }

            $content = @file_get_contents($filename);
            if ($content === false) {
                return null;
            }

            $decoded = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }

            if (isset($decoded['timestamp'])) {
                return [
                    'timestamp' => $decoded['timestamp'],
                    'datetime' => date('d/m/Y H:i:s', $decoded['timestamp']),
                    'relative' => $this->getRelativeTime($decoded['timestamp']),
                    'from_cache' => true
                ];
            }

            return null;
        } catch (Exception $e) {
            error_log("ProikosCacheManager::getLastUpdate() Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene el tiempo relativo desde una marca de tiempo
     */
    private function getRelativeTime($timestamp)
    {
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'hace ' . $diff . ' segundo' . ($diff != 1 ? 's' : '');
        }

        $minutes = floor($diff / 60);
        if ($minutes < 60) {
            return 'hace ' . $minutes . ' minuto' . ($minutes != 1 ? 's' : '');
        }

        $hours = floor($minutes / 60);
        if ($hours < 24) {
            return 'hace ' . $hours . ' hora' . ($hours != 1 ? 's' : '');
        }

        $days = floor($hours / 24);
        return 'hace ' . $days . ' día' . ($days != 1 ? 's' : '');
    }

    /**
     * Obtiene el tiempo restante hasta que expire el caché
     */
    public function getTimeUntilExpiration($params)
    {
        if (!$this->enabled) {
            return null;
        }

        try {
            $key = $this->getCacheKey($params);
            $filename = $this->getCacheFilename($key);

            if (!file_exists($filename)) {
                return null;
            }

            $fileTime = @filemtime($filename);
            if ($fileTime === false) {
                return null;
            }

            $expiresAt = $fileTime + $this->cacheLifetime;
            $timeLeft = $expiresAt - time();

            if ($timeLeft <= 0) {
                return null;
            }

            $minutes = floor($timeLeft / 60);
            $seconds = $timeLeft % 60;

            return [
                'seconds' => $timeLeft,
                'formatted' => $minutes . ':' . str_pad($seconds, 2, '0', STR_PAD_LEFT),
                'expires_at' => date('H:i:s', $expiresAt)
            ];
        } catch (Exception $e) {
            error_log("ProikosCacheManager::getTimeUntilExpiration() Error: " . $e->getMessage());
            return null;
        }
    }
}
