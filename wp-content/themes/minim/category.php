<?php get_header(); ?>

  <div id="container">
    <div id="entries">
      <?php next_posts_link('&laquo; Previous Entries') ?><br/>
      <?php previous_posts_link('&raquo; Next Entries') ?>

      <h3>Category</h3>

      <h1 style="display:inline; font-size:3em;"><?php single_cat_title() ?></h1><br/>
      <br/>
      <br/>
      <?php if (have_posts()) : ?>

      <ul>
        <?php while (have_posts()) : the_post(); ?>

        <li>
            <div class="results_content">
           <h3><?php the_category(', ') ?></h3><h1 style="display:inline;"><a href="<?php the_permalink(); ?>" style="color:#444444;"><?php the_title(); ?></a></h1><div style="margin-top:-2px;"><small><?php the_time('m.d.y'); ?> <b>|</b> <a href="<?php the_permalink(); ?>">Permalink</a> <b>|</b> <?php comments_popup_link('Comment?', '1 Comment', '% Comments' ) ?> </small></div>


            <div style="margin-top: -1em;">
              <?php the_excerpt(); ?>
            </div>
          </div>
        </li><?php endwhile; ?>
      </ul><?php else : ?>

      <h3>Not Found</h3><?php endif; ?><?php next_posts_link('&laquo; Previous Entries') ?><br/>
      <?php previous_posts_link('&raquo; Next Entries') ?>
    </div><!--end entries-->
<?php get_sidebar(); ?>
</div><!-- end container -->
<?php get_footer(); ?>