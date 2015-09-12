<?php get_header(); ?>

<!-- ?php get_template_part('includes/breadcrumbs', 'index'); ? -->
<?php include ('breadcrumbs-stories.php'); ?>


<div id="content-area" class="clearfix">
	<div id="left-area">
		<?php include ('loop-single-story.php'); ?>
	</div> <!-- end #left_area -->

	<?php include ('sidebar-stories.php'); ?>
</div> 	<!-- end #content-area -->
	

	
<?php get_footer(); ?>