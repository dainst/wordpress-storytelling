<?php
/**
 * @package eagle-storytelling
 * @version 1.0 BETA Tester Version
 */
/*
Plugin Name: Eagle Storytelling Application
Plugin URI:  http://www.eagle-network.eu/stories/
Description: Create your own EAGLE story! 
Author:	     Philipp Franck
Author URI:	 http://www.dainst.org/
Version:     1.0 BETA Tester Version
*/
/*

Copyright (C) 2015  Deutsches Archäologisches Institut

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/


// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	die();
}

/**
 * Settings
 */
define('ESA_DEBUG', false);

$esa_settings = array(
	'post_types' => array('post', 'page')
);

require_once('esa_datasource.class.php');
require_once('esa_item.class.php');

/**
 * Installation
 */
function esa_install () {

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
		timestamp DATETIME NOT NULL,
		PRIMARY KEY  (source, id)
	)
	COLLATE utf8_general_ci
	ENGINE = MYISAM
	;";

	dbDelta( $sql );

	// because esa_item has wo columns as index, we can't solve this with a taxonomy...
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

	dbDelta( $sql );

	
	
}
register_activation_hook( __FILE__, 'esa_install');



/**
 * Settings page
 */

add_action('admin_menu', function () {
	
	
	//create new top-level menu
	add_menu_page('Eagle Storytelling Application', 'Eagle Storytelling Application', 'administrator', __FILE__, function() {
		
		global $esa_settings;
		$url = admin_url('admin.php');
		
		$datasources = json_decode(get_option('esa_datasources'));
		if (!is_array($datasources)) {
			$datasources  = array();
		}
		
		//update_option('esa_datasources') = json_encode($list);
		
		$dsfiles = glob(dirname(__file__) . "/datasources/*.class.php");
		
		echo "<div class='wrap'><h2>Eagle Storytelling Application</h2>";
		
		
		echo "<h3>Settings</h3>";
	
		echo "<form method='POST' action='$url'>";
		echo "<h4>Available Data Sources</h4>";
		$labels = array();
		foreach ($dsfiles as $filename) {
			$name = basename($filename, '.class.php');
			$ds = get_esa_datasource($name);
			$label = $ds->title;
			$labels[$name] = $label;
			$is_ok = $ds->dependancy_check();
			$error = ($is_ok === true) ? "<span style='color:green'>O.K.</span>" : "<span style='color:red'>Error: $is_ok</span>";
			$checked = ((in_array($name, $datasources)) and ($is_ok === true)) ?  'checked="checked"' : '';
			$disabled = ($is_ok === true) ? '' : 'disabled="disabled"';
			echo "<div><input type='checkbox' name='esa_datasources[]' value='$name' id='esa_activate_datasource_$name' $checked $disabled /><label for='esa_activate_datasource_$name'>$label [$error]</label></div>";
		}
		update_option('esa_datasource_labels', json_encode($labels));
		wp_nonce_field('esa_save_settings', 'esa_save_settings_nonce');
		echo "<input type='hidden' name='action' value='esa_save_settings'>";
		echo "<input type='submit' value='Save' class='button button-primary'>";
		echo "</form>";
		
		
		
		echo "<h3>Cache</h3>";
		echo "<form method='POST' action='$url'>";
    	echo "<input type='hidden' name='action' value='esa_flush_cache'>";
    	echo "<input type='submit' value='Flush esa_item cache!' class='button'>";
		echo "</form>";
		
		echo "</div>";
	});

});

add_action('admin_action_esa_flush_cache', function() {
	global $wpdb;
	
	$sql = "truncate {$wpdb->prefix}esa_item_cache;";
	
	$wpdb->query($sql);
	
    wp_redirect($_SERVER['HTTP_REFERER']);
    exit();
	
});


