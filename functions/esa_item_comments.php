<?php



function esa_get_module_scripts_comments() {
    global $esa_settings;
    if (!$esa_settings['modules']['comments']['activate']) {
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
    wp_add_inline_script('esa_item_comments.js', "var ajaxurl = '" . admin_url('admin-ajax.php') . "';", "before");
}

function esa_get_module_content_comments($esa_item) {
    global $esa_settings;
    $wrapper = get_esa_item_wrapper($esa_item);

    $comment_count = get_comments_number($wrapper->ID);
    $comment_count_s = sprintf(_n('%s Comment', '%s Comments', $comment_count), $comment_count);

    $esa_style = $esa_settings['modules']['comments']['esa_style'] ? 'esa' : '';

    ob_start();

    echo "<div class='esa-item-comments $esa_style' data-esa-item-wrapper-id='{$wrapper->ID}'>";
    echo "<button class='esa-item-comments-button show-comments' aria-expanded='false' title='$comment_count_s' />$comment_count</button>";
    // if current user can
    echo "<button class='esa-item-comments-button show-form' aria-expanded='false' title='---' /></button>";

    echo "<div class='esa-item-comments-list'>";

    echo "</div>";

    echo "<div class='esa-item-comments-form'>";
    comment_form(array(
        "must_log_in" => false
    ), $wrapper->ID);
    echo "</div>";

    echo "</div>";

    return ob_get_clean();
}

function esa_get_module_settings_comments() {
    return array(
        'label' => "Comments on Esa-Items",
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
                'label' => "Comment function is open for every Esa-Item by Default"
            ),
            'esa_style' => array(
                'default' => true,
                'type' => 'checkbox',
                'label' => "Add Plugins Own stylesheets to the Wordpress' themes stylesheet on comments"
            )
        )
    );
}


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
    $comments = get_comments(array(
        'post_id' =>  $wrapper->ID,
        'status' => "approve",
        'number' => 5,
        'offset' => $page * 5
    ));
    echo "<ol class=\"commentlist\">";
    echo wp_list_comments(array(
        'avatar_size' => 16,
    ), $comments);
    echo "</ol>";

    if ($comment_count > 5) {
        echo "<table class='esa-item-comment-nav'><tr><td>";
        if ($page > 0) {
            $next = $page - 1;
            echo "<button class='esa-item-comments-button change-page' data-esa-comment-page='$next' title='---' >back</button>";
        }
        echo "</td><td><span>Page: " . ($page + 1) . " / " . ($pages + 1) . "</span></td><td>";
        if ($page < $pages) {
            $prev = $page + 1;
            echo "<button class='esa-item-comments-button change-page' data-esa-comment-page='$prev' title='---' >next</button>";
        }
        echo "</td></tr></table>";
    }

    wp_die();
}


add_filter('wp_insert_post_data', function($data) {
    global $esa_settings;
    $esmc = $esa_settings['modules']['comments'];
    if($data['post_type'] == 'esa_item_wrapper') {
        $data['comment_status'] = (($esmc['comments_open_by_default']) and ($esmc['activate'])) ? 'open' : 'close';
    }

    return $data;
});

add_action('init', function() {
    add_action('wp_ajax_esa-comment-list', 'esa_comment_list', 1);
    add_action('wp_ajax_nopriv_esa-comment-list', 'esa_comment_list', 1);
});