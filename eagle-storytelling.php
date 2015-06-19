<?php
/**
 * @package eagle-storytelling
 * @version 0.6
 */
/*
Plugin Name: Eagle Storytelling Application
Plugin URI:  http://wordpress.org/plugins/eagle-storytelling/
Description: Create your own EAGLE story! 
Author:	     Wolfgang Schmidle & Philipp Franck
Author URI:	 http://www.dainst.org/
Version:     0.6

*/


/****************************************/

function esa_create_keywords_widget() {

    $labels = array(
        'name' => _x('Keywords', 'Keywords', 'Flexible'),
        'singular_name' => _x('Keyword', 'taxonomy singular name', 'Flexible'),
        'search_items' => __('Search Keywords', 'Flexible'),
        'all_items' => __('All Keywords', 'Flexible'),
        'parent_item' => __('Parent Keyword', 'Flexible'),
        'parent_item_colon' => __('Parent Keyword:', 'Flexible'),
        'edit_item' => __('Edit Keyword', 'Flexible'),
        'update_item' => __('Update Keyword', 'Flexible'),
        'add_new_item' => __('Add New Keyword', 'Flexible'),
        'new_item_name' => __('New Keyword Name', 'Flexible'),
        'menu_name' => __('Keyword', 'Flexible')
    );

    register_taxonomy('story_keyword', array('story'), array(
        'hierarchical' => false,
        'labels' => $labels,
        'show_ui' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'keyword')
    ));
}

add_action('init', 'esa_create_keywords_widget', 0);


/****************************************/
/*
function esa_create_portfolio_taxonomies() {

    $labels = array(
        'name' => _x('Categories', 'taxonomy general name', 'Flexible'),
        'singular_name' => _x('Category', 'taxonomy singular name', 'Flexible'),
        'search_items' => __('Search Categories', 'Flexible'),
        'all_items' => __('All Categories', 'Flexible'),
        'parent_item' => __('Parent Category', 'Flexible'),
        'parent_item_colon' => __('Parent Category:', 'Flexible'),
        'edit_item' => __('Edit Category', 'Flexible'),
        'update_item' => __('Update Category', 'Flexible'),
        'add_new_item' => __('Add New Category', 'Flexible'),
        'new_item_name' => __('New Category Name', 'Flexible'),
        'menu_name' => __('Category', 'Flexible')
    );

    register_taxonomy('story_category', array('story'), array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'query_var' => true,
        'rewrite' => apply_filters('et_portfolio_category_rewrite_args', array('slug' => 'story_category'))
    ));
}

add_action('init', 'esa_create_portfolio_taxonomies', 0);
*/

/****************************************/
/* create editor page for stories */ 

function esa_register_story_post_type() {

    $labels = array(
        'name' => _x('Stories', 'post type general name', 'Flexible'),
        'singular_name' => _x('Story', 'post type singular name', 'Flexible'),
        'add_new' => _x('Add New', 'story item', 'Flexible'),
        'add_new_item' => __('Add New Story', 'Flexible'),
        'edit_item' => __('Edit Story', 'Flexible'),
        'new_item' => __('New Story', 'Flexible'),
        'all_items' => __('All Stories', 'Flexible'),
        'view_item' => __('View Story', 'Flexible'),
        'search_items' => __('Search Stories', 'Flexible'),
        'not_found' => __('Nothing found', 'Flexible'),
        'not_found_in_trash' => __('Nothing found in Trash', 'Flexible'),
        'parent_item_colon' => ''
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'query_var' => true,
        'rewrite' => apply_filters('et_portfolio_posttype_rewrite_args', array('slug' => 'story', 'with_front' => false)),
        'capability_type' => 'post',
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'comments', 'revisions', 'custom-fields')
    );

    register_post_type('story', $args);
}

add_action('init', 'esa_register_story_post_type' );


/****************************************/
/* register template for the story pages */ 

function esa_get_story_post_type_template($single_template) {
     global $post;

     if ($post->post_type == 'story') {
          $single_template = dirname( __FILE__ ) . '/template/single-story.php';
     }
     return $single_template;
}

add_filter( 'single_template', 'esa_get_story_post_type_template' );


/****************************************/
/* register template for page "stories" */ 

function esa_get_stories_page_template( $page_template )
{
    
	if ( is_page( 'stories' ) ) {
        $page_template = dirname( __FILE__ ) . '/template/page-stories.php';
    }
    return $page_template;
}

add_filter( 'page_template', 'esa_get_stories_page_template' );


/****************************************/
/* register template for page "search stories" */ 

function esa_get_search_stories_page_template( $page_template )
{
    /*if ( is_page( 'search-stories' ) ) {
        $page_template = dirname( __FILE__ ) . '/template/search-stories.php';
    }*/
	
   	if ( get_query_var('post_type') == "story" ) {
        $page_template = dirname( __FILE__ ) . '/template/search-stories.php';
        
      //  echo "<div style='background:yellow'>";       	echo "!!!!";        echo "</div>";
        
    }
    return $page_template;
}

add_filter( 'search_template', 'esa_get_search_stories_page_template' );
add_filter( 'archive_template', 'esa_get_search_stories_page_template' );
add_filter( '404_template', 'esa_get_search_stories_page_template' );



function searchfilter($query) {
    if ($query->is_search && $query->post_type == "story") {
        $query->set('meta_key','_wp_page_template');
        $query->set('meta_value', dirname( __FILE__ ) . '/template/search-stories.php');    
   	}
	return $query;
}

