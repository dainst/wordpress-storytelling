<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

	<?php if (et_get_option('flexible_integration_single_top') <> '' && et_get_option('flexible_integrate_singletop_enable') == 'on') echo (et_get_option('flexible_integration_single_top')); ?>
	
	<article id="post-<?php the_ID(); ?>" <?php post_class('entry clearfix'); ?>>

	
		<?php $thumbnail_url = esa_thumpnail(get_post());  ?>

		<h1 class="page_title"><?php the_title(); ?></h1>
	
		<?php if($thumbnail_url) { ?><div><?php } ?>
			<div class="meta-info">
				<?php 
					echo "Posted by <a href='"; bloginfo('url'); echo "?s=&post_type=story&author="; the_author_meta('ID'); echo "'>"; the_author(); echo "</a>";
					echo " on "; the_time(et_get_option('flexible_date_format'));
					echo '<br>' . esa_get_story_keywords();
				?>	
			</div>
		<?php if($thumbnail_url) { ?></div><?php } ?>
		

		<p class="sharelink"><a class="addthis_button_compact"><span>Share &raquo;</span></a></p>

		<div class="post-excerpt">
			<?php the_excerpt(); ?>
		</div>
		
		<div class="post-content">
			<?php the_content(); ?>
			<?php edit_post_link(esc_attr__('Edit this page','Flexible')); ?>
			<?php
			 wp_link_pages(array('before' => '<p><strong>'.esc_attr__('Pages','Flexible').':</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
		</div>
		<div style='clear:both'></div>
		
		 	<!-- end .post-content -->
	</article> <!-- end .entry -->
	
	<?php if (et_get_option('flexible_integration_single_bottom') <> '' && et_get_option('flexible_integrate_singlebottom_enable') == 'on') echo(et_get_option('flexible_integration_single_bottom')); ?>
		
	<?php 
		if ( et_get_option('flexible_468_enable') == 'on' ){
			if ( et_get_option('flexible_468_adsense') <> '' ) echo( et_get_option('flexible_468_adsense') );
			else { ?>
			   <a href="<?php echo esc_url(et_get_option('flexible_468_url')); ?>"><img src="<?php echo esc_url(et_get_option('flexible_468_image')); ?>" alt="468 ad" class="foursixeight" /></a>
	<?php 	}    
		}
	?>
	
	<?php 
		if ( 'on' == et_get_option('flexible_show_postcomments') ) comments_template('', true);
	?>
<?php endwhile; // end of the loop. ?>
