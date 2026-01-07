<?php
namespace UmhMgmt\Utils;

class View {
    public static function render($template, $data = []) {
        extract($data);
        $template_path = UMH_PLUGIN_DIR . 'templates/' . $template . '.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        }
    }
}
