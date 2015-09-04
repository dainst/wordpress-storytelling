<div class="et_pt_blogentry clearfix">
							<h2 class="et_pt_title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?><?php //the_ID(); ?></a></h2>
							
							<p class="et_pt_blogmeta">
								<?php esc_html_e('Posted','Flexible'); ?> 
								<?php esc_html_e('by','Flexible'); ?> 
								<?php /*the_author_posts_link();*/ the_author(); ?> 
								<?php esc_html_e('on','Flexible'); ?> 
								<?php the_time(et_get_option('flexible_date_format')) ?>
								<?php /*esc_html_e('in','Flexible');*/ ?> 
								<?php /*the_category(', ');*/ ?> 
								<?php /*comments_popup_link(esc_html__('0 comments','Flexible'), esc_html__('1 comment','Flexible'), '% '.esc_html__('comments','Flexible'));*/ ?>			
								<?php 
									$the_taxonomys = get_the_taxonomies($get_the_ID, array('template' => "<span class='tax-%s'>%l</span>"));
									echo (count($the_taxonomys['story_keyword'])) ? '<br> Keywords: ' . $the_taxonomys['story_keyword'] : '';
								?>				
							</p>
							<?php $thumb = '';
							$width = 480;
							$height = 220;
							$classtext = '';
							$titletext = get_the_title();

							$thumbnail = get_thumbnail($width,$height,$classtext,$titletext,$titletext);
							$thumb = $thumbnail["thumb"]; ?>
							<?php if(has_post_thumbnail()): ?>
								<div class="post-thumbnail news-thumbnail">
									<?php print_thumbnail($thumb, $thumbnail["use_timthumb"], $titletext, $width, $height, $classtext); ?>
									<a href="<?php the_permalink(); ?>"><span class="overlay"></span></a>
								</div>
							<?php endif; ?>
							
							
							<?php if (!$et_ptemplate_blogstyle) { ?>
								<p><?php the_excerpt();?></p>
								<a href="<?php the_permalink(); ?>" class="readmore"><span><?php esc_html_e('read more','Flexible'); ?> &raquo;</span></a>
							<?php } else { ?>
								<?php
									global $more;
									$more = 0;
								?>
								<?php the_content(); ?>
							<?php } ?>
						</div> <!-- end .et_pt_blogentry -->