<?php
do_action('msreader_dashboard_reader_sidebar_top');

$sidebar_widgets = apply_filters('msreader_dashboard_reader_sidebar_widgets', 
	array(
		'reader' => array(
				'title' => apply_filters('msreader_dashboard_reader_sidebar_widget_reader_title', 'Reader'),
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
			<div class="inside">
	<?php 
	}
	
	//echo widget data
	if(isset($details['data']) && is_array($details['data']))
		foreach ($details['data'] as $type => $content) {
			//echo as links if links
			if($type == 'links' && isset($content) && count($content) > 0) {
				echo '<ul class="links">';
				foreach ($details['data']['links'] as $priority => $value) {
					if(!isset($value['title']) || !isset($value['link']))
						continue;
					
					//check for active url so class can be added
					$link_query = parse_url($value['link']);
					$link_query = $link_query['query'];
					$active = ($link_query == $_SERVER['QUERY_STRING']) ? ' class="active"' : '';

					echo '<li'.$active.'><a href="'.$value['link'].'">'.$value['title'].'</a></li>';
				}
				echo '</ul>';
			}
			//echo as html by default
			else
				echo isset($details['data']['html']) ? $details['html'] : '';
		}
	elseif(isset($details['data']))
		echo $details['data'];

	
	//close default styling
	if($default_style) { ?>
			</div>
		</div>	
	<?php
	}

}

do_action('msreader_dashboard_reader_sidebar_bottom');