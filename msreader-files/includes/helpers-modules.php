<?php
abstract class WMD_MSReader_Modules {
	var $module_details;

	function __construct() {
		global $module;

		$this->module = $module;

		//sets default unnecessary data
		if(!isset($this->module['page_title']))
			$this->module['page_title'] = $this->module['name'];
		if(!isset($this->module['menu_title']))
			$this->module['menu_title'] = $this->module['name'];

		//do the custom init by module
		$this->init();
    }
    abstract function init();

    function create_link_for_main_widget() {
		$link = array(
				'title' => $this->module['menu_title'], 
				'link' => add_query_arg(array('module' => $this->module['slug']))
			);

		return $link;
    }

    function create_links_widget($links) {
		$widget = array(
    		'title' => $this->module['menu_title'], 
    		'data' => array(
    			'links' => $links
    		)
    	);

		return $widget;
    }
} 