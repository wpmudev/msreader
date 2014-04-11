<?php
class WMD_MSReader_Query {
	var $modules;

	var $blog_id;
	var $post_id;

	var $module;
	var $page;
	var $limit;
	var $args;

	function __construct() {
		global $msreader_modules;

		//load available modules
		$this->modules = $msreader_modules;
		
		$this->setup_default_parameters();
    }

	function setup_default_parameters() {
		//set up which module to display
		if(isset($_REQUEST['module']) && array_key_exists($_REQUEST['module'], $this->modules))
			$this->module = $this->modules[$_REQUEST['module']];
		else {
			reset($this->modules);
			$this->module = $this->modules[key($array)];
		}

		//set up which page to display
		if(isset($_REQUEST['page']) && is_numeric($_REQUEST['module']))
			$this->page = $this->parameters['page'];
		else
			$this->page = 1;

		//setup the default limit
		$this->limit = 10;
	}

	function get_query_details() {
		return array(
				'page_title' => $this->module->get_page_title()
			);
	}

	function get_posts() {
		$posts = $this->module->query();

		return $posts;
	}

	function get_post() {

	}

	function get_posts_rss() {

	}
}