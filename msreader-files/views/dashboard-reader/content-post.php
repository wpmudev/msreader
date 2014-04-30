<div class="postbox msreader-post" data-blog_id="<?php echo $post->BLOG_ID ?>" data-post_id="<?php echo get_the_ID(); ?>">
	<div class="inside">
		<h2><?php the_title(); ?></h2>
		<?php 
		if($post->featured_image_html)
			echo '<center>'.$post->featured_image_html.'</center>';

		the_excerpt();
		?>

	</div>
	<div class="msreader-post-meta">
		<div class="inside">
			<?php echo get_avatar($post->post_author, 48); ?>

			<div class="vertical-middle">
				<?php echo human_time_diff( get_post_time('U', true), current_time('timestamp') ); ?>
				<?php _e( 'by', 'wmd_msreader' ); ?>
				<?php the_author(); ?>
				<br/>

				<?php echo $post->blog_details->blogname; ?>
			</div>

			<button class="right button button-primary msreader-open-post"><?php _e( 'Read more', 'wmd_msreader' ); ?></button>
			<span class="spinner spinner-save"></span>
		</div>
	</div>
</div>	