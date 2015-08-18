<?php if ( is_active_sidebar( 'sidebar' ) ){ ?>
	<div id="sidebar">

	<div class="widget widget_search">
<?php /*
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
}*/
?>
	</div>

	<div class="widget">
		<form role="search" method="get" class="searchform" action="<?php echo site_url(); ?>/">
			<h4 class="widgettitle">Search Stories</h4>
		
    		<div>
        		<input type="text" name="s" class="s" value="<?php echo $_GET['s'] ?>" /><input type="submit" class="searchsubmit" value="Search" />
				<input type="hidden" name="post_type" value="story" />
    		</div>


			<h4 class="widgettitle">Filters</h4>
			<h5>Filter by keywords</h5>
    		<div>
        		<input type="text"   name="term" value="<?php echo $_GET['term'] ?>" class="s" /><input type="submit" class="searchsubmit" value="Search" />
        		<input type="hidden" name="taxonomy" 			value="story_keyword" />
    		</div>

			<h5>Filter by author</h5>
    		<div>
        		<input type="text" name="author_name" value="<?php echo $_GET['author_name'] ?>" class="s" /><input type="submit" class="searchsubmit" value="Search" />
    		</div>
		</form>

		<h5>Filter by TM ID</h5>
		<form role="search" method="get" class="searchform" action="<?php echo site_url(); ?>/">
    		<div>
        		<input type="number" value="" name="s" class="s" /><input type="submit" class="searchsubmit" value="Search" />
        		<input type="hidden" name="post_type" 			value="story" />
        		<input type="hidden" name="s" 					value="<?php echo $_GET['s'] ?>" />
        		<input type="hidden" name="trismegistos" 		value="1" />
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
