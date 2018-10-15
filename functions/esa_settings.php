<?php

$esa_settings = array(
    'post_types' => array('post', 'page'), // post types which can contain embedded content (esa items)
    'add_media_entry' => 'Storytelling Application', // how is the entry called  in the add media dialogue
    'modules' => array('tags', 'comments'),
    'script_suffix' => ""
);

add_action('init', function() {
    global $esa_settings;
    foreach ($esa_settings['modules'] as $modNr => $mod) {
        $esa_settings['modules'][$mod] = call_user_func("esa_get_module_settings_$mod");
        unset($esa_settings['modules'][$modNr]);
        load_settings($esa_settings['modules'], $mod, "esa_settings");
    }

});

function load_settings(&$setting, $setting_name, $option_domain) {
    $option_name = $option_domain . '_' .$setting_name;
    $default_value = isset($setting[$setting_name]['default']) ? $setting[$setting_name]['default'] : null;
    if (!is_null($default_value)) {
        $setting[$setting_name]['value'] = get_option($option_name, $default_value);
    }
    if (isset($setting[$setting_name]['children']) and is_array($setting[$setting_name]['children'])) {
        foreach ($setting[$setting_name]['children'] as $sub_setting_name => $sub_setting) {
            load_settings($setting[$setting_name]['children'], $sub_setting_name, $option_name);
        }
    }
}

/**
 * @param e. G. "modules", "tags", "color", "red"
 * @return array|null
 */
function esa_get_settings() {
    global $esa_settings;
    $args = func_get_args();
    if (!count($args)) {
        return $esa_settings;
    }
    $set = $esa_settings;
    while (count($args)) {
        if (isset($set['children'])) {
            $set = $set['children'];
        }
        $sub = array_shift($args);
        if (!isset($set[$sub])) {
            return null;
        }
        $set = $set[$sub];
    }
    return isset($set['value']) ? $set['value'] : $set;
}

