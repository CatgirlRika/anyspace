<?php
function get_pages_config() {
    $config_file = __DIR__ . '/../../page_config.php';
    if (file_exists($config_file)) {
        return include($config_file);
    }
    return array(
        'home_welcome' => '',
        'about' => ''
    );
}

function update_page_config($new_config) {
    $config_file = __DIR__ . '/../../page_config.php';
    $config = get_pages_config();
    foreach ($new_config as $key => $value) {
        if (array_key_exists($key, $config)) {
            $config[$key] = $value;
        }
    }
    $config_content = "<?php\nreturn " . var_export($config, true) . ";\n";
    return file_put_contents($config_file, $config_content) !== false;
}
?>
