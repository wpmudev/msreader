<?php
/*
Plugin Name: WMD MSReader
Plugin URI:
Description: Lets msreader doing the plugins in style!
Version: 0.1
Network: false
Text Domain: wmd_msreader
Author: WPMU DEV
Author URI: http://premium.WMD.org/
WDP ID: 0
*/

define( 'MSREADER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

class WMD_MSReader {
	var $plugin;

	var $available_modules;
	var $modules;

	var $main_query;

	function __construct() {
		$this->init();

		add_action( 'plugins_loaded', array($this,'plugins_loaded') );
		register_activation_hook($this->plugin_main_file, array($this, 'activate'));

		add_action('admin_enqueue_scripts', array($this,'register_scripts_styles_admin'));
		add_action('admin_init', array($this,'admin_init') );

		add_action( 'admin_menu', array($this,'admin_page') );
		add_action( 'network_admin_menu', array($this,'admin_page') );

		//AJAX actions for dashboards
		add_action( 'wp_ajax_dashboard_display_posts_ajax', array($this, 'dashboard_display_posts_ajax') );
		add_action( 'wp_ajax_dashboard_display_post_ajax', array($this, 'dashboard_display_post_ajax') );
		add_action( 'wp_ajax_dashboard_display_comments_ajax', array($this, 'dashboard_display_comments_ajax') );
		add_action( 'wp_ajax_dashboard_add_get_comment_ajax', array($this, 'dashboard_add_get_comment_ajax') );
		add_action( 'wp_ajax_dashboard_moderate_get_comment_ajax', array($this, 'dashboard_moderate_get_comment_ajax') );
	}

    function init() {
    	//set up all the default options
    	$this->plugin['debug'] = 0;
    	$this->plugin['mode'] = 'hard';

		$this->plugin['main_file'] = __FILE__;
		$this->plugin['dir'] = MSREADER_PLUGIN_DIR.'msreader-files/';
		$this->plugin['dir_url'] = plugin_dir_url($this->plugin['main_file']).'msreader-files/';
		$this->plugin['basename'] = plugin_basename($this->plugin['main_file']);
		$this->plugin['rel'] = dirname($this->plugin['basename']).'/';

		$this->plugin['default_site_options'] = array(
			'select' => 'label_two',
			'text' => 'sample text',
			'textarea' => 'sample textarea'
		);

		$this->plugin['site_options'] = get_site_option('wmd_msreader_options', 0);


		//load whats necessary
		include_once($this->plugin['dir'].'includes/modules.php');
		$this->load_modules();

		include_once($this->plugin['dir'].'includes/query.php');
		$this->setup_main_query();
    }

	function load_modules() {
		global $msreader_modules, $msreader_available_modules;

		$required_module_info = array('slug', 'class', 'name', 'description');

		if($this->plugin['mode'] == 'soft') {
			$modules = array(
				$this->plugin['dir'].'includes/modules/my-posts.php',
				$this->plugin['dir'].'includes/modules/trending-tags.php'
			);
		}
		else {
			//search the dir for files
			$location = $this->plugin['dir'].'includes/modules/';

			$modules = array();
			if ( !is_dir( $location ) )
				return;

			if ( ! $dh = opendir( $location ) )
				return;

			while ( ( $module = readdir( $dh ) ) !== false ) {

				if ( substr( $module, -4 ) == '.php' )
				$modules[] = $location . $module;
			}
			closedir( $dh );
			sort( $modules );

		}

		$modules = apply_filters('msreader_load_modules', $modules);

		foreach ($modules as $file) {
			//simple global array to pass the module data around
			$module = array();

			include_once( $file );

			//check if it has all the data
			foreach ($required_module_info as $info)
				if(!array_key_exists($info, $module)) {
					$break = 1;
					break;
				}
			if($break)
				break;


			//load modules to be used
			if(class_exists($module['class'])) {
				$this->available_modules[$module['slug']] = $msreader_available_modules[$module['slug']] = $module;
				$this->modules[$module['slug']] = $msreader_modules[$module['slug']] = new $module['class'];
			}
		}
	}

	function setup_main_query() {
		$this->main_query = new WMD_MSReader_Query();

		//turn arg into array
		if(isset($_REQUEST['args']) && !is_array($_REQUEST['args']))
			$_REQUEST['args'] = array($_REQUEST['args']);

		//pass available parameters to main query
		$parameters_to_pass = 
		array(
			'numeric' => array(
				'page', 'limit', 'blog_id', 'post_id', 'comments_page', 'comments_limit'
			), 
			'array' => array(
				'args', 'comments_args', 'comment_add_data', 'comment_moderate_data'
			)
		);

		foreach ($parameters_to_pass as $type => $parameters)
			foreach ($parameters as $parameter)
				if(
					isset($_REQUEST[$parameter]) && 
					( 
						($type == 'numeric' && is_numeric($_REQUEST[$parameter])) || 
						($type == 'array' && is_array($_REQUEST[$parameter]))
					) 
				)
					$this->main_query->$parameter = $_REQUEST[$parameter];

		//set up which module to display
		if(isset($_REQUEST['module']) && array_key_exists($_REQUEST['module'], $this->modules))
			$this->main_query->load_module($this->modules[$_REQUEST['module']]);
		else {
			//lets use first one
			reset($this->modules);
			$this->main_query->load_module($this->modules[key($this->modules)]);
		}
	}


	function activate() {
        //save default options
		if($this->plugin['site_options'] == 0)
			update_option('wmd_msreader_options', $this->plugin['default_site_options']);
	}

	function plugins_loaded() {
		load_plugin_textdomain( 'wmd_msreader', false, $this->plugin['rel'].'languages/' );
	}

	function admin_init(){
		add_action( 'network_admin_notices', array(&$this,'options_page_validate_save_notices') );
	}

	function register_scripts_styles_admin($hook) {
		wp_register_style('wmd-msreader-admin', $this->plugin['dir_url'].'css/admin.css');
		wp_enqueue_style('wmd-msreader-admin');

		wp_register_script('wmd-msreader-admin', $this->plugin['dir_url'].'js/admin.js', array('jquery'));
		wp_enqueue_script('wmd-msreader-admin');

		wp_localize_script( 'wmd-msreader-admin', 'msreader_main_query', array(
			'module' => $this->main_query->module->details['slug'],
			'page' => $this->main_query->page,
			'limit' => $this->main_query->limit,
			'args' => $this->main_query->args,
			'comments_page' => $this->main_query->comments_page,
			'comments_limit' => $this->main_query->comments_limit,
			'comments_args' => $this->main_query->comments_args,
			'comment_add_data' => $this->main_query->comment_add_data
		) );

		wp_localize_script( 'wmd-msreader-admin', 'msreader_translation', array(
			'confirm' => __('Are you sure you want to do this?', 'wmd_msreader'),
			'confirm_child' => __('Are you sure you want to do this? This action will also affect all replies for this comment.', 'wmd_msreader'),
		) );
	}



	function admin_page() {
		add_dashboard_page('Reader', 'Reader', 'manage_options', basename($this->plugin['main_file']), array($this,'option_page'));
	}

	function option_page() {
		//get details to display
		$query_details = $this->main_query->get_query_details();
		$posts = $this->main_query->get_posts();

		include($this->plugin['dir'].'views/dashboard-reader/index.php');
	}

	function dashboard_display_posts_ajax() {
		error_reporting(0);

		$posts = $this->main_query->get_posts();

		if(is_array($posts)) {
			global $post;

			foreach ($posts as $post) {
				setup_postdata($post);
				
				include($this->plugin['dir'].'views/dashboard-reader/content-post.php');
			}
		}
		else 
			echo 0;

		die();
	}

	function dashboard_display_post_ajax() {
		error_reporting(0);
		global $post;

		if(get_current_blog_id() != $this->blog_id) {
			$restore = 1;
			switch_to_blog($this->blog_id);
		}

		$post = $this->main_query->get_post();

		if(!current_user_can('moderate_comments'))
			$this->main_query->comments_args = array('status' => 'approve');

		$comments = $this->main_query->get_comments();

		$comments_limit = $this->main_query->comments_limit;
		$comments_page = $this->main_query->comments_page;

		$current_user_id = get_current_user_id();

		if($post) {
			setup_postdata($post);

			include($this->plugin['dir'].'views/dashboard-reader/content-single.php');
		}
		else 
			echo 0;

		if(isset($restore))
			restore_current_blog();

		die();
	}

	function dashboard_display_comments_ajax() {
		error_reporting(0);

		if(get_current_blog_id() != $this->main_query->blog_id) {
			$restore = 1;
			switch_to_blog($this->main_query->blog_id);
		}

		$comments = $this->main_query->get_comments();

		$comments_limit = $this->main_query->comments_limit;
		$comments_page = $this->main_query->comments_page;

		include($this->plugin['dir'].'views/dashboard-reader/comments.php');

		if(isset($restore))
			restore_current_blog();

		die();
	}

	function dashboard_add_get_comment_ajax() {
		error_reporting(0);

		//fix to pass level to comment
		global $msreader_comment_level;

		if(get_current_blog_id() != $this->main_query->blog_id) {
			$restore = 1;
			switch_to_blog($this->main_query->blog_id);
		}

		$comment_id = $this->main_query->add_comment();

		$this->main_query->comments_args['ID'] = $comment_id;
		$comments = $this->main_query->get_comments();

		$msreader_comment_level = $this->main_query->comment_add_data['level'];

		include($this->plugin['dir'].'views/dashboard-reader/comments.php');

		if(isset($restore))
			restore_current_blog();

		die();
	}

	function dashboard_moderate_get_comment_ajax() {
		error_reporting(0);

		if(get_current_blog_id() != $this->main_query->blog_id) {
			$restore = 1;
			switch_to_blog($this->main_query->blog_id);
		}

		$status = $this->main_query->moderate_comment();

		echo $status;

		if(isset($restore))
			restore_current_blog();	

		die();
	}



	function option_page_network() {
		include($this->plugin['dir'].'views/page-settings-network.php');
	}

	function options_page_validate_save_notices() {
		if(isset($_POST['option_page']) && $_POST['option_page'] == 'wmd_msreader_options') {
			$input = $_POST['wmd_msreader_options'];
			$validated = $input;

			update_site_option( 'wpmud_moreb_options', $validated );

			if($validated)
				echo '<div id="message" class="updated"><p>'.__( 'Successfully saved', 'wmd_msreader' ).'</p></div>';
		}
	}
}
global $wmd_msreader;
$wmd_msreader = new WMD_MSReader;