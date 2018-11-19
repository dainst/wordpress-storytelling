<?php

add_action('admin_menu', function () {

    add_submenu_page(ESA_FILE, 'Settings', 'Settings', 'administrator', ESA_FILE . '-settings', function () {

        $url = admin_url('admin.php');

        echo "<div class='wrap' id='esa_settings'>";
        echo "<h2>Settings</h2>";

        echo "<form method='POST' action='$url'>";

        esa_settings_datasources();

        $all_settings_names = esa_settings_features();

        // to update  also checkboxes which are set to false and get not submitted
        echo "<input type='hidden' name='esa_all_settings' value='" . implode(',', $all_settings_names) . "' />";

        wp_nonce_field('esa_save_settings', 'esa_save_settings_nonce');
        echo "<input type='hidden' name='action' value='esa_save_settings'>";
        echo "<input type='submit' value='Save' class='button button-primary'>";
        echo "</form>";

        echo "</div>";

    });
});

function esa_settings_datasources() {
    echo "<h3>Available Data Sources</h3>";
    $datasources = json_decode(get_option('esa_datasources'));
    if (!is_array($datasources)) {
        $datasources  = array();
    }
    echo "<p>Here you can see all currently installed sub-plugins, which are connectors to several epigraphic / other datasources.";
    $ds_files = esa_collect_datasource_files();
    $labels = array();
    $optionlist = array();
    foreach ($ds_files as $name => $filename) {
        try  {
            $ds = get_esa_datasource($name);
            $label = $ds->title;
            $labels[$name] = $label;
            $is_ok = true;
            $status = $ds->dependency_check();
        } catch(\exception $e) {
            $is_ok = false;
            $status = $e->getMessage();
        }
        $status = ($is_ok === true) ? "<span style='color:green'>($status)</span>" : "<span style='color:red'>(Error: $status)</span>";
        $checked = ((in_array($name, $datasources)) and ($is_ok === true)) ?  'checked="checked"' : '';
        $disabled = ($is_ok === true) ? '' : 'disabled="disabled"';
        $optionlist[$ds->index] = "<li><input type='checkbox' name='esa_datasources[]' value='$name' id='esa_activate_datasource_$name' $checked $disabled /><label for='esa_activate_datasource_$name'>$label $status</label></li>";
    }
    ksort($optionlist);
    echo "<ul>" . implode("\n", $optionlist) . "</ul>";
    update_option('esa_datasource_labels', json_encode($labels));
    //update_option('esa_datasources') = json_encode($list);
}

function esa_settings_features($settings_set = false, $parent = "esa_settings", $level = 3) {
    $esa_settings = esa_get_settings();
    $settings_set = !$settings_set ? $esa_settings['modules'] : $settings_set;
    $all_settings_names = array();

    echo "<ul>";
    foreach ($settings_set as $setting_name => $setting) {

        $name = $parent . '_' . $setting_name;
        $all_settings_names [] = $name;

        echo "<li>";

        if (isset($setting['value'])) {
            $label = isset($setting['label']) ? $setting['label'] : '#' . $setting_name;
            $disabled = "";
            echo "<input ";
            foreach ($setting as $attr => $attr_value) {
                if (in_array($attr, array('default', 'label', 'children', 'value'))) {
                    continue;
                }
                echo " $attr='$attr_value'";
            }
            echo " name='$name' id='$name' $disabled";
            if (in_array($setting['type'], array('checkbox', 'radio'))) {
                echo $setting['value'] ? " checked='{$setting['value']}'" : '';
            } else {
                echo " value='{$setting['value']}'";
            }
            echo " /><label for='$name'>$label</label>";
        }

        if (isset($setting['children']) and is_array($setting['children'])) {
            echo "<h$level>" . $setting['label'] . "</h$level>";
            $all_settings_names = array_merge(esa_settings_features($setting['children'], $name,$level + 1), $all_settings_names);
        }

        if (isset($setting['info'])) {
            echo "<p>{$setting['info']}</p>";
        }

        echo "</li>";

    }
    echo "</ul>";

    return $all_settings_names;
}