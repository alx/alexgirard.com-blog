<?php // Do not delete these lines
	if ('comments.php' == basename($_SERVER['SCRIPT_FILENAME']))
		die ('Please do not load this page directly. Thanks!');

	if (!empty($post->post_password)) { // if there's a password
		if ($_COOKIE['wp-postpass_' . COOKIEHASH] != $post->post_password) {  // and it doesn't match the cookie
			?>

			<p class="nocomments">This post is password protected. Enter the password to view comments.<p>

			<?php
			return;
		}
	}

	/* This variable is for alternating comment background */
	$oddcomment = 'alt';
?>

<!-- You can start editing here. -->

<?php if ($comments) : ?>
<h3><?php comments_number('Add Your Comment', '1 Comment', '% Comments' );?></h3>

						
<ul class="commentlist">
	<?php foreach ($comments as $comment) : ?>

		<li class="<?php echo $oddcomment; ?>" id="comment-<?php comment_ID() ?>">
		<?php edit_comment_link('e ','',''); ?>On <?php comment_date('m.d.y') ?> <?php comment_author_link() ?> wrote these pithy words:
				<?php if ($comment->comment_approved == '0') : ?>
				<em>Your comment is awaiting moderation.</em>
				<?php endif; ?>
				<?php comment_text() ?>
		</li>

			<?php /* Changes every other comment to a different class */
		if ('alt' == $oddcomment) $oddcomment = '';
		else $oddcomment = 'alt';
	?>

	<?php endforeach; /* end for each comment */ ?>

</ul>


 <?php else : // this is displayed if there are no comments so far ?>

	<?php if ('open' == $post->comment_status) : ?>
		<!-- If comments are open, but there are no comments. -->

	 <?php else : // comments are closed ?>
		<!-- If comments are closed. -->
		<p class="nocomments">Comments are closed.</p>

	<?php endif; ?>
<?php endif; ?>


<?php if ('open' == $post->comment_status) : ?>

<a name="respond"></a>	<h3>have your say</h3>

					<div style="background-color: #f3f3f3; padding: 10px;">		<?php if (('open' == $post-> comment_status) && ('open' == $post->ping_status)) {
							// Both Comments and Pings are open ?>
							Add your comment below, or <a href="<?php trackback_url(true); ?>" rel="trackback">trackback</a> from your own site. <?php comments_rss_link('Subscribe to these comments.'); ?>
						
						<?php } elseif (!('open' == $post-> comment_status) && ('open' == $post->ping_status)) {
							// Only Pings are Open ?>
							Responses are currently closed, but you can <a href="<?php trackback_url(true); ?> " rel="trackback">trackback</a> from your own site.
						
						<?php } elseif (('open' == $post-> comment_status) && !('open' == $post->ping_status)) {
							// Comments are open, Pings are not ?>
							You can skip to the end and leave a response. Pinging is currently not allowed.
			
						<?php } elseif (!('open' == $post-> comment_status) && !('open' == $post->ping_status)) {
							// Neither Comments, nor Pings are open ?>
							Both comments and pings are currently closed.			
						
						<?php } edit_post_link('Edit this post.','',''); ?>
<p><small><strong>Be nice. Keep it clean. Stay on topic. No spam.</strong></small></p>
<p><small><strong>You can use these tags:</strong><br/><?php echo allowed_tags(); ?></small></p>
				<?php if ( get_option('comment_registration') && !$user_ID ) : ?>
				<p>You must be <a href="<?php echo get_option('siteurl'); ?>/wp-login.php?redirect_to=<?php the_permalink(); ?>">logged in</a> to post a comment.</p>
</div><!-- end container -->
				<?php else : ?>
				<form action="<?php echo get_option('siteurl'); ?>/wp-comments-post.php" method="post" id="commentform">
				<?php if ( $user_ID ) : ?>
				<p>Logged in as <a href="<?php echo get_option('siteurl'); ?>/wp-admin/profile.php"><?php echo $user_identity; ?></a>. <a href="<?php echo get_option('siteurl'); ?>/wp-login.php?action=logout" title="Log out of this account">Logout &raquo;</a></p>
					<?php else : ?>
				
				<p><label class="text">Name<?php if ($req) echo " (required)"; ?></label>: <input type="text" name="author" id="author" value="<?php echo $comment_author; ?>" class="textfield" tabindex="1" /></p>
				<p><label class="text">Email<?php if ($req) echo " (required)"; ?></label>: <input type="text" name="email" id="email" value="<?php echo $comment_author_email; ?>" class="textfield" tabindex="2" /></p>
				<p><label class="text">Website: </label><input type="text" name="url" id="url" value="http://" class="textfield" tabindex="3" /></p>
				
				<?php endif; ?>

	<textarea name="comment" id="comment" class="commentbox" tabindex="4"></textarea>
				
<p><input alt="Submit" name="submit" type="submit" tabindex="5" value="Speak!"/></p>
								
				<input type="hidden" name="comment_post_ID" value="<?php echo $id; ?>" />
				<?php do_action('comment_form', $post->ID); ?>
				</form></div>

<?php endif; // If registration required and not logged in ?>

<?php endif; // if you delete this the sky will fall on your head ?>