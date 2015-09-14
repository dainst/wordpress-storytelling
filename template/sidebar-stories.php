<?php if ( is_active_sidebar( 'sidebar' ) ){ ?>
	<div id="sidebar">

	<div class="topsearch">
          <h3><a href='<?php bloginfo('url'); ?>/stories'>Stories</a></h3>
          <p><a href='<?php bloginfo('url'); ?>/stories'>Flagship Storytelling Application</a></p>
    </div>
    
<?php	/*
	<div class="widget widget_search">
 
if ( is_user_logged_in() ) {
?>
		<h3><?php echo 'Hello '.wp_get_current_user()->user_login.'!' ?></h3>
		<a href="<?php echo site_url(); ?>/wp-admin/post-new.php?post_type=story">Create new story</a><br>
		<a href="<?php echo site_url(); ?>/wp-admin/edit.php?post_type=story">Edit existing stories</a><br><br>
		<a href="<?php echo wp_logout_url() ?>">Logout</a>
<?php
} else {
?>
		<h3><a href="<?php echo wp_login_url(site_url('/stories/')); ?>" title="Login">Login</a></h3>
		<p>(Logged in users can create new stories)</p>
<?php
}
	</div>*/
?>

	<div class="widget">
	

		<form role="search" method="get" class="searchform" action="<?php echo site_url(); ?>/">
			<h4 class="widgettitle">Search Stories</h4>
		
    		<div>
        		<input type="text" name="s" class="s" value="<?php echo get_query_var('s') ?>" /><input type="submit" class="searchsubmit" value="Search" />
				<input type="hidden" name="post_type" value="story" />
    		</div>


			<h4 class="widgettitle">Filters</h4>
			<h5>Filter by keywords</h5>
    		<div>
        		<input type="text"   name="term" value="<?php echo (get_query_var('taxonomy') == 'story_keyword') ? get_query_var('term') : '' ?>" class="s" /><input type="submit" class="searchsubmit" value="Search" />
        		<input type="hidden" name="taxonomy" value="story_keyword" />
    		</div>

			<h5>Filter by author</h5>
    		<div>
    			<?php wp_dropdown_users(array('id' => 'story-author-dropdown', 'name' => 'author', 'show_option_all' => '<all>', 'selected' => (int) $_GET['author'])); ?>
        		<?php // <input type="text" name="author_name" value="<?php echo get_query_var('author_name') ? >" class="s" />?>
				<input type="submit" class="searchsubmit" value="Search" /> 
    		</div>

			<h5>Filter by Europeana ID</h5>
    		<div>
        		<input type="hidden" name="esa_item_source" 	value="europeana" />
        		<input type="text" 	 name="esa_item_id" 		value="<?php echo ($_GET['esa_item_source']  == 'europeana') ? $_GET['esa_item_id'] : '' ?>" class="s" /><input type="submit" class="searchsubmit" value="Search" />
    		</div>
		</form>
	</div>
	
	
	
	<div id="recent-stories" class="widget widget_recent_entries">
		<h4 class="widgettitle">Latest Stories</h4>
		<ul>
<?php
    $args = array(
        'post_type' => 'story',
        'showposts' => 10
    );
    $latest_stories_loop = new WP_Query( $args );
    while ( $latest_stories_loop->have_posts() ) : $latest_stories_loop->the_post(); 
?>
			<li>
				<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a>
			</li>
<?php
    endwhile;
    wp_reset_postdata();
?>
		</ul>
	</div> 



		 		 
	</div> <!-- end #sidebar -->
<?php } ?>
