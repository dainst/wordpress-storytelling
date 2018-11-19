<?php
/**
 * ******************************************* useful functions (for templates)
 */

/**
 * checks whether actual selected post is of a post type which is selected for the esa functions
 * if this in templates
 *
 * @param string $post_type
 * @return boolean
 */
function is_esa($post_type = false) {
    global $is_esa_story_page; // if you want to set manually a page as esa page in template use this
    if (!$post_type) {
        global $post;
        $post_type = (is_object($post)) ? $post->post_type : '';
    }
    return (in_array($post_type, esa_get_post_types())) or $is_esa_story_page;
}




function esa_debug($whatver) {
    ob_start();
    echo "<pre>";
    var_dump($whatver);
    echo "</pre>";
    return ob_get_clean();
}