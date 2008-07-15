<div id="sidebar">

<a href="<?php echo get_settings('home'); ?>/"><img src="<?php bloginfo('stylesheet_directory'); ?>/images/header.jpg" width="336" alt="<?php bloginfo('name'); ?>" /></a><br/>

<h1><a href="<?php echo get_option('home'); ?>/"><?php bloginfo('name'); ?></a></h1>
<?php bloginfo('description'); ?>

<?php if ( !function_exists('dynamic_sidebar')
        || !dynamic_sidebar(1) ) : ?>
        
        
	<h3>about</h3>
		<?php bloginfo('name'); ?> was designed by <a href="http://www.upstartblogger.com/">Upstart Blogger</a>. Here's where you put a little blurb to let visitors know what your site is about. Just edit <b>sidebar.php</b>.

		
<h3>search</h3><br/>
	<?php include(TEMPLATEPATH . '/searchform.php'); ?>

<?php if (function_exists('akpc_most_popular')) { ?>
<h3>popular</h3>
<ul>
<?php akpc_most_popular($limit = 5); ?>
</ul>
<?php } ?>

<!-- edit this to add your own advertising content -->
<h3>clickage</h3>
You can put an ad here if you like. Just edit <b>sidebar.php</b>. For the latest information about this theme, please check this theme's page on Upstart Blogger, <a href="http://www.upstartblogger.com/wordpress-theme-upstart-blogger-minim" title="WordPress Theme: Upstart Blogger Minim">WordPress Theme: Upstart Blogger Minim</a>.		

<h3>recent</h3>
			<ul>
					<?php	query_posts('showposts=5');	?>
					<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
					<li><strong><a href="<?php the_permalink() ?>"><?php the_title() ?> </a></strong></li>
					<?php endwhile; endif; ?>
			</ul>	

<?php if (function_exists('get_recent_comments')) { ?>
<h3>comments</h3>
<ul class="dates"><?php get_recent_comments(); ?></ul>
<?php } ?>

<?php endif; ?>


<?php if (function_exists('wp_tag_cloud') ) : ?>
<h3>Tag Cloud</h3>
<?php wp_tag_cloud('smallest=8&largest=36&'); ?>
<?php endif; ?>

 
<div id="sidebarright">
<?php if ( !function_exists('dynamic_sidebar')
        || !dynamic_sidebar(3) ) : ?>

		
<h3>inside</h3>
<ul>
	<?php wp_list_pages('title_li=' ); ?>
</ul>


				<h3>Links</h3>
				<ul>
				<?php wp_list_bookmarks('categorize=0&title_li='); ?>
				</ul>

				<h3>Meta</h3>
				<ul>
					<?php wp_register(); ?>
					<li><?php wp_loginout(); ?></li>
					<li><a href="http://jigsaw.w3.org/css-validator/validator?uri=<?php echo get_settings('home'); ?>" title="Validate documents CSS">Validate CSS</a></li>
					<li><a href="http://validator.w3.org/check/referer" title="This page validates as XHTML 1.0 Transitional">Valid <abbr title="eXtensible HyperText Markup Language">XHTML</abbr></a></li>
					<li><a href="http://gmpg.org/xfn/"><abbr title="XHTML Friends Network">XFN</abbr></a></li>
							<li><a href="http://www.upstartblogger.com/">Upstart Blogger</a></li>
					<li><a href="http://wordpress.org/" title="Powered by WordPress, state-of-the-art semantic personal publishing platform.">WordPress</a></li>
					<?php wp_meta(); ?>
				</ul>
				


<?php endif; ?>
</div><!--sidebarright-->


<div id="sidebarleft">
<?php if ( !function_exists('dynamic_sidebar')
        || !dynamic_sidebar(2) ) : ?>

	<h3>Archives</h3>	
		<ul>
    <?php wp_get_archives('type=monthly&show_post_count=1'); ?>
  	</ul>	

 <h3>Categories</h3>
	 <ul>
	<?php wp_list_categories('show_count=1&title_li='); ?>
	</ul>


				
<h3>feeds</h3>
				<ul>
					<li><a href="<?php bloginfo('rss2_url'); ?>">Entries (RSS)</a></li>
					<li><a href="<?php bloginfo('comments_rss2_url'); ?>">Comments (RSS)</a></li>
				</ul>

<p><a href="<?php bloginfo('rss2_url'); ?>"><img src="<?php bloginfo('stylesheet_directory'); ?>/images/feed-icon-28x28.png" alt="Feed" /></a></p>

			<?php endif; ?>
			
<h3>Copyright</h3>
	Copyright &copy; 2007 by <?php bloginfo('name'); ?>. All rights reserved.
	<!-- This theme is free and you can alter it as you see fit, but please keep the link to Upstart Blogger. Thanks! --><p>Designed by <a href="http://www.upstartblogger.com/">Upstart Blogger</a>.</p>
								
</div><!--end sidebarleft-->				
</div><!--end sidebar-->