add_action('admin_action_esa_save_settings' ,function() {
	if (!check_admin_referer('esa_save_settings', 'esa_save_settings_nonce')) {
	   echo "Nonce failed";
	}
	
	print_r($_POST);
	if (isset($_POST['esa_datasources'])) {
		update_option('esa_datasources', json_encode(array_map('sanitize_text_field', $_POST['esa_datasources'])));
	}		
	wp_redirect($_SERVER['HTTP_REFERER']);
    exit();
});

add_action('save_post', function($post_id) {
		
	$post = get_post($post_id);
	global $wpdb;

	if (!wp_is_post_revision($post_id) and is_esa($post->post_type)) { 
		
		$regex = get_shortcode_regex();
		preg_match_all("#$regex#s", $post->post_content, $shortcodes, PREG_SET_ORDER);

		echo "<pre>", print_r($shortcodes,1), "</pre>";
		
		if ($shortcodes) {
			
			$sql = "delete from wp_esa_item_to_post where post_id='$post_id'";
			$wpdb->query($sql);
			
			foreach($shortcodes as $shortcode) {
				if ($shortcode[2] == 'esa') {
					$atts = shortcode_parse_atts($shortcode[3]);
					echo "<pre>", print_r($atts,1), "</pre>";
					
					$wpdb->insert(
							$wpdb->prefix . 'esa_item_to_post',
							array(
									"post_id" => $post_id,
									"esa_item_source" => $atts['source'],
									"esa_item_id" => $atts['id']
							)
					);
					
					
				}
			}
		}
	}		
});


/**
 * Register style sheets and javascript
 */

add_action('wp_enqueue_scripts', function() {
	global $post;
	
	if (is_esa($post->post_type)) {

		// css
		wp_register_style('eagle-storytelling', plugins_url('eagle-storytelling/css/eagle-storytelling.css'));
		wp_enqueue_style('eagle-storytelling' );
		wp_register_style('esa_item', plugins_url('eagle-storytelling/css/esa_item.css'));
		wp_enqueue_style('esa_item');
		wp_register_style('leaflet', 'http://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.3/leaflet.css');
		wp_enqueue_style('leaflet');
		
		//js
		wp_enqueue_script('esa_item.js', plugins_url() .'/eagle-storytelling/js/esa_item.js', array('jquery'));
	}
});



/**
 * 
 * Make search able to search inside of esa_item_cache to find Entries by it's content in esa item. 
 * 
 * 
 */

add_filter('query_vars', function($public_query_vars) {
	$public_query_vars[] = 'esa_item_source';
	$public_query_vars[] = 'esa_item_id';
	return $public_query_vars;
});

add_filter('posts_search', function($sql) {
	
	global $wp_query;
	global $wpdb;
	
	$sqst = "select
				esai2post.post_id
			from
				{$wpdb->prefix}esa_item_to_post as esai2post
			left join {$wpdb->prefix}esa_item_cache as esai on (esai.id = esai2post.esa_item_id and esai.source = esai2post.esa_item_source)
				where";
	
	if (isset($wp_query->query['s']) and ($wp_query->query['s'] != '')) {
		$where = "\n\t esai.searchindex like '%{$wp_query->query['s']}%'";
		$sqlr = "AND (({$wpdb->prefix}posts.ID in ($sqst $where)) or (1 = 1 $sql))";
	}
	if (isset($wp_query->query['esa_item_source']) and isset($wp_query->query['esa_item_id'])
		and $wp_query->query['esa_item_source'] and $wp_query->query['esa_item_id']) {
		$story = true;
		$where = "esai.id = '{$wp_query->query['esa_item_id']}' and esai.source = '{$wp_query->query['esa_item_source']}'";
		$sqlr = "AND {$wpdb->prefix}posts.ID in ($sqst $where)";
	}
	
	
	//echo "<pre>"; print_r($wp_query->query); die($sql);
	
	if (($wp_query->query['post_type'] != 'story') or (!$sql and !$story)) {
		return $sql;
	}
	
	//echo '<pre style="border:1px solid red; background: silver">', $sql, '</pre>';				
	//echo '<pre style="border:1px solid red; background: silver">', print_r($sqlr, 1), '</pre>';
	
	return $sqlr;
	
});


