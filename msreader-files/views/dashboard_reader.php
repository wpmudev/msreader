<div class="wrap">

	<?php screen_icon(); ?>
	<h2><?php echo $query_details['page_title']; ?></h2>
	
	<div id="poststuff">
	<div id="msreader-posts">
		<div id="post-body" class="metabox-holder columns-2">

			<div id="postbox-container-1" class="postbox-container">
				<?php include_once('dashboard_reader_sidebar.php'); ?>
			</div>

			<div id="postbox-container-2" class="postbox-container">
				<div class="postbox ">
					<div class="inside">
						<?php
						if(is_array($posts)) {

						}
						elseif($posts == 'error')
							echo 'oh no we have error:(';
						else
							echo $posts;
						?>
					</div>
				</div>				
			</div>

		</div>
	</div>
	</div>
</div>