<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

	<?php if (et_get_option('flexible_integration_single_top') <> '' && et_get_option('flexible_integrate_singletop_enable') == 'on') echo (et_get_option('flexible_integration_single_top')); ?>
	
	<article id="post-<?php the_ID(); ?>" <?php post_class('entry clearfix'); ?>>
		<h2 class="page_title"><?php the_title(); ?></h2>

		<p class="et_pt_blogmeta">
			<?php esc_html_e('Posted','Flexible'); ?>
			<?php esc_html_e('by','Flexible'); ?>
			<?php /*the_author_posts_link();*/ the_author(); ?>
			<?php esc_html_e('on','Flexible'); ?>
			<?php the_time(et_get_option('flexible_date_format')) ?>
			<?php esc_html_e('in','Flexible'); ?>
			<?php 
				$the_taxonomys = get_the_taxonomies(get_the_ID(), array('template' => "<span class='tax-%s'>%l</span>"));
				echo $the_taxonomys['story_keyword'];
			?>
			<?php /*comments_popup_link(esc_html__('0 comments','Flexible'), esc_html__('1 comment','Flexible'), '% '.esc_html__('comments','Flexible'));*/ ?>
		</p>

		<?php /*
			$index_postinfo = et_get_option('flexible_postinfo2');
			if ( $index_postinfo ){
				echo '<p class="meta-info">';
				et_postinfo_meta( '', et_get_option('flexible_date_format'), esc_html__('0 comments','Flexible'), esc_html__('1 comment','Flexible'), '% ' . esc_html__('comments','Flexible') );
				echo '</p>';
				echo "d<pre>"; the_tags(); echo "</pre>";
				echo "e<pre>"; the_category(); echo "</pre>";
			}*/
		?>
		<p class="sharelink"><a class="addthis_button_compact"><span>Share &raquo;</span></a></p>

		<?php
			$thumb = '';
			$width = apply_filters('et_blog_image_width',640);
			$height = apply_filters('et_blog_image_height',320);
			$classtext = '';
			$titletext = get_the_title();
			$thumbnail = get_thumbnail($width,$height,$classtext,$titletext,$titletext,false,'Blogimage');
			$thumb = $thumbnail["thumb"];
		?>
		<?php if ( has_post_thumbnail() && '' != $thumb && 'on' == et_get_option('flexible_thumbnails') ) { ?>
			<div class="post-thumbnail">
				<?php print_thumbnail($thumb, $thumbnail["use_timthumb"], $titletext, $width, $height, $classtext); ?>	
			</div> 	<!-- end .post-thumbnail -->
		<?php } ?>
		
		<div class="post-content">
			
			<?php the_content(); ?>
			<?php wp_link_pages(array('before' => '<p><strong>'.esc_attr__('Pages','Flexible').':</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
			
		</div>
		<div style='clear:both'></div>
		<?php edit_post_link(esc_attr__('Edit this page','Flexible')); ?>
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
