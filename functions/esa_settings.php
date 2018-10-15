<?php

$esa_settings = array(
    'post_types' => array('post', 'page'), // post types which can contain embedded content (esa items)
    'add_media_entry' => 'Storytelling Application', // how is the entry called  in the add media dialogue
    'modules' => array('tags', 'comments'),
    'script_suffix' => ""
);

$esa_labels = array(
    "esa_settings" => "Feature Settings"
);

add_action('init', function() {
    global $esa_settings;
    foreach ($esa_settings['modules'] as $modNr => $mod) {
        $esa_settings['modules'][$mod] = call_user_func("esa_get_module_settings_$mod");
        unset($esa_settings['modules'][$modNr]);
        load_settings($esa_settings['modules'], $mod, "esa_{$mod}");
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
            load_settings($setting[$setting_name]['children'], $sub_setting_name, $option_domain);
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

/*
 * 'tags' => array(
                'activate' => true, // is the tagging feature active
                'visitor_can_add' => true, // can tags be edited at the frontend
                'visitor_can_create' => true, // can new tags be created in the frontend?
                'visitor_can_delete' => true, // can tags be deleted in the frontend?
                'color' => array( // rgb color for the tags. channels which are set to false will get an automatic values
                    'red' => 0,
                    'green' => 75,
                    'blue' => false
                )
            ),
            'comments' => array(
                'activate' => true, // is the comment feature active
                'comments_open_by_default' => true,
                'esa_style' => true
            )
 */