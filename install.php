<?php

/**
 * Config the plugin.
 *
 * @author Alex Aragon <alex.aragon@tunqui.pe>
 *
 * @package chamilo.plugin.proikos
 */

require_once __DIR__.'/config.php';

if (!api_is_platform_admin()) {
    die('You must have admin permissions to install plugins');
}

ProikosPlugin::create()->install();
