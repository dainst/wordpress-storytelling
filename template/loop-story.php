<div class="et_pt_blogentry clearfix">
	
	
	<div class="story-thumbnail story-thumbnail-list">
		<?php if ($thumpnail_url = get_post_meta(get_the_ID(), 'esa_thumbnail', true)) { ?>
			<img src="<?php echo $thumpnail_url; ?>" alt='<?php the_title; ?>' />
		<?php } ?>
	</div>
	
	<a href="<?php the_permalink(); ?>" class="readmore"><span><?php esc_html_e('read Story','Flexible'); ?> &raquo;</span></a>
	
	<h2 class="et_pt_title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?><?php //the_ID(); ?></a></h2>
	
	<p class="et_pt_blogmeta">
		<?php 
			echo "Posted by <a href='"; bloginfo('url'); echo "?s=&post_type=story&author="; the_author_meta('ID'); echo "'>"; the_author(); echo "</a>";
			echo " on "; the_time(et_get_option('flexible_date_format'));
			$the_taxonomys = get_the_taxonomies($get_the_ID, array('template' => "<span class='tax-%s'>%l</span>"));
			echo (count($the_taxonomys['story_keyword'])) ? '<br> Keywords: ' . $the_taxonomys['story_keyword'] : '';
		?>				
	</p>

	
	<p><?php the_excerpt();?></p>
	

</div> <!-- end .et_pt_blogentry -->