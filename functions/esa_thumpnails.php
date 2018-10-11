<?php
/**
 *  ******************************************* thumbnailing
 *  esa thumbnail > regular thumbnail
 *
 */

add_action('wp_ajax_esa_set_featured_image', function() {

    if (isset($_POST['image_url']) and isset($_POST['post'])) {

        $image_url = $_POST['image_url'];
        $post_id = $_POST['post'];

        delete_post_meta($post_id, 'esa_thumbnail');

        if (($image_url != '%none%') and !add_post_meta($post_id, 'esa_thumbnail', $image_url, true)) {
            update_post_meta($post_id, 'esa_thumbnail', $image_url);
        }

        wp_send_json_success(array('image_url' => $image_url));

    }

    wp_send_json_error(array(
        'type' => 'esa',
        'message' => 'set featured image error',
        'post' => $_POST
    ));
});


function esa_thumbnail($post, $return = false) {

    // check if esa thumbnail exists
    if ($esa_thumbnail_url = get_post_meta($post->ID, 'esa_thumbnail', true)) {
        $thumbnail = "<img src='$esa_thumbnail_url' alt='thumbnail' />";
    }

    // check if regular thumbnail exists
    if (empty($thumbnail)) {
        $thumbnail = get_the_post_thumbnail($post->ID, array(150, 150));
    }

    if (!$return) {
        echo "<div class='story-thumbnail'>$thumbnail</div>";
    }

    return $thumbnail;

}

add_filter('admin_post_thumbnail_html', function($html) {

    global $post;


    // check if esa or regular thumbnail exists
    $esa_thumbnail_url = get_post_meta($post->ID, 'esa_thumbnail', true);
    $class = $esa_thumbnail_url ? 'hasEsathumbnail' : '';

    $reg_thumbnail = get_the_post_thumbnail($post->ID);
    $class .= $reg_thumbnail ? 'hasthumbnail ' : '';


    return "<div id='esa_thumbnail_chooser' class='$class'>
                <span id='esa_thumbnail_current_esa'>
                    <img id='esa_thumbnail_admin_picture' src='$esa_thumbnail_url' alt='' /></p>
                    <p><a href='#' id='esa_unfeatured_btn'>Remove featured image</a></p>
                </span>
                <span id='esa_thumbnail_set_esa' style='display: none;'>
                    <p><a href='#' id='esa_featured_btn'>Use selected item as featured Image</a></p>
                </span>
                
                <span id='esa_thumbnail_set_other'>
                    <p>Select thumbanil from harddrive or media library:</p>
                    $html
                </span>
            </div>
";
});