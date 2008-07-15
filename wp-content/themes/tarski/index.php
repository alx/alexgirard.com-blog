<?php get_header(); ?>


<?php if(have_posts()) { ?>
	
	<?php if(is_single() || is_page()) { // Single entries and pages ?>
		
		
		<div class="primary">
			<?php while(have_posts()) { the_post(); ?>
				
				<div class="entry hentry">
					
					<div class="meta">
						<h1 class="title entry-title"><?php the_title(); ?></h1>
						<?php if(is_single()) { ?>
							<p class="metadata"><?php echo '<span class="date updated">' . tarski_date() . '</span>';
							tarski_post_categories_link();
							tarski_author_posts_link();
							tarski_comments_link();
							edit_post_link(__('edit','tarski'),' <span class="edit">(',')</span>'); ?></p>
						<?php } else { ?>
							<?php edit_post_link(__('edit page','tarski'), '<p class="metadata"><span class="edit">(', ')</span></p>'); ?>
						<?php } ?>
					</div>
					
					<div class="content">
						<?php the_content(); ?>
					</div>

					<?php th_postend(); ?>
					
				</div> <!-- /entry -->
				
			<?php } // End entry loop ?>
		</div> <!-- /primary -->
	
	
	<?php } else { ?>
		

		<div class="primary posts">
			
			<?php include(TEMPLATEPATH . '/loop.php'); ?>
			
		</div>
	
	<?php } // End loop types ?>
	
<?php } else { // If no posts ?>


	<div class="primary">
		
		<?php include(TARSKIDISPLAY . "/no_posts.php"); ?>
		
	</div> <!-- /primary -->


<?php } // End loop ?>



<?php get_sidebar(); ?>



<?php if(is_single() || is_page()) { comments_template(); } ?>



<?php get_footer(); ?>