<?php
/**
* @package 		eagle-storytelling
* @subpackage	functions fro the eagle-sidebar
* @link 		http://www.eagle-network.eu/
* @author 		Philipp Franck
* 
* 
* Status: Currently theese function s get called manually from template.. 
* will be bundeld together in a widget.
* 
*/

/* esa users */

function esa_dropdown_users($selected) {

	// get users who has at least one story published
	global $wpdb;
	$sql = "
		select
			count(post_author) as post_count,
			display_name,
			users.ID
		from
			{$wpdb->prefix}posts as posts
			left join {$wpdb->prefix}users as users on (posts.post_author=users.ID)
		where
			post_type='story'
			and post_status='publish'
			group by
			posts.post_author
		order by
			post_count desc"; //!

	$users = $wpdb->get_results($sql);

	//print_r($sql);echo"<hr>";print_r($result);

	// some copied wp_dropdown_users

	$output = '';
	if (!empty($users)) {
	$name = 'author';
	$id = 'esa-filter-author';
	$output = "<select name='{$name}' id='{$id}'>\n";
	$output .= "\t<option value='0'>&lt;all&gt;</option>\n";

		$found_selected = false;
			foreach ((array) $users as $user) {
			$user->ID = (int) $user->ID;
				$_selected = selected($user->ID, $selected, false);
				if ($_selected) {
				$found_selected = true;
				}
				$display = "{$user->display_name} ({$user->post_count})";
				$output .= "\t<option value='$user->ID'$_selected>" . esc_html($display) . "</option>\n";
			}

			$output .= "</select>";
}

echo $output;

}


/* esa tag cloud */
function esa_keyword_cloud($args = array()) {
/**
* mostly copied from wp_generate_tag_cloud  (wp-includes/category-template.php)
	*/

	$defaults = array(
	'smallest' => 8, 'largest' => 22, 'unit' => 'pt', 'number' => 45,
	'format' => 'flat', 'separator' => "\n", 'orderby' => 'name', 'order' => 'ASC',
		'exclude' => '', 'include' => '', 'link' => 'view', 'taxonomy' => 'story_keyword', 'post_type' => 'story', 'echo' => true,
		'topic_count_text' => null, 'topic_count_text_callback' => null,
		'topic_count_scale_callback' => 'default_topic_count_scale', 'filter' => 1,
		'selected' => ''
		);
	$args = wp_parse_args( $args, $defaults );

	$tags = get_terms( $args['taxonomy'], array_merge( $args, array( 'orderby' => 'count', 'order' => 'DESC' ) ) ); // Always query top tags

	if ( empty( $tags ) || is_wp_error( $tags ) )
	return;

	// Juggle topic count tooltips:
	if ( isset( $args['topic_count_text'] ) ) {
	// First look for nooped plural support via topic_count_text.
		$translate_nooped_plural = $args['topic_count_text'];
	} elseif ( ! empty( $args['topic_count_text_callback'] ) ) {
	// Look for the alternative callback style. Ignore the previous default.
		if ( $args['topic_count_text_callback'] === 'default_topic_count_text' ) {
		$translate_nooped_plural = _n_noop( '%s topic', '%s topics' );
		} else {
		$translate_nooped_plural = false;
	}
	} elseif ( isset( $args['single_text'] ) && isset( $args['multiple_text'] ) ) {
			// If no callback exists, look for the old-style single_text and multiple_text arguments.
		$translate_nooped_plural = _n_noop( $args['single_text'], $args['multiple_text'] );
	} else {
				// This is the default for when no callback, plural, or argument is passed in.
		$translate_nooped_plural = _n_noop( '%s topic', '%s topics' );
				}

				$tags_sorted = apply_filters( 'tag_cloud_sort', $tags, $args );

	if ( $tags_sorted !== $tags ) {
				$tags = $tags_sorted;
				unset( $tags_sorted );
				} else {
				if ( 'RAND' === $args['order'] ) {
						shuffle( $tags );
				} else {
				// SQL cannot save you; this is a second (potentially different) sort on a subset of data.
					if ( 'name' === $args['orderby'] ) {
					uasort( $tags, '_wp_object_name_sort_cb' );
			} else {
							uasort( $tags, '_wp_object_count_sort_cb' );
			}

			if ( 'DESC' === $args['order'] ) {
			$tags = array_reverse( $tags, true );
							}
							}
							}

							if ( $args['number'] > 0 )
								$tags = array_slice( $tags, 0, $args['number'] );

							$counts = array();
							$real_counts = array(); // For the alt tag
							foreach ( (array) $tags as $key => $tag ) {
								$real_counts[ $key ] = $tag->count;
								$counts[ $key ] = call_user_func( $args['topic_count_scale_callback'], $tag->count );
							}

							$min_count = min( $counts );
							$spread = max( $counts ) - $min_count;
							if ( $spread <= 0 )
								$spread = 1;
							$font_spread = $args['largest'] - $args['smallest'];
							if ( $font_spread < 0 )
								$font_spread = 1;
							$font_step = $font_spread / $spread;

							// Assemble the data that will be used to generate the tag cloud markup.
							$tags_data = array();

							$tags_data[] = array(
									'id'         => 'X_x',
									'name'	     => '<all>',
									'title'      => '<all>',
									'slug'       => '',
									'real_count' => '10',
									'class'	     => 'tag-link-X_x',
									'font_size'  => ($args['smallest'] + $args['largest']) / 2
							);



							foreach ( $tags as $key => $tag ) {
								$tag_id = isset( $tag->id ) ? $tag->id : $key;

								$count = $counts[ $key ];
								$real_count = $real_counts[ $key ];

								if ( $translate_nooped_plural ) {
									$title = sprintf( translate_nooped_plural( $translate_nooped_plural, $real_count ), number_format_i18n( $real_count ) );
								} else {
									$title = call_user_func( $args['topic_count_text_callback'], $real_count, $tag, $args );
								}

								$tags_data[] = array(
										'id'         => $tag_id,
										'name'	     => $tag->name,
										'title'      => $title,
										'slug'       => $tag->slug,
										'real_count' => $real_count,
										'class'	     => 'tag-link-' . $tag_id,
										'font_size'  => $args['smallest'] + ( $count - $min_count ) * $font_step,
								);
							}


							$a = array();

							// generate the output links array
							foreach ( $tags_data as $key => $tag_data ) {
								$a[] = "<input
					type='radio'
					value='" . esc_attr( $tag_data['slug'] ) . "'
					class='" . esc_attr( $tag_data['class'] ) . "'
					id='esa_cloud_item_$key'
					name='term'" .
					(($args['selected'] == $tag_data['slug']) ? "checked='checked' " : ' ') .

					"/>
					<label
					for='esa_cloud_item_$key'
					style='font-size: " . esc_attr( str_replace( ',', '.', $tag_data['font_size'] ) . $args['unit'] ) . ";'
					title='" . esc_attr( $tag_data['title'] ) . "'
				>".	esc_html( $tag_data['name'] ) . "
				</label>";
							}

							$return = join( $args['separator'], $a );


							echo "<div id='esa-filter-keywords'>$return</div>";
				}



				function esa_get_story_keywords() {
					global $post;
					$terms = wp_get_object_terms($post->ID, 'story_keyword');
					$links = array();
					$url = get_site_url();
					foreach ( $terms as $term ) {
						$links[] = "<a href='$url/?s=&post_type=story&term={$term->slug}&taxonomy=story_keyword&author=0'>{$term->name}</a>";
					}
					if (count($links)) {
						return "Keywords: " . wp_sprintf('%l', $links);
					}
				}
?>