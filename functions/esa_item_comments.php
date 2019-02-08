<?php

add_action('init', function() {
    add_action('wp_ajax_esa-comment-list', 'esa_comment_list', 1);
    add_action('wp_ajax_nopriv_esa-comment-list', 'esa_comment_list', 1);
});


add_filter('wp_insert_post_data', function($data) {
    if($data['post_type'] == 'esa_item_wrapper') {
        $data['comment_status'] =
            (esa_get_settings('modules', 'comments', 'comments_open_by_default') and esa_get_settings('modules', 'comments', 'activate'))
                ? 'open'
                : $data['comment_status'];
    }

    return $data;
});


add_action('wp_insert_comment', function($fields) {
    if (isset($_POST['esa_return_to'])) {
        wp_redirect($_POST['esa_return_to']);
        exit;
    }
}, 1000, 10);

add_action("esa_get_module_scripts", function() {

    if (!esa_get_settings('modules', 'comments', 'activate')) {
        return;
    }
    $dev_suffix = esa_get_settings('script_suffix');

    wp_register_style('esa_item-comments', plugins_url(ESA_DIR . '/css/esa_item-comments.css'));
    wp_enqueue_style('esa_item-comments');

    wp_enqueue_script(
        'esa_item_comments.js',
        plugins_url() . ESA_DIR . '/js/esa_item_comments.js',
        array('jquery')
    );

    wp_localize_script('esa_item_comments.js', 'esaItemCommentsOptions', array(
        "tab_list_open" => esa_get_settings('modules', 'comments', 'tab_list_open'),
        "tab_form_open" => esa_get_settings('modules', 'comments', 'tab_form_open'),
    ));
    wp_add_inline_script('esa_item_comments.js', "var ajaxurl = '" . admin_url('admin-ajax.php') . "';", "before");
});

add_filter("esa_get_module_content", function($content, $esa_item) {

    $wrapper = esa_get_wrapper($esa_item);

    $comment_count = get_comments_number($wrapper->ID);
    $comment_count_s = sprintf(_n('%s Comment', '%s Comments', $comment_count), $comment_count);

    $esa_style = esa_get_settings('modules', 'comments', 'esa_style') ? 'esa' : '';

    $list_visibility = (($wrapper->comment_status !== 'open') and ($comment_count == 0)) ? 'esa-hide' : '';
    $form_visibility = ($wrapper->comment_status !== 'open') ? 'esa-hide' : '';

    ob_start();

    echo "<span class='esa-item-comments $esa_style' data-esa-item-wrapper-id='{$wrapper->ID}'>";
    echo "<div class='esa-item-comments-buttons esa-module-buttons'>";
    echo "<button class='esa-item-comments-button show-comments $list_visibility' aria-expanded='false' title='$comment_count_s' />$comment_count</button>";
    $label = __( 'Add new Comment' );
    echo "<button class='esa-item-comments-button show-form $form_visibility' aria-expanded='false' title='$label' /></button>";
    echo "</div>";

    echo "<div class='esa-separator'></div>";
    echo "<div class='esa-item-comments-list $list_visibility'><!-- filled with ajax --></div>";


    echo "<div class='esa-item-comments-form $form_visibility'>";

    $comment_form_args = array();
    $comment_form_args["must_log_in"] = false;

    global $post;
    if ($post->post_type != "esa_item_wrapper") {
        $the_permalink = get_permalink($post);
        $comment_form_args["comment_notes_after"] = "<input type='hidden' name='esa_return_to' value='$the_permalink'>";
    }

    comment_form($comment_form_args, $wrapper->ID);

    echo "</div>";

    echo "</span>";

    return $content . ob_get_clean();
}, 20, 2);

add_filter("esa_get_module_settings", function($settings) {
    $settings["comments"] = array(
        'label' => "Comments on Esa-Items",
        'info' => "More Settings on Comments see <a href='options-writing.php'>" . __('Settings') . ' > ' . __('Writing')  . "</a>",
        'children' => array(
            // is the comment feature active
            'activate' => array(
                'default' => true,
                'label' => "Activate Feature",
                'type' => 'checkbox'
            ),
            'open_by_default' => array(
                'default' => true,
                'type' => 'checkbox',
                'label' => "Allow commenting on all Esa-Items, unaware of their individual comment-status"
            ),
            'tab_list_open' => array(
                'default' => true,
                'type' => 'checkbox',
                'label' => "open Comment-List for each Esa-Item on page load"
            ),
            'tab_form_open' => array(
                'default' => true,
                'type' => 'checkbox',
                'label' => "open Comment-Form  for each Esa-Item on page load"
            ),
            'esa_style' => array(
                'default' => true,
                'type' => 'checkbox',
                'label' => "Add Plugins Own stylesheets to the Wordpress' themes stylesheet on comments"
            )
        )
    );
    return $settings;
});


function esa_comment_list() {
    if (!isset($_POST['esa_item_wrapper_id'])) {
        wp_die("wrapper id missing");
    }
    $wrapper = get_post($_POST['esa_item_wrapper_id']);
    if (!$wrapper or $wrapper->post_type != "esa_item_wrapper") {
        wp_die("wrapper not found");
    }
    $page = isset($_POST['page']) ? (int) $_POST['page'] : 0;
    $comment_count = get_comments_number($wrapper->ID);
    $pages = (int) ($comment_count / 5);
    $pages -= ($comment_count % 5 == 0) ? 1 : 0;
    $comments = get_comments(array(
        'post_id' =>  $wrapper->ID,
        'status' => "approve",
        'number' => 5,
        'offset' => $page * 5
    ));
    echo "<ol class=\"commentlist\">";
    echo wp_list_comments(array(
        'avatar_size' => 16
    ), $comments);
    echo "</ol>";

    if ($comment_count > 5) {
        echo "<table class='esa-item-comment-nav'><tr><td>";
        if ($page > 0) {
            $next = $page - 1;
            $label = __('Previous page');
            echo "<button class='esa-item-comments-button change-page' data-esa-comment-page='$next' title='$label'>$label</button>";
        }
        $label = __('Page');
        echo "</td><td><span>$label: " . ($page + 1) . " / " . ($pages + 1) . "</span></td><td>";
        if ($page < $pages) {
            $prev = $page + 1;
            $label = __('Next page');
            echo "<button class='esa-item-comments-button change-page' data-esa-comment-page='$prev' title='$label'>$label</button>";
        }
        echo "</td></tr></table>";
    }

    if ($comment_count == 0) {
        echo "<p>" . __( 'No comments found.' ) . "</p>";
    }

    wp_die();
}

add_action("esa_get_module_store_shortcode", function($post, $attrs) {
    $item = new esa_item($attrs['source'], $attrs['id']);
    $item->html(true);
    esa_get_wrapper($item);
}, 10, 2);