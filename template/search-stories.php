<?php
/*
  Template Name: Search Stories
 */
?>


<?php 
$et_ptemplate_settings = array();
$et_ptemplate_settings = maybe_unserialize( 
// get_post_meta($post->ID,'et_ptemplate_settings',true) );
get_metadata('story', $post->ID,'et_ptemplate_settings',true) );

$fullwidth = isset( $et_ptemplate_settings['et_fullwidthpage'] ) ? (bool) $et_ptemplate_settings['et_fullwidthpage'] : false;

$et_ptemplate_blogstyle = isset( $et_ptemplate_settings['et_ptemplate_blogstyle'] ) ? (bool) $et_ptemplate_settings['et_ptemplate_blogstyle'] : false;

$et_ptemplate_showthumb = isset( $et_ptemplate_settings['et_ptemplate_showthumb'] ) ? (bool) $et_ptemplate_settings['et_ptemplate_showthumb'] : false;

$blog_cats = isset( $et_ptemplate_settings['et_ptemplate_blogcats'] ) ? (array) $et_ptemplate_settings['et_ptemplate_blogcats'] : array();
//$et_ptemplate_blog_perpage = isset( $et_ptemplate_settings['et_ptemplate_blog_perpage'] ) ? (int) $et_ptemplate_settings['et_ptemplate_blog_perpage'] : 10;
$et_ptemplate_blog_perpage = 2;
?>

<?php get_header(); ?>

<?php include ('breadcrumbs-stories.php'); ?>

<div id="content-area" class="clearfix<?php if ( $fullwidth ) echo ' fullwidth'; ?>">
	<div id="left-area">
	    <h1 class="page_title"><?php echo ($q = get_search_query()) ? "SEARCH RESULTS FOR '$q'" :  "SEARCH RESULTS"; ?></h1>
		<article id="post-<?php the_ID(); ?>" <?php post_class('entry clearfix'); ?>>
			

			<?php
				$thumb = '';
				$width = apply_filters('et_blog_image_width',640);
				$height = apply_filters('et_blog_image_height',320);
				$classtext = '';
				$titletext = get_the_title();
				$thumbnail = get_thumbnail($width,$height,$classtext,$titletext,$titletext,false,'Blogimage');
				$thumb = $thumbnail["thumb"];
			?>
			<?php if ( '' != $thumb && 'on' == et_get_option('flexible_page_thumbnails') ) { ?>
				<div class="post-thumbnail">
					<?php print_thumbnail($thumb, $thumbnail["use_timthumb"], $titletext, $width, $height, $classtext); ?>	
				</div> 	<!-- end .post-thumbnail -->
			<?php } ?>
			
			<div class="post-content">
				<?php the_content(); ?>
				
				<div id="et_pt_blog" class="responsive">
					<?php 
						$cat_query = ''; 
						if (!empty($blog_cats)) {
							$cat_query = '&cat=' . implode(",", $blog_cats);
						} else {
							echo '<!-- blog category is not selected -->';
							$et_paged = is_front_page() ? get_query_var( 'page' ) : get_query_var( 'paged' );
						}
					
						if (have_posts()) {
							while (have_posts()) {
								the_post(); 
								include('loop-story.php');
							}
					 	
							echo '<div class="page-nav clearfix">';
							if(function_exists('wp_pagenavi')) {
								echo wp_pagenavi(); 
							} else { 
								get_template_part('includes/navigation');
							}
							echo "</div>";
						} else {
							get_template_part('includes/no-results');
						}
						wp_reset_query();
					?>
				</div> <!-- end #et_pt_blog -->
				
				<?php wp_link_pages(array('before' => '<p><strong>'.esc_attr__('Pages','Flexible').':</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
			</div> 	<!-- end .post-content -->
		</article> <!-- end .entry -->
	</div> <!-- end #left_area -->

	<?php if ( ! $fullwidth ) include ('sidebar-stories.php'); ?>
</div> 	<!-- end #content-area -->

<?php get_footer(); ?>

