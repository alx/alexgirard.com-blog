<?php
/*
Template Name: Links
*/
?>

<?php get_header(); ?>
<div id="container">
<div id="entries">
<h3>Links</h3>
<h1 style="display:inline; font-size:3.3em;"><?php the_title(); ?></h1>
<div class="post">
 <ul>
		 	<?php get_links_list(); ?>		
		 	</ul>
</div><!--end posts--> 
</div><!--end entries-->
<?php get_sidebar(); ?>
</div><!-- end container -->
<?php get_footer(); ?>