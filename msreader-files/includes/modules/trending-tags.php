<?php
$module = array(
	'name' => __( 'Trending Tags', 'wmd_msreader' ),
	'description' => __( 'Displays trending tags', 'wmd_msreader' ),
	'slug' => 'trending-tags', 
	'class' => 'WMD_MSReader_Module_TrendingTags'
);

class WMD_MSReader_Module_TrendingTags extends WMD_MSReader_Modules {

	function init() {
		add_filter( 'msreader_dashboard_reader_sidebar_widgets', array($this,'add_widget'), 20 );
    }

    function add_widget($widgets) {
    	$widgets['trending-tags'] = $this->create_links_widget(array(array('args' => 'argument','title' => 'test')));

    	return $widgets;
    }

    function get_page_title() {
		return $this->module['page_title'].' TAGX';
    }

}