/* 
// view Query
add_action('found_posts', function() {
	global $wp_query;
	if (is_search()) {
		echo '<pre style="border:1px solid red; background: silver">', print_r($wp_query->request, 1), '</pre>';
	}
});*/



/** 
 * add media submenu!
 */

// add them to media menu

add_filter('media_upload_tabs', function($tabs) {
	global $post;
	return (!is_object($post) or is_esa($post->post_type)) ?
    	array_merge($tabs, array('esa' => 'EAGLE Storytelling Application')) :
		$tabs;
});


// create submenu

add_action('media_upload_esa', function() {
	
	add_action('admin_print_styles-media-upload-popup', function() {
		wp_enqueue_style('colors');
		wp_enqueue_style('media');
		wp_enqueue_style('media-views');
		wp_enqueue_style('esa_item', plugins_url() .'/eagle-storytelling/css/esa_item.css');
		wp_enqueue_style('esa_item-admin', plugins_url() .'/eagle-storytelling/css/esa_item-admin.css');
		wp_enqueue_style('leaflet', 'http://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.3/leaflet.css');
	});
	
	
	add_action('admin_print_scripts-media-upload-popup', function() {
		wp_enqueue_script('jquery');
		wp_enqueue_script('esa_item.js', plugins_url() .'/eagle-storytelling/js/esa_item.js', array('jquery'));
		wp_enqueue_script('esa_mediamenu.js', plugins_url() .'/eagle-storytelling/js/esa_mediamenu.js', array('jquery'));
	});
	
	/**
	 * this builds the iframe wich is in fact the add media dialogue of our plugin!
	 *
	 */
	return wp_iframe('media_esa_dialogue'); 
});


function media_esa_dialogue() {
	
	$time = microtime(true);
	
	global $esa_settings;
	$esa_datasources = json_decode(get_option('esa_datasources'));
	$labels = (array) json_decode(get_option('esa_datasource_labels'));
	// get current search engine
	$eds = get_esa_datasource(isset($_GET['esa_source']) ? $_GET['esa_source'] : $esa_datasources[0]);
	
	$post_id = isset($_REQUEST['post_id']) ? intval($_REQUEST['post_id']) : 0;
	
	
	//media_upload_header();
	
	echo "<div id='esa-mediaframe'>";
	
	echo "<div class='media-frame-router'>";
	echo "<div class='media-router'>";
	
	// create search engine menu
	foreach ($esa_datasources as $source) {
		$sel = ($source == $engine) ? 'active' : '';
		$label = $labels[$source];
		echo "<a class='media-menu-item $sel' href='?tab=esa&esa_source=$source'>$label</a>";
	}
	echo "</div>";
	echo "</div>"; //esa_item_list_sidebar
	
	
	//Sidebar
	echo "<div id='esa_item_list_sidebar'>";
	echo "<div id='esa_item_preview' class='esa_item esa_item_$engine'></div>";
	
	echo '<div id="esa_item_settings"><form>';
	echo '<p>Some <strong>optional</strong> parameters to define <br />the looks of your Item. Leave out for <a title="reset to default settings" href="#" onclick="esa_ds.reset_form()"> default</a>.</p>';
	
	echo '<div class="esa_item_setting">';
	echo '<label for="height">' . __('Height') . '</label>';
	echo '<input type="number" min="0" name="height" value="">';
	echo "</div>";
	
	echo '<div class="esa_item_setting">';
	echo '<label for="width">' . __('Width') . '</label>';
	echo '<input type="number" min="0" name="width" value="">';
	echo "</div>";
	
	echo '<div class="esa_item_setting">';
	echo '<label for="align">' . __('Align') . '</label>';
	echo '<select height="1" name="align">
			<option value="" selected>none</option>
			<option value="left">Left</option>
			<option value="right">Right</option>
		</select>';
	echo "</div>";
	
	if (count($eds->optional_classes)) {
		echo '<div class="esa_item_setting">';
		echo '<label for="mode">' . __('Modus') . '</label>';
		echo '<select height="1" name="mode">
				<option value="" selected>none</option>';
		foreach ($eds->optional_classes as $key => $caption) {
			echo "<option value='$key'>$caption</option>";
		}
		echo '</select>';
	
		echo "</div>";
	}
	
	echo "</form></div>";
	
	
	echo "</div>"; //esa_item_list_sidebar
	
	echo "<div class='media-frame-content'>";
	
	echo "<div class='attachments-browser'>";

	
	//echo "<h3 class='media-title'>{$eds->title}</h3>";
	$success = $eds->search();	
	$eds->search_form();
	echo '<div id="media-items">';
	if ($success) {
		$eds->show_result();
	} else {
		$eds->show_errors();
	}
	echo '</div>'; //media-items
	
	echo '</div>'; //attachments-browser
	
	echo "</div>"; //media-frame-content
	
	echo "<div class='media-frame-toolbar'>";
	echo "<div class='media-toolbar'>";
	echo '<div class="media-toolbar-primary search-form">';
	echo '<input type="button" class="button button-primary media-button" id="go_button" disabled="disabled" onclick="esa_ds.insert()" value="' . esc_attr__('Insert into Post') . '" />';
	echo "</div>"; //media-toolbar-primary search-form
	echo "</div>"; //media-toolbar
	echo "</div>"; //media-frame-toolbar

	echo "</div>"; // esa-mediaframe
	//echo "<div class='timestamp'>Time: ", microtime(true) -$time, "</div>";
}



