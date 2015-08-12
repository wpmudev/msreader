<?php
do_action('msreader_dashboard_reader_sidebar_top');

$sidebar_widgets = apply_filters('msreader_dashboard_reader_sidebar_widgets', 
	array(
		'reader' => array(
				'title' => apply_filters('msreader_dashboard_reader_sidebar_widget_reader_title', __('Reader', 'wmd_prettyplugins')),
				'data' => array(
						'links' => array()
					)
			)
	)
);

foreach ($sidebar_widgets as $slug => $details) {
	//open default styling
	$default_style = (!isset($details['default_style']) || (isset($details['default_style']) && $details['default_style'])) ? 1 : 0;
	if($default_style) { 
	?>
		<div id="msreader-widget-<?php echo $slug; ?>" class="msreader-widget postbox">
			<?php echo isset($details['title']) ? '<h3>'.$details['title'].'</h3>' : ''; ?>
	<?php 
	}
	else {
	?>
		<div id="msreader-widget-<?php echo $slug; ?>" class="msreader-widget">
	<?php
	}
	
	//echo widget data
	if(isset($details['data']) && is_array($details['data']))
		foreach ($details['data'] as $type => $content) {
			//echo as links if links
			if($type == 'list' && isset($content) && count($content) > 0) {
				echo '<div class="inside"><ul class="list">';
				foreach ($content as $priority => $value) {
					if(!isset($value['title']))
						continue;
					
					//check for active url so class can be added
					if(isset($value['link'])){
						$link_query = parse_url($value['link']);
						$link_query = isset($link_query['query']) ? $link_query['query'] : '';
						$link_args = array();
						parse_str($link_query, $link_args);
						$default_module = apply_filters('msreader_default_module', $this->plugin['site_options']['default_module']);

						if(isset($_POST['current_url']) && !isset($_GET['module'])) {
							$parts = parse_url($_POST['current_url']);
							parse_str($parts['query'], $query);
							$current_module = isset($query['module']) ? $query['module'] : 0;
						}
						else
							$current_module = isset($_GET['module']) ? $_GET['module'] : 0;

						$link_classes = isset($value['link_classes']) ? ' class="'.(is_array($value['link_classes']) ? implode(' ', $value['link_classes']) : $value['link_classes']).'"' : '';

						$active = ($this->helpers->is_page_link_active($value['link']) || ($link_args['module'] == $default_module && !$current_module)) ? ' class="active"' : '';
						echo '<li'.$active.'>'.(isset($value['before']) ? $value['before'] : '').'<a href="'.$value['link'].'"'.$link_classes.'>'.$value['title'].'</a>'.(isset($value['after']) ? $value['after'] : '').'</li>';
					}
					else
						echo '<li>'.(isset($value['before']) ? $value['before'] : '').$value['title'].(isset($value['after']) ? $value['after'] : '').'</li>';
				}
				echo '</ul></div>';
			}
			//echo as html by default
			else
				echo $type == 'html' ? $content : '';
		}
	elseif(isset($details['data']))
		echo $details['data'];

	
	//close default styling
	if($default_style) { ?>
		</div>	
	<?php
	}
	else {
	?>
		</div>
	<?php
	}
}

do_action('msreader_dashboard_reader_sidebar_bottom');
if(is_super_admin())
	echo '<p style="margin:-10px 0 10px 0; text-align:center;"><small>'.sprintf(__('<a href="%s">Change Reader settings</a>', 'wmd_prettyplugins'), admin_url('network/settings.php?page=msreader.php')).'</small></p>';
