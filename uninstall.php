<?php
/**
 * Config the plugin.
 *
 * @author Alex Aragon <alex.aragon@tunqui.pe>
 *
 * @package chamilo.plugin.proikos
 */

require_once __DIR__.'/config.php';

Proikos::create()->uninstall();