/**
 *  the esa_item shortcode 
 *  
 *  attribute
 *  id (mandatory) - unique id of the datatset as used in the external data source 
 *  source (mandatory) - name of the datasource as used in this plugin
 *  
 *  height - height of the item in px
 *  width - width of the item in px
 *  align (left|right|none) - align of the object in relation to the surrounding text (deafult is right)
 *  
 *  bsp: 
 *  
 *  [esa source="wiki" id="berlin"] 
 *  
 */


function esa_shortcode($atts, $context) {
	
	if (!isset($atts['source']) or !isset($atts['id'])) {
		return;
	}
	
	$css = array();
	$classes = array();
	$tags = array();
	
	if (isset($atts['height'])) {
		$css['height'] = $atts['height'] . 'px';
	}
	if (isset($atts['width'])) {
		$css['width'] = $atts['width'] . 'px';
	}

	if (isset($atts['align'])) {
		if ($atts['align'] == 'right') {
			$classes[] = 'esa_item_right';
			//$css['float'] = 'left';
		} elseif ($atts['align'] == 'left') {
			$classes[] = 'esa_item_left';
			//$css['float'] = 'right';
		} else {
			$classes[] = 'esa_item_none';
			//$css['float'] = 'none';
		}
	}
	
	$item = new esa_item($atts['source'], $atts['id'], false, false, $classes, $css);

	
	ob_start();
	$item->html();
	return ob_get_clean();
	

}

add_shortcode('esa', 'esa_shortcode');



/** 
 * the caching mechanism for esa_items 
 * 
 * how it works: everytime a esa_item get displayed, it look in the cache if there is a non expired cache of this item. if not,
 * it fetches the contents from the corresponding api and caches it
 * that has two reasons: 
 * - we want to make the embedded esa_items searchable
 * - page loading would be quite slow, if every items content had to be fetched again from the api
 * 
 * how long may content eb kept in cache? that has to be diskussed.
 * 
 * 
 */






/**
 * tinyMCE with esa_objects 
 * 
 * 
 */


add_action('init', function() {
	add_filter("mce_external_plugins", function($plugin_array) {
		$plugin_array['esa_item'] = plugins_url() . '/eagle-storytelling/js/esa_mce.js';
		$plugin_array['noneditable'] = plugins_url() . '/eagle-storytelling/js/mce_noneditable.js';
		add_editor_style(plugins_url() .'/eagle-storytelling/css/esa_item.css');
		add_editor_style(plugins_url() .'/eagle-storytelling/css/esa_item-admin.css');
		return $plugin_array;
	});
	
	add_filter('mce_buttons', function($buttons) {
		array_push($buttons, 'whatever');
		return $buttons;
	});
	
});


