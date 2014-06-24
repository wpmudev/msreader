<div id="msreader-dashboard" class="wrap">

	<?php screen_icon(); ?>
	<h2><?php echo apply_filters('msreader_dashboard_page_title', $query_details['page_title']); ?></h2>

	<?php 
	if($this->main_query->module->message) {
		$notification_class = $this->main_query->module->message_type ? 'updated' : 'error';
		?>
		
		<div id="setting-error-settings_updated" class="<?php echo $notification_class; ?> settings-error"> 
		<p><strong><?php echo $this->main_query->module->message; ?></strong></p></div>
		
		<?php 
	} 
	?>
	
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">

			<div id="postbox-container-1" class="postbox-container msreader-sidebar">
				<?php include_once('sidebar.php'); ?>
			</div>

			<div id="postbox-container-2" class="msreader-posts postbox-container <?php echo 'msreader_module_'.$this->main_query->module->details['slug']; ?>">
				<?php do_action('msreader_dashboard_before_post_list'); ?>
				<?php include('404.php'); ?>
				<div class="msreader-post-loader">
					<img alt="<?php _e( 'Loading...', 'wmd_msreader' ); ?>" src="<?php echo includes_url('images/spinner-2x.gif'); ?>"/>
				</div>
			</div>

		</div>
	</div>
</div>

<div class="theme-overlay msreader-post-overlay" style="display:none;">
	<div class="theme-backdrop"></div>
	<div class="theme-wrap msreader-post-wrap"></div>
</div>