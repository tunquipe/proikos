<?php

require_once __DIR__.'/config.php';
$plugin = ProikosPlugin::create();
$enable = $plugin->get('tool_enable') == 'true';
$nameTools = $plugin->get_lang('AcademicResults');
$htmlHeadXtra[] = api_get_css(api_get_path(WEB_PLUGIN_PATH) . 'proikos/css/style.css');

api_block_anonymous_users();


$tpl = new Template($nameTools, true, true, false, false, true, false);
$content = $tpl->fetch('proikos/view/proikos_results.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
