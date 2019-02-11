<?php
register_activation_hook(ESA_FILE, function() {

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    global $wpdb;

    // need a special table for that cache
    $table_name = $wpdb->prefix . "esa_item_cache";
    $sql =
        "CREATE TABLE $table_name (
        source VARCHAR(12) NOT NULL,
        id VARCHAR(200) NOT NULL,
        content LONGTEXT NULL,
        searchindex TEXT NULL,
        url TEXT NULL,
        title TEXT NULL,
        timestamp DATETIME NOT NULL,
        latitude FLOAT NULL,
        longitude FLOAT NULL,
        PRIMARY KEY (source, id)
    )
    COLLATE utf8_general_ci
    ENGINE = MYISAM
    ;";

    dbDelta($sql);

    // because esa_item has two columns as index, we can't solve this with a taxonomy...
    $table_name = $wpdb->prefix . "esa_item_to_post";
    $sql =
        "CREATE TABLE $table_name (
        post_id BIGINT(20) UNSIGNED NOT NULL,
        esa_item_source VARCHAR(12) NOT NULL,
        esa_item_id VARCHAR(200) NOT NULL
    )
    COLLATE utf8_general_ci
    ENGINE = MYISAM
    ;";

    dbDelta($sql);

    // run sub-plugins
    do_action("esa_install");

    // default options
    add_option('esa_datasources',  json_encode(array("idai", "wiki", "commons", "pelagios")));

    $dsfiles = glob(ESA_PATH . "datasources/*.class.php");
    $labels = array();
    foreach ($dsfiles as $filename) {
        $name = basename($filename, '.class.php');
        $ds = esa_get_datasource($name);
        $labels[$name] = $ds->title;
    }
    add_option('esa_datasource_labels', json_encode($labels));




});

