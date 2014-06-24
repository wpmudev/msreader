<div class="theme-header msreader-post-header-navigation">
	<button class="left dashicons dashicons-no"><span class="screen-reader-text"><?php _e( 'Show previous post', 'wmd_msreader' ); ?></span></button>
	<button class="right dashicons dashicons-no"><span class="screen-reader-text"><?php _e( 'Show next post', 'wmd_msreader' ); ?></span></button>
	<div class="links">
		<a href="<?php echo get_permalink($post->ID); ?>"><?php _e( 'View Orginal', 'wmd_msreader' ); ?></a><?php
		if(current_user_can( 'edit_others_posts', $post->ID ) || ($post->post_author == $current_user_id))
			 edit_post_link(__( 'Edit', 'wmd_msreader' ), '', '');
		if($post->post_status != 'publish' && current_user_can( 'publish_posts', $post->ID ))
			echo '<button class="publish" data-nonce="'.wp_create_nonce( 'publish_post' ).'">'.__( 'Publish', 'wmd_msreader' ).'</button>';
		echo apply_filters('msreader_dashboard_single_links', '', $post);
		?>
	</div>
	<button class="close dashicons dashicons-no"><span class="screen-reader-text"><?php _e( 'Close overlay', 'wmd_msreader' ); ?></span></button>
	<span class="spinner spinner-save"></span>
</div>
<div class="msreader-post">
	<div class="msreader-post-holder">
		<h3 class="theme-name"><?php echo apply_filters('msreader_post_title', get_the_title(), $post); ?><span class="theme-version msreader-blogname"><?php echo apply_filters('msreader_post_blog', $post->blog_details->blogname, $post); ?></span></h3>
		<h4 class="theme-author">
			<?php echo $post->post_date_relative; ?>
			<?php _e( 'ago', 'wmd_msreader' ); ?>
			<?php _e( 'by', 'wmd_msreader' ); ?>
			<?php echo apply_filters('msreader_post_author', get_the_author(), $post); ?>
		</h4>
		
		<div class="msreader-post-content">
			<?php the_content(); ?>
		</div>
	</div>
	<div class="msreader-comments">
		<?php if(count($comments) > $comments_limit) { ?>
		<div class="comments-loader">
			<button class="button button-secondary load-previous-comments"><?php _e('Load Previous Comments', 'wmd_msreader' ); ?></button>
			<span class="spinner spinner-save"></span>
		</div>
		<?php } ?>
		<div id="the-comment-list" class="comments" data-nonce="<?php echo wp_create_nonce( 'moderate_comment' ); ?>">
			<?php include('comments.php'); ?>
		</div>
	</div>

	<div class="msreader-add-comment">
		<?php if(comments_open( $post->ID )) { ?>
			<form action="" method="post" class="add-comment-form">
				<div class="add-comment-info"><h4><?php _e('Add new comment', 'wmd_msreader' ); ?></h4></div>
				<p class="comment-form-comment">
					<textarea id="comment" name="comment_add_data[comment]" cols="44" rows="7" aria-required="true"></textarea>
				</p>
				<p class="form-submit">
					<span class="reply-info" style="display:none;"><small><?php _e('Reply to', 'wmd_msreader' ); ?>:</small> <strong class="reply-parent-name">Trex admin</strong> <small>(<a class="reply-cancel" href="#"><?php _e('cancel', 'wmd_msreader' ); ?></a>)</small></span>
					<input name="submit" type="button" class="button button-primary right" id="submit" value="Post Comment">
					<span class="spinner spinner-save"></span>
					<input type="hidden" name="comment_add_data[comment_parent]" id="comment-parent" value="0">
					<input type="hidden" name="nonce_add_comment" id="nonce_add_comment" value="<?php echo wp_create_nonce( 'add_comment' ); ?>">
				</p>
			</form>
		<?php } else { ?>
			<div id="msreader-comments-closed">
				<?php _e( 'Comments are closed.', 'wmd_msreader' ); ?>
			</div>
		<?php } ?>
	</div>
</div>