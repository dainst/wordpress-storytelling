<div class="et_pt_blogentry clearfix">
	
	
	<div class="story-thumbnail story-thumbnail-list">
		<?php if ($thumplnail_url = get_post_meta(get_the_ID(), 'esa_thumbnail', true)) { ?>
			<img src="<?php echo $thumplnail_url; ?>" alt='<?php the_title; ?>' />
		<?php } ?>
	</div>
	
	<a href="<?php the_permalink(); ?>" class="readmore"><span><?php esc_html_e('read Story','Flexible'); ?> &raquo;</span></a>
	
	<h2 class="et_pt_title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?><?php //the_ID(); ?></a></h2>
	
	<p class="et_pt_blogmeta">
		<?php esc_html_e('Posted','Flexible'); ?> 
		<?php esc_html_e('by','Flexible'); ?> 
		<?php /*the_author_posts_link();*/ the_author(); ?> 
		<?php esc_html_e('on','Flexible'); ?> 
		<?php the_time(et_get_option('flexible_date_format')) ?>
		<?php 
			$the_taxonomys = get_the_taxonomies($get_the_ID, array('template' => "<span class='tax-%s'>%l</span>"));
			echo (count($the_taxonomys['story_keyword'])) ? '<br> Keywords: ' . $the_taxonomys['story_keyword'] : '';
		?>				
	</p>

	
	<p><?php the_excerpt();?></p>
	

</div> <!-- end .et_pt_blogentry -->