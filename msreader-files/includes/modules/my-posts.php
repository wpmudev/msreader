<?php
$module = array(
	'name' => __( 'My Posts', 'wmd_msreader' ),
	'description' => __( 'Displays my posts', 'wmd_msreader' ),
	'slug' => 'my-posts', 
	'class' => 'WMD_MSReader_Module_MyPosts'
);

class WMD_MSReader_Module_MyPosts extends WMD_MSReader_Modules {

	function init() {
		add_filter( 'msreader_dashboard_reader_sidebar_widgets', array($this,'add_link_to_widget'), 10 );
    }

    function add_link_to_widget($widgets) {
		$widgets['reader']['data']['links'][] = $this->create_link_for_main_widget();

    	return $widgets;
    }

    function query() {
    	return 'kupa';
    }

}