//add_filter('pre_get_posts','searchfilter');


/****************************************/
/* register template for page "create story" (which exists but is not yet used!) */ 

function esa_get_create_story_page_template( $page_template )
{
    if ( is_page( 'create-story' ) ) {
        $page_template = dirname( __FILE__ ) . '/template/page-create-story.php';
    }
    return $page_template;
}

add_filter( 'page_template', 'esa_get_create_story_page_template' );


/****************************************/


/**
 * Register style sheet.
 */

function esa_register_plugin_styles() {
	wp_register_style( 'eagle-storytelling', plugins_url( 'eagle-storytelling/eagle-storytelling.css' ) );
	wp_enqueue_style( 'eagle-storytelling' );
}

add_action( 'wp_enqueue_scripts', 'esa_register_plugin_styles' );


/****************************************/

/**
 * provisional solution for serach for TRISMEGISTOS-ID
 * 
 * @param WP_Query $query
 */

function trismegistos_filter( $query ) {

	if( $query->is_main_query() ) {
		if(isset($_GET['trismegistos'])) {
			//echo "<div style='background:yellow'>";       	echo "!!!!";        echo "</div>";
			$query->set('s', "tm:{$_GET['s']}"); 
		}
	}
	
}
add_action( 'pre_get_posts', 'trismegistos_filter' );


/****************************************/


/** 
 * add media submenu!
 */


// list of available data sources (must correspondent with files in /datasources)
$esa_datasources = array(
		'eagle' 		=> __('search EAGLE inscriptions'),
		'europeana' 	=> __('search in Europeana'),
		'wiki' 			=> __('TEST Data-Engine: Wikipedia')
);

require_once('esa_datasource.class.php');
require_once('esa_item.class.php');


// add them to media menu


add_filter('media_upload_tabs', function($tabs) {
    return array_merge($tabs, array('esa' => 'EAGLE Storytelling Application'));
});


// create submenu

add_action('media_upload_esa', function() {
	
	
	add_action('admin_print_styles-media-upload-popup', function() {
		wp_enqueue_style('colors');
		wp_enqueue_style('media');
		wp_enqueue_style('esa_mediamenu', plugins_url() .'/eagle-storytelling/eagle-storytelling.css');
	});
	
	
	add_action('admin_print_scripts-media-upload-popup', function() {
		wp_enqueue_script('jquery');
		wp_enqueue_script('esa_mediamenu.js', plugins_url() .'/eagle-storytelling/esa_mediamenu.js', array('jquery'));
	});
	
		
	return wp_iframe('media_esa_dialogue'); 
});

/**
 * this builds the iframe wich is in fact the add media dialogue of our plugin!
 * 
 */
function media_esa_dialogue() {
	
	global $esa_datasources;
	
	//media_upload_header();
	
	// get current search engine
	$engine = isset($_GET['esa_source']) ? $_GET['esa_source'] : 'europeana';
	
	//preview field
	echo "<div id='esa_item_list_sidebar'>";
	echo "<div id='esa_item_preview' class='esa_item esa_item_$engine'></div>";
	echo '<input type="button" class="button button-primary" id="go_button" onclick="esa_ds.insert()" value="' . esc_attr__('Insert into Post') . '" />';
	echo "</div>";
	echo "<div id='esa_item_list_main'>";

	// create search engine menu
	foreach ($esa_datasources as $source => $label) {
		$sel = ($source == $engine) ? 'button-primary' : 'button-secondary';
		echo "<a class='button $sel' href='?tab=esa&esa_source=$source'>$label</a>";
	}

	
	
	
	
	//echo "<div id='esa_media_frame_body'>";

	// get engine interface		
	if (!$engine or !file_exists(plugin_dir_path(__FILE__) . "datasources/$engine.class.php")) {
		echo "Error: Search engine $engine not found!"; return;
	}
	
	require_once(plugin_dir_path(__FILE__) . "datasources/$engine.class.php");
	$ed_class = "\\esa_datasource\\$engine";
	$eds = new $ed_class;

	

	
	$post_id = isset( $_REQUEST['post_id'] ) ? intval( $_REQUEST['post_id'] ) : 0;
	
	
	
	if (get_user_setting('uploader')) { // todo: user-rights
		$form_class .= ' html-uploader';
	}
	

	echo "<h3 class='media-title'>{$eds->title}</h3>";
	echo '<div id="media-items">';
	// serach engine & results
	$eds->search_form();
	if ($eds->search()) {
		$eds->show_result();
	} else {
		$eds->show_errors();
	}
	echo '</div>';
	
	echo "</div>";
	
	
	/*
	echo '
	<div class="media-sidebar visible">
	<h3>Some Settings</h3>
	
	
	<label class="setting" data-setting="url">
	<span class="name">URL</span>
	<input value="" readonly="" type="text">
	</label>
	</div>';
	*/

	
	
}



/**
 *  the shortcode 
 *  
 *  available codes:
 *  id
 *  source
 *  
 *  bsp: 
 *  
 *  [esa source="wiki" id="berlin"] 
 *  
 */


add_shortcode( 'esa', function ( $atts ) {
	if (!isset($atts['source']) or !isset($atts['id'])) {
		return;
	}
	
	$item = new esa_item($atts['source'], $atts['id']);
	
	
	return $item->html();
	
});


		
		




?>

































