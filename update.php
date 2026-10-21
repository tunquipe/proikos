<?php
/**
 * This script is meant to update the database of a previous version of the plugin.
 *
 * @author Alex Aragon <alex.aragon@tunqui.pe>
 *
 * @package chamilo.plugin.proikos
 */

require_once __DIR__.'/config.php';

if (!api_is_platform_admin()) {
    die('You must have admin permissions to update plugins');
}

ProikosPlugin::create()->update();

echo 'Proikos plugin updated';
