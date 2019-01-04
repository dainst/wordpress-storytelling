<?php

add_filter('default_content', function($content, $post) {

    if (!is_esa($post->post_type)) {
        return $content;
    }

    if (!isset($_GET['esa_item_id']) or !isset($_GET['esa_item_source'])) {
        return $content;
    }

    $content = "[esa source=\"{$_GET['esa_item_source']}\" id=\"{$_GET['esa_item_id']}\"]";
    return $content;
}, 10, 2);