add_action('wp_ajax_esa_shortcode', function() {
	if (isset($_POST['esa_shortcode'])) {
		
		$result = array();
		
		if (!isset($_POST['featured_image']) or ($_POST['featured_image'] == '')) {
			$post_id = $_POST['post_id'];
			$thumpnail = get_post_meta($post_id, 'esa_thumbnail', true);
			$result['featured_image'] = $thumpnail;
		}
		
		$result['esa_item'] = do_shortcode(str_replace('\\', '', rawurldecode($_POST['esa_shortcode'])));

		//$result['debug'] = $post;
		
		echo json_encode($result);
		
		wp_die();
	}
	echo "ERROR"; // todo: do something more useful
	wp_die();
});

add_action('wp_ajax_esa_set_featured_image', function() {
	
	
	if (isset($_POST['image_url']) and isset($_POST['post'])) {
		
		$image_url = $_POST['image_url'];
		$post_id = $_POST['post'];
			
		if (!add_post_meta($_POST['post'], 'esa_thumbnail', $image_url, true)) {
			update_post_meta($_POST['post'], 'esa_thumbnail', $image_url);
		}
		
		echo $image_url;
		wp_die();
	}
	echo 'ERROR'; // 
	wp_die();
});




/**
 *  thumbnailing
 */

function esa_thumpnail($post, $return = false) {
	
	
	// check if esa thumpnail exists
	if ($esa_thumbnail_url = get_post_meta($post->ID, 'esa_thumbnail', true)) {
		$thumbnail = "<img src='$esa_thumbnail_url' alt='thumbnail' />";
	}
	
	// check if regular thumpnail exists
	if (!$thumbnail) {
		$thumbnail = get_the_post_thumbnail($post->ID, array(150, 150));
	}

	if (!$return) {
		echo "<div class='story-thumbnail'>$thumbnail</div>";
	}

	return $thumbnail;
	
} 

add_filter('admin_post_thumbnail_html', function($html) {
	
	global $post;
	
	$thumbnail = '';
	$style1 = 'style="display: none;"';
	$style2 = 'style="display: block;"';
	
	// check if esa thumpnail exists
	if ($esa_thumbnail_url = get_post_meta($post->ID, 'esa_thumbnail', true)) {
		$thumbnail = "";
		$style2 = 'style="display: none;"';
		$style1 = 'style="display: block;"';
	}

	$text1 = "Featured image from embedded content. Use the <img src='' alt='img'>-buttons in the upper right corner of embedded content to select or deselect.</p>";
	$text2 = "Use this to select a featured image from the media library or to upload images. Alternatively use the <img src='' alt='img'>-buttons in the upper right corner of embedded content to set Images from there as featured image.</p>";	
	
	return "<span id='esa_thumpnail_content_1' $style1>
				<img id='esa_thumpnail_admin_picture' src='$esa_thumbnail_url' alt='{$post->post_title}' /> 
				<p>$text1</p>
			</span>
			<span id='esa_thumpnail_content_2' $style2>
				$text2 $html
			</span>";
});

/**
 * useful functions
 */
function is_esa($post_type) {
	global $is_esa_story_page;
	global $esa_settings;
	//print_r($esa_settings['post_types'] );die();
	return (in_array($post_type, $esa_settings['post_types'])) or $is_esa_story_page;
}

function get_esa_datasource($engine) {
	// get engine interface
	if (!$engine or !file_exists(plugin_dir_path(__FILE__) . "datasources/$engine.class.php")) {
		echo "Error: Search engine $engine not found!"; return;
	}
	
	require_once(plugin_dir_path(__FILE__) . "datasources/$engine.class.php");
	$ed_class = "\\esa_datasource\\$engine";
	return new $ed_class;
}

?>