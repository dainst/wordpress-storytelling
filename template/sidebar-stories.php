<?php if ( is_active_sidebar( 'sidebar' ) ){ ?>
	<div id="sidebar">

	<div class="topsearch">
          <h3><a href='<?php bloginfo('url'); ?>/stories'>Stories</a></h3>
          <p><a href='<?php bloginfo('url'); ?>/stories'>Flagship Storytelling Application</a></p>
    </div>
    


	<div class="widget">
	

		<form role="search" method="get" class="searchform" id="esa_searchform" action="<?php echo site_url(); ?>/">
			<h4 class="widgettitle">Search Stories</h4>
		
    		<div>
        		<input type="text" name="s" class="s" value="<?php echo get_query_var('s') ?>" /><input type="submit" class="searchsubmit" value="Search" />
				<input type="hidden" name="post_type" value="story" />
    		</div>


			<h4 class="widgettitle">Filters</h4>
			<h5>Filter by keyword</h5>
    		<div>
    			<?php esa_keyword_cloud(array('selected' =>  $_GET['term'])); ?>
        		<?php // <input type="hidden" name="term" 	value="<?php echo (get_query_var('taxonomy') == 'story_keyword') ? get_query_var('term') : '' ? >" class="s" id='esa_keyword_filter' />?>
				<?php // <input type="submit" class="searchsubmit" value="Search" /> ?>
        		<input type="hidden" name="taxonomy" value="story_keyword" />
    		</div>

			<h5>Filter by author</h5>
    		<div>
    			<?php esa_dropdown_users((int) $_GET['author']); ?>
        		<?php // <input type="text" name="author_name" value="<?php echo get_query_var('author_name') ? >" class="s" />?>
				<?php // <input type="submit" class="searchsubmit" value="Search" /> ?>
    		</div>

			<h5>Filter by Europeana ID</h5>
    		<div>
        		<input type="hidden" name="esa_item_source" 	value="europeana" />
        		<input type="text" 	 name="esa_item_id" 	id="esa-filter-europeana"	value="<?php echo ($_GET['esa_item_source']  == 'europeana') ? $_GET['esa_item_id'] : '' ?>" class="s" />
        		<?php //<input type="submit" class="searchsubmit" value="Search" / ?>
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

	<div class="widget widget_edit">
		<?php if (is_user_logged_in()) { ?>
			<h4 class='widgettitle'><?php echo 'Logged in as '.wp_get_current_user()->user_login ?></h4>
			<p>
				<?php if(current_user_can( 'edit_others_posts', get_ ) || ($post->post_author == $current_user->ID))  { ?>
					<?php edit_post_link('Edit this story'); ?>	<br>
				<?php }?>
				<a href="<?php echo site_url(); ?>/wp-admin/post-new.php?post_type=story">Create new story</a><br>
				<a href="<?php echo site_url(); ?>/wp-admin/edit.php?post_type=story">Edit existing stories</a><br><br>
				<a href="<?php echo wp_logout_url() ?>">Logout</a>
			</p>
		<?php } else { ?>
			<h4 class='widgettitle'>Not logged in</h4>
			<p>
				<a href="<?php echo wp_login_url(site_url('/stories/')); ?>" title="Login">Log in</a> to create a story.
			</p>
		<?php }	?>
	</div>
		 		 
	</div> <!-- end #sidebar -->
<?php } ?>
