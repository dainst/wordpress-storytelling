<?php

add_action('admin_menu', function () {

    add_submenu_page(ESA_FILE,'Import to Cache', 'Import to Cache', 'administrator', ESA_FILE . '-import', function () {

        echo "<h1>Import to cache</h1>";

        echo "<div class='wrap' id='esa-import'>";

        echo "<div id='esa-input-form'>";

        if (!isset($_POST['esa_ds_type'])) {
            echo "<h2>Step 1: Select Datasource</h2>";
            echo esa_select_datasource();
            echo "</div>";
            echo "</div>";
            return;
        }



        echo "<h2>Step 3: Start Import</h2>";
        $ds = get_esa_datasource($_POST['esa_ds_type']);

        echo "<div style='display:none'>";
        $ds->search_form();
        echo "</div>";

        $query = $_POST['esa_ds_query'];
        $success = $ds->search($query);

        if ($success) {
            echo "<strong>Import {$ds->pages} pages of data?</strong><br>";
            echo "<input type='checkbox' id='esa-import-copyright'><label for='esa_import_copyright'>I am aware of all copyright issues 
            which may be connected with this action I am 100% repsonsible for that.</label><br>";
            echo "<button id='esa-import-start' disabled>Start</button>";

        } else {
            $ds->show_errors();
        }

        echo "<ol id='esa-import-log' style='list-style-type: decimal'>";
        echo "</ol>";
        echo "<hr>";
        echo "<div id='esa-import-status'></div>";

        echo "</div>";

    });
});

add_action('admin_enqueue_scripts', function($hook) {
    if ($hook == 'storytelling-application_page_wordpress-storytelling/wordpress-storytelling-import') {
        wp_enqueue_script(
            'esa_import.js',
            plugins_url() . ESA_DIR . '/js/esa_import.js',
            array('jquery')
        );
        wp_localize_script('esa_import.js', 'esa', array('ajax_url' => admin_url('admin-ajax.php')));
    }
});


add_action('wp_ajax_esa_get_ds_form', function() {
    $engine = isset($_POST['esa_ds']) ? $_POST['esa_ds'] : false;
    $ds = get_esa_datasource($engine);
    if (!$ds) {
        echo "error.";
        wp_die();
    }
    echo "<h2>Step 2: Select Query Paramters</h2>";
    $ds->search_form();
    wp_die();

});


add_action('wp_ajax_esa_import_next_page', function() {
    if (!isset($_POST['esa_ds_type'])) {
        echo json_encode(array(
            "success" => false,
            "message" => "No Datasource"
        ));
        wp_die();
    }

    ob_start();
    $ds = get_esa_datasource($_POST['esa_ds_type']);
    if (!$ds) {
        echo json_encode(array(
            "success" => false,
            "message" => ob_get_clean()
        ));
        wp_die();
    }
    ob_end_flush();

    if (!$ds->search()) {
        echo json_encode(array(
            "success" => false,
            "message" => implode(",", $ds->errors)
        ));
        wp_die();
    }

    $results = count($ds->results);

    echo json_encode(array(
        "success" => true,
        "message" => "Page {$ds->page} successfully fetched, $results items added.",
        "results" => count($ds->results),
        "page" => $ds->page
    ));

    wp_die();
});

function esa_select_datasource() {
    $current = isset($_POST['esa_ds_type']) ? $_POST['esa_ds_type'] : "";
    $labels = (array) json_decode(get_option('esa_datasource_labels'));

    $return = "<select id='esa-select-datasource'>";
    foreach ($labels as $ed_name => $ed_title) {
        $selected = ($current == $ed_name) ? "selected" : '';
        $return .= "<option value='$ed_name' $selected>$ed_title</option>";
    }

    $return .= "</select>";

    return $return;

}

//get_esa_datasource