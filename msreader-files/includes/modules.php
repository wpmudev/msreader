<?php
//Class with default functions for all modules. Fast to use and easy to customize
abstract class WMD_MSReader_Modules {
	var $module;

	function __construct() {
		global $module;

		//set module details
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

    //This function needs to be replaced to display proper data
    function query() {
		return 'error';
    }

    //by default page title is module title
    function get_page_title() {
		return $this->module['page_title'];
    }

    //easily adds link to main widget
    function create_link_for_main_widget() {
		$link = array(
				'title' => $this->module['menu_title'], 
				'link' => add_query_arg(array('module' => $this->module['slug']))
			);

		return $link;
    }

    //lets you create links widget for module by providing array with arrays with "arg"(argument that will be added at the end), "title" or optionaly full link by "link"
    function create_links_widget($links) {
    	foreach ($links as $position => $data) {
    		if(isset($data['args'])){
    			$data['link'] = add_query_arg(array('module' => $this->module['slug'], 'args' => $data['args']));
    			$links[$position] = $data;
    		}
    	}
		$widget = array(
    		'title' => $this->module['menu_title'], 
    		'data' => array(
    			'links' => $links
    		)
    	);

		return $widget;
    }
} 