<div class="theme-header msreader-post-header-navigation">
	<button class="left dashicons dashicons-no"><span class="screen-reader-text"><?php _e( 'Show previous post', 'wmd_msreader' ); ?></span></button>
	<button class="right dashicons dashicons-no"><span class="screen-reader-text"><?php _e( 'Show next post', 'wmd_msreader' ); ?></span></button>
	<div class="links">
		<a href="<?php echo get_permalink($post->ID); ?>"><?php _e( 'View Orginal', 'wmd_msreader' ); ?></a><?php
		if(current_user_can( 'edit_others_posts', $post->ID ) || ($post->post_author == $current_user_id))  { 
			 edit_post_link(__( 'Edit', 'wmd_msreader' ), '', '');
		}
		?>
	</div>
	<span class="spinner spinner-save"></span>
	<button class="close dashicons dashicons-no"><span class="screen-reader-text"><?php _e( 'Close overlay', 'wmd_msreader' ); ?></span></button>
</div>
<div class="msreader-post-content-comments">
	<div class="msreader-post-content">
		<h3 class="theme-name"><?php the_title(); ?><span class="theme-version"><?php echo get_bloginfo('name'); ?></span></h3>
		<h4 class="theme-author">By <?php the_author(); ?></h4>
		
		<?php the_content(); ?>
	</div>
	<div class="msreader-post-comments">
		<?php if(count($comments) > $comments_limit) { ?>
		<div class="comments-loader">
			<button class="button button-secondary load-previous-comments"><?php _e('Load Previous Comments', 'wmd_msreader' ); ?></button>
		</div>
		<?php } ?>
		<div id="the-comment-list" class="comments">
			<?php include('comments.php'); ?>
		</div>
	</div>

	<div class="msreader-add-comment">
		<form action="" method="post" class="add-comment-form">
			<div class="add-comment-info"><h4><?php _e('Add new comment', 'wmd_msreader' ); ?></h4></div>
			<p class="comment-form-comment">
				<textarea id="comment" name="comment_add_data[comment]" cols="44" rows="7" aria-required="true"></textarea>
			</p>
			<p class="form-submit">
				<span class="reply-info" style="display:none;"><small><?php _e('Replay to', 'wmd_msreader' ); ?>:</small> <strong class="reply-parent-name">Trex admin</strong> <small>(<a class="reply-cancel" href="#"><?php _e('cancel', 'wmd_msreader' ); ?></a>)</small></span>
				<input name="submit" type="button" class="button button-primary right" id="submit" value="Post Comment">
				<input type="hidden" name="comment_add_data[comment_parent]" id="comment-parent" value="0">
			</p>
		</form>
	</div>
</div>