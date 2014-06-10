<div class="postbox msreader-post" data-blog_id="<?php echo $post->BLOG_ID ?>" data-post_id="<?php echo get_the_ID(); ?>">
	<div class="msreader-post-content">
		<div class="inside">
			<?php 
			echo apply_filters('msreader_list_post_title', 
			'<h2>'.get_the_title().'</h2>', 
			$post); 
			?>
			<?php 
			if($post->featured_media_html)
				echo '<div class="msreader_featured_media"><center>'.$post->featured_media_html.'</center></div>';
			?>
			<div class="msreader-post-excerpt">
				<?php echo $post->post_excerpt; ?>
			</div>
			<div class="msreader-post-actions">
				<?php 
				echo apply_filters('msreader_read_more_button', 
				'<button class="right button button-secondary msreader-open-post">'. __( 'Read More', 'wmd_msreader' ) .'</button>', 
				$post); 
				?>
				<span class="spinner spinner-save"></span>
			</div>
		</div>
	</div>

	<div class="msreader-post-meta">
		<div class="inside">
			<?php echo get_avatar($post->post_author, 48); ?>

			<div class="vertical-middle">
				<span data-post_time="<?php echo $post->post_date_stamp; ?>"><?php echo $post->post_date_relative; ?></span>
				<?php _e( 'ago', 'wmd_msreader' ); ?>
				<?php _e( 'by', 'wmd_msreader' ); ?>
				<?php echo apply_filters('msreader_post_author', get_the_author(), $post); ?>
				<br/>

				<?php echo apply_filters('msreader_post_blog', $post->blog_details->blogname, $post); ?>
			</div>
		</div>
	</div>
</div>	