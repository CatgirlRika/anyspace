<?php
function get_page_content($page) {
    $config_file = __DIR__ . '/../page_config.php';
    if (file_exists($config_file)) {
        $config = include($config_file);
        if (isset($config[$page])) {
            return $config[$page];
        }
    }
    return '';
}
?>
