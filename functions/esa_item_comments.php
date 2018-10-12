<?php

function esa_get_module_scripts_comments() {
    global $esa_settings;
    if (!$esa_settings['modules']['tags']['activate']) {
        return;
    }
    $dev_suffix = $esa_settings['script_suffix'];

    wp_register_style('esa_item-comments', plugins_url(ESA_DIR . '/css/esa_item-comments.css'));
    wp_enqueue_style('esa_item-comments');

    wp_enqueue_script(
        'esa_item_comments.js',
        plugins_url() . ESA_DIR . '/js/esa_item_comments.js',
        array('jquery')
    );

    wp_localize_script('esa_item_comments.js', 'esaItemCommentsL10n', array());
}

function esa_get_module_content_comments($esa_item) {
    $wrapper = get_esa_item_wrapper($esa_item);

    $comments = get_comments(array(
        'post_id' =>  $wrapper->ID
    ));
    $comment_count = get_comments_number($wrapper->ID);
    $comment_count_s = sprintf(_n('%s Comment', '%s Comments', $comment_count), $comment_count);

    ob_start();

    echo "<div class='esa-item-comments'>";
    echo "<button class='esa-item-show-comments-button' aria-expanded='false' title='$comment_count_s' />$comment_count</button>";

    echo "<div class='esa-item-comments-list'>";
    echo wp_list_comments(array(
        'avatar_size' => 16,
    ), $comments);
    echo "</div>";

    echo "<div class='esa-item-comment-form'>";
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