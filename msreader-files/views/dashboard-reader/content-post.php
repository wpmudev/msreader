<div class="postbox msreader-post" data-blog_id="<?php echo $post->BLOG_ID ?>" data-post_id="<?php echo get_the_ID(); ?>">
	<div class="msreader-post-content">
		<div class="inside">
			<h2><?php the_title(); ?></h2>
			<?php 
			if($post->featured_media_html)
				echo $post->featured_media_html;
			?>
			<div class="msreader-post-excerpt">
				<?php echo $post->post_excerpt; ?>
			</div>

		</div>
	</div>
	<div class="msreader-post-meta">
		<div class="inside">
			<?php echo get_avatar($post->post_author, 48); ?>

			<div class="vertical-middle">
				<?php echo $post->relative_time; ?>
				<?php _e( 'ago', 'wmd_msreader' ); ?>
				<?php _e( 'by', 'wmd_msreader' ); ?>
				<?php echo apply_filters('msreader_post_author', get_the_author(), $post); ?>
				<br/>

				<?php echo apply_filters('msreader_post_blog', $post->blog_details->blogname, $post); ?>
			</div>

			<?php 
			echo apply_filters('msreader_read_more_button', 
			'<button class="right button button-secondary msreader-open-post">'. __( 'Read more', 'wmd_msreader' ) .'</button>', 
			$post); 
			?>
			<span class="spinner spinner-save"></span>
		</div>
	</div>
</div>	