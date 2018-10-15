<?php
/**
 * *******************************************add media submenu!
 */

// add them to media menu
add_filter('media_upload_tabs', function($tabs) {
    global $post;
    return (!is_object($post) or is_esa($post->post_type)) ?
        array_merge($tabs, array('esa' => esa_get_settings('add_media_entry'))) :
        $tabs;
});

// create submenu
add_action('media_upload_esa', function() {

    add_action('admin_print_styles-media-upload-popup', function() {
        wp_enqueue_style('colors');
        wp_enqueue_style('media');
        wp_enqueue_style('media-views');
        wp_enqueue_style('thickbox');
        wp_enqueue_style('esa_item', plugins_url() . ESA_DIR . '/css/esa_item.css');
        esa_register_special_styles();
        wp_enqueue_style('esa_item-mediaframe', plugins_url() . ESA_DIR . '/css/esa_item-mediaframe.css');
    });


    add_action('admin_print_scripts-media-upload-popup', function() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('thickbox');
        wp_enqueue_script('esa_item.js', plugins_url() . ESA_DIR . '/js/esa_item.js', array('jquery'));
        wp_enqueue_script('esa_mediamenu.js', plugins_url() . ESA_DIR . '/js/esa_mediamenu.js', array('jquery'));

    });

    // this builds the iframe which is in fact the add media dialogue of our plugin!
    return wp_iframe('media_esa_dialogue');
});


function media_esa_dialogue() {

    //    error_reporting(E_ALL | E_NOTICE);
    //    ini_set('display_errors', 1);

    $esa_datasources = json_decode(get_option('esa_datasources'));
    $labels = (array) json_decode(get_option('esa_datasource_labels'));
    $post_id = isset($_REQUEST['post_id']) ? intval($_REQUEST['post_id']) : 0;
    $item_id = isset($_GET['esa_id']) ? $_GET['esa_id'] : null;
    $engine  = isset($_GET['esa_source']) ? $_GET['esa_source'] : $esa_datasources[0];

    // get current search engine
    $eds = ($engine != '_info') ? get_esa_datasource($engine) : '';

    //media_upload_header();

    if(empty($esa_datasources)) {
        echo "<p>Error: No active Sub-Plugins found. <a href='admin.php?page=eagle-storytelling-application/eagle-storytelling.php' target='_parent'> In the admin menu, at 'Eagle Storytelling Application' </a> you can activate some.</p>";
        return;
    }

    echo "<div id='esa-mediaframe'>";

    echo "<div class='media-frame-router'>";
    echo "<div class='media-router'>";


    // create search engine menu
    foreach ($esa_datasources as $source) {
        $sel = ($source == $engine) ? 'active' : '';
        $label = $labels[$source];
        echo "<a class='media-menu-item $sel' href='?tab=esa&esa_source=$source'>$label</a>";
    }
    $sel = ('_info' == $engine) ? 'active' : '';
    echo "<a class='media-menu-item $sel' href='?tab=esa&esa_source=_info'>?</a>";
    echo "</div>";
    echo "</div>"; //media-router



    if ($engine == '_info') {
        esa_info();
    } else {
        //Sidebar
        echo "<div id='esa_item_list_sidebar'>";
        echo "<div id='esa_item_preview' class='esa_item esa_item_$engine'></div>";

        echo '<div id="esa_item_settings"><form>';
        echo '<p>Some <strong>optional</strong> parameters to define <br />the looks of your Item. Leave out for <a title="reset to default settings" href="#" onclick="esa_ds.reset_form()"> default</a>.</p>';

        echo '<div class="esa_item_setting">';
        echo '<label for="height">' . __('Height') . '</label>';
        echo '<input type="number" min="0" name="height" value="">';
        echo "</div>";

        echo '<div class="esa_item_setting">';
        echo '<label for="width">' . __('Width') . '</label>';
        echo '<input type="number" min="0" name="width" value="">';
        echo "</div>";

        echo '<div class="esa_item_setting">';
        echo '<label for="align">' . __('Align') . '</label>';
        echo '<select height="1" name="align">
				<option value="" selected>none</option>
				<option value="left">Left</option>
				<option value="right">Right</option>
			</select>';
        echo "</div>";

        if (count($eds->optional_classes)) {
            echo '<div class="esa_item_setting">';
            echo '<label for="mode">' . __('Modus') . '</label>';
            echo '<select height="1" name="mode">
					<option value="" selected>none</option>';
            foreach ($eds->optional_classes as $key => $caption) {
                echo "<option value='$key'>$caption</option>";
            }
            echo '</select>';

            echo "</div>";
        }

        echo "</form></div>";


        echo "</div>"; //esa_item_list_sidebar

        // main
        echo "<div class='media-frame-content'>";

        echo "<div class='attachments-browser'>";

        $query = $item_id ? $eds->api_record_url($item_id) : null;
        $success = $eds->search($query);
        $eds->search_form();
        echo '<div id="media-items">';
        if ($success) {
            $eds->show_result();
        } else {
            $eds->show_errors();
        }

        echo '</div>'; //media-items
        echo '</div>'; //attachments-browser
    }

    echo "</div>"; //media-frame-content

    echo "<div class='media-frame-toolbar'>";
    echo "<div class='media-toolbar'>";
    echo '<div class="media-toolbar-primary search-form">';
    echo '<input type="button" class="button button-primary media-button" id="go_button" disabled="disabled" onclick="esa_ds.insert()" value="' . esc_attr__('Insert into Story') . '" />';
    echo "</div>"; //media-toolbar-primary search-form
    echo "</div>"; //media-toolbar
    echo "</div>"; //media-frame-toolbar

    echo "</div>"; // esa-mediaframe
    //echo "<div class='timestamp'>Time: ", microtime(true) -$time, "</div>";
}