<?php

require_once __DIR__.'/config.php';
$plugin = ProikosPlugin::create();
$enable = $plugin->get('tool_enable') == 'true';
$nameTools = $plugin->get_lang('DashboardProikos');
$htmlHeadXtra[] = api_get_css(api_get_path(WEB_PLUGIN_PATH) . 'proikos/css/style.css');

api_block_anonymous_users();

if ($enable) {
    if (api_is_platform_admin() || api_is_drh() || api_is_contractor_admin()) {
        $tpl = new Template($nameTools, true, true, false, false, true, false);
        $tpl->assign('src_plugin', api_get_path(WEB_PLUGIN_PATH) . 'proikos/');
        $tpl->assign('is_platform_admin', api_is_platform_admin());
        $tpl->assign('is_drh', api_is_drh());
        $tpl->assign('is_contractor_admin', api_is_contractor_admin());
        $content = $tpl->fetch('proikos/view/proikos_start.tpl');
        $tpl->assign('content', $content);
        $tpl->display_one_col_template();
    }
}
