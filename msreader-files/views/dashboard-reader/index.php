<div id="msreader-dashboard" class="wrap">

	<?php screen_icon(); ?>
	<h2><?php echo $query_details['page_title']; ?></h2>
	
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">

			<div id="postbox-container-1" class="postbox-container msreader-sidebar floating">
				<?php include_once('sidebar.php'); ?>
			</div>

			<div id="postbox-container-2" class="msreader-posts postbox-container">
				<?php
				if(is_array($posts) && count($posts) > 0) {
					global $post;

					foreach ($posts as $post) {
						setup_postdata($post);
						
						include('content-post.php');
					}
				}
				elseif($posts == 'error' || (is_array($posts) && count($posts) < 1))
					include('404.php');
				else {
					$html = $posts;
					include('content-page.php');
				}
				?>		
			</div>

		</div>
	</div>
</div>

<div class="theme-overlay msreader-post-overlay" style="display:none;">
	<div class="theme-backdrop"></div>
	<div class="theme-wrap msreader-post-wrap"></div>
</div>