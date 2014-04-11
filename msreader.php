<?php
/*
Plugin Name: WMD MSReader
Plugin URI:
Description: Lets msreader doing the plugins in style!
Version: 0.1
Network: false
Text Domain: wmd_msreader
Author: WPMUDEV
Author URI: http://premium.WMD.org/
WDP ID: 0
*/

define( 'MSREADER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

class WMD_MSReader {
	var $plugin;

	var $available_modules;
	var $modules;

	function __construct() {
		$this->init();

		add_action( 'plugins_loaded', array($this,'plugins_loaded') );

		register_activation_hook($this->plugin_main_file, array($this, 'activate'));
		register_deactivation_hook($this->plugin_main_file, array($this, 'deactivate'));

		add_action('wp_enqueue_scripts', array($this,'register_scripts_styles_public'));

		add_action('admin_enqueue_scripts', array($this,'register_scripts_styles_admin'));

		add_action('admin_init', array($this,'admin_init') );

		add_action( 'admin_menu', array($this,'admin_page') );
		add_action( 'network_admin_menu', array($this,'admin_page') );
	}

    function init() {
    	//set up all the default options
    	$this->plugin['debug'] = 0;
    	$this->plugin['mode'] = 'soft';

		$this->plugin['main_file'] = __FILE__;
		$this->plugin['dir'] = MSREADER_PLUGIN_DIR.'msreader-files/';
		$this->plugin['dir_url'] = 'msreader-files/'.plugin_dir_url($this->plugin['main_file']);
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
		include_once($this->plugin['dir'].'includes/query.php');
		$this->load_modules();
    }

	function load_modules() {
		global $msreader_modules;
		$required_module_info = array('slug', 'class', 'name', 'description');

		if($this->plugin['mode'] == 'soft') {
			$modules = array(
				$this->plugin['dir'].'includes/modules/my-posts.php',
				$this->plugin['dir'].'includes/modules/trending-tags.php'
			);
		}
		else {
			//search the dir for files
			$location = $this->plugin['dir'].'modules/';

			$modules = array();
			if ( !is_dir( $location ) )
				return;

			if ( ! $dh = opendir( $dir ) )
				return;

			while ( ( $module = readdir( $dh ) ) !== false ) {
				if ( substr( $module, -4 ) == '.php' )
				$modules[] = $dir . $module;
			}
			closedir( $dh );
			sort( $modules );
		}

		foreach ($modules as $file) {
			//simple global array to pass the module data around
			global $module;
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
				$this->available_modules[$module['slug']] = $module;
				$this->modules[$module['slug']] = $msreader_modules[$module['slug']] = new $module['class'];
			}
		}
	}

	function activate() {
        //save default options
		if($this->plugin['site_options'] == 0)
			update_option('wmd_msreader_options', $this->plugin['default_site_options']);
	}

	function deactivate() {
	}

	function uninstall() {
		if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
			exit ();
	}

	function plugins_loaded() {
		load_plugin_textdomain( 'wmd_msreader', false, $this->plugin['rel'].'languages/' );
	}

	function admin_init(){
		register_setting( 'wmd_msreader_options', 'wmd_msreader_options', array($this,'validate_options') );
		add_action( 'admin_notices', array($this,'options_page_notices') );
		add_action( 'network_admin_notices', array(&$this,'options_page_validate_save_notices') );
	}

	function register_scripts_styles_public() {
		wp_enqueue_script( 'jquery' );

		wp_register_script('wmd-msreader-public', $this->plugin['dir_url'].'js/public.js', array('jquery'));
		wp_enqueue_script('wmd-msreader-public');

		wp_register_style('wmd-msreader-public', $this->plugin['dir_url'].'css/public.css');
		wp_enqueue_style('wmd-msreader-public');
	}

	function register_scripts_styles_admin($hook) {
		wp_register_script('wmd-msreader-admin', $this->plugin['dir_url'].'js/admin.js', array('jquery'));
		wp_enqueue_script('wmd-msreader-admin');

		wp_register_style('wmd-msreader-admin', $this->plugin['dir_url'].'css/admin.css');
		wp_enqueue_style('wmd-msreader-admin');
	}



	function admin_page() {
		add_options_page('MSReader', 'MSReader', 'manage_options', basename($this->plugin['main_file']), array($this,'option_page'));
		add_submenu_page('settings.php', 'MSReader', 'MSReader', 'manage_options', basename($this->plugin['main_file']), array($this,'option_page_network'));
	}

	function option_page() {
		//get details to display
		$query = new WMD_MSReader_Query;
		$query_details = $query->get_query_details();
		$posts = $query->get_posts();

		include($this->plugin['dir'].'views/dashboard_reader.php');
	}

	function options_page_notices() {
		settings_errors( 'wmd_msreader_options' );
	}

	function validate_options($input) {
		add_settings_error( 'wmd_msreader_options', 'wmd_msreader_options_updated', __( 'Successfully saved', 'wmd_msreader' ), 'updated' );
		return $input;
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