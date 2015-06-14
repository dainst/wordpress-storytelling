<?php
/*
  Template Name: Create Story
 */
?>
<?php get_header(); ?>

<?php get_template_part('includes/breadcrumbs', 'page'); ?>

<div id="content-area" class="clearfix">
    <h1 class="page_title">CREATE STORY</h1>
    <div onclick="Search.sideBarToggle();" id="slideInBarBtn" class="left"></div>
    <?php
    get_sidebar('archives');
    ?>
    <div id="left-area">
        <div class="post-content">
            <div class="mysearchdiv">
                <?php echo do_shortcode('[esi_shortcode page_name="archives"]') ?>
            </div>
        </div>
    </div> <!-- end #left_area -->
</div> 	<!-- end #content-area -->

<?php get_footer(); ?>