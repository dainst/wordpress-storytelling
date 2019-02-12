<?php

add_action("esa_install", function() {

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    global $wpdb;

    $table_name = $wpdb->prefix . "esa_item_data_cache";
    $sql =
        "CREATE TABLE $table_name (
        source VARCHAR(12) NOT NULL,
        id VARCHAR(200) NOT NULL,
        language VARCHAR(12) NOT NULL,
        key VARCHAR(30) NOT NULL,
        value VARCHAR(500) NOT NULL
    )
    COLLATE utf8_general_ci
    ENGINE = MYISAM
    ;";

    dbDelta($sql);
});

add_action("esa_flush_cache", function($wrappers) {
    global $wpdb;
    $sql = "truncate {$wpdb->prefix}esa_item_cache;";
    $wpdb->query($sql);
}, 10, 1);