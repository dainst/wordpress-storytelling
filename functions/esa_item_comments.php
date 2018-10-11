<?php

function esa_get_module_scripts_comments() {
    // no special scripts needed
}

function esa_get_module_content_comments($esa_item) {
    $wrapper = get_esa_item_wrapper($esa_item);

    $comments = get_comments(array(
        'avatar_size' => 16,
    ));

    ob_start();

    echo "<div class='esa_item_comments'>";

    echo "<div class='esa_item_comments_list'>";

    echo wp_list_comments(array(
        'post_id' =>  $wrapper->ID
    ), $comments);
    echo "</div>";

    echo "<div class='esa_item_comment_form'>";

    comment_form(array(
        "must_log_in" => false
    ), $wrapper->ID);
    echo "</div>";

    echo "</div>";

    return ob_get_clean();
}

add_filter('wp_insert_post_data', function($data) {
    global $esa_settings;
    $esmc = $esa_settings['modules']['comments'];
    if($data['post_type'] == 'esa_item_wrapper') {
        $data['comment_status'] = (($esmc['comments_open_by_default']) and ($esmc['activate'])) ? 'open' : 'close';
    }

    return $data;
});