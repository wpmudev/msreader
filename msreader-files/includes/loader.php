<?php
class WMD_MSReader_Loader {
	var $mode = 'soft';



	var $parameters;

	function __construct() {
		$this->load_modules();
		$this->setup_parameters();
    }



	function setup_parameters() {
		//set up which module to display
		if(isset($_REQUEST['module']) && array_key_exists($_REQUEST['module'], $this->available_modules))
			$this->parameters['module'] = $_REQUEST['module'];
		else {
			reset($this->available_modules);
			$this->parameters['module'] = $this->available_modules[key($array)];
		}

		//set up which page to display
		if(isset($_REQUEST['page']) && is_numeric($_REQUEST['module']))
			$this->parameters['page'] = $this->parameters['page'];
		else
			$this->parameters['page'] = 1;

		$this->parameters['limit'] = 10;
	}

	function get_post($post_id, $blog_id) {

	}

	function get_posts($module, $limit = 10, $page = 1, $args) {

	}

	function get_posts_rss($module, $limit = 15, $args) {

	}
}