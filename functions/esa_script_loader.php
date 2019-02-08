<?php
/**
 * ******************************************* Register style sheets and javascript
 */

add_action('wp_enqueue_scripts', function() {
    global $post;

    $dev_suffix = esa_get_settings('script_suffix');

    if (is_object($post) and is_esa($post->post_type)) {

        wp_enqueue_style('thickbox');
        wp_register_style('esa_item', plugins_url(ESA_DIR . '/css/esa_item.css'));
        wp_enqueue_style('esa_item');

        esa_register_special_styles();

        wp_enqueue_script('thickbox');
        wp_enqueue_script(
            'esa_item.js',
            plugins_url() . ESA_DIR . '/js/esa_item.js',
            array('jquery')
        );

        wp_localize_script('esa_item.js', 'esa', array('ajax_url' => admin_url('admin-ajax.php')));

        do_action("esa_get_module_scripts");

    }
});

add_action('admin_init', function() {

    // tinyMCE with esa_objects

    add_filter("mce_external_plugins", function($plugin_array) {
        $plugin_array['esa_mce'] = plugins_url() . ESA_DIR . '/js/esa_mce.js';
        $plugin_array['esa_item'] = plugins_url() . ESA_DIR . '/js/esa_item.js';
        add_editor_style(plugins_url() . ESA_DIR . '/css/esa_item.css');
        add_editor_style(plugins_url() . ESA_DIR . '/css/esa_item-mce.css');
        return $plugin_array;
    });

    add_filter('mce_buttons', function($buttons) {
        array_push($buttons, 'whatever');
        return $buttons;
    });

});

add_action('admin_enqueue_scripts', function($hook) {
    if ($hook == 'toplevel_page_' . ESA_NAME . '/' . basename(ESA_FILE, '.php')) {
        wp_enqueue_style('esa_item', plugins_url() . ESA_DIR . '/css/esa_item.css');
        esa_register_special_styles();
        wp_enqueue_style('esa_admin', plugins_url() . ESA_DIR . '/css/esa_admin.css');
        wp_enqueue_script('esa_item', plugins_url() . ESA_DIR . '/js/esa_item.js', array('jquery'));
    }
});


// registers additional stylesheet for enabled datasources
function esa_register_special_styles() {

    $datasources = json_decode(get_option('esa_datasources'));
    if (!is_array($datasources)) {
        $datasources  = array();
    }
    $css = array();
    foreach ($datasources as $ds) {
        $dso = esa_get_datasource($ds);

        if (!$dso) {
            continue;
        }

        $cssInfo = $dso->stylesheet();
        if (isset($cssInfo['css']) and $cssInfo['css']) {
            $css[$cssInfo['name']] = "\n\n/* {$cssInfo['name']} styles ($ds)  */\n" . $cssInfo['css']; // names to avoid dublication if some datasources share the same styles e. g. epidoc
        }
        if (isset($cssInfo['file']) and $cssInfo['file']) {
            wp_enqueue_style('esa_item_' . $cssInfo['name'], $cssInfo['file']);
        }


    }

    wp_add_inline_style('esa_item', implode('\n', $css));
    return implode('\n', $css);
};