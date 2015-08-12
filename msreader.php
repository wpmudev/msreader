<?php
/*
Plugin Name: Reader
Plugin URI: https://premium.wpmudev.org/project/reader/
Description: Enabled reader that lets users browse posts inside network
Version: 1.2
Network: false
Text Domain: wmd_msreader
Author: WPMU DEV
Author URI: http://premium.wpmudev.org/
WDP ID: 910241
*/

define( 'MSREADER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

class WMD_MSReader {
	var $plugin;

	var $available_modules;
	var $modules;

	var $main_query;

	var $helpers;

	function __construct() {
		//loads dashboard stuff
		global $wpmudev_notices;
		$wpmudev_notices[] = array( 'id'=> 910241, 'name'=> 'Reader', 'screens' => array( 'dashboard_page_msreader', 'settings_page_msreader-network' ) );
		if(file_exists(MSREADER_PLUGIN_DIR.'dash-notice/wpmudev-dash-notification.php'))
			include_once(MSREADER_PLUGIN_DIR.'dash-notice/wpmudev-dash-notification.php');

		add_action('plugins_loaded', array($this,'plugins_loaded'));

		$this->setup();
		
		register_activation_hook( $this->plugin['main_file'], array($this, 'activate'));
		
		add_action('init', array($this,'load_modules'), 0);
		
		add_action('network_admin_menu', array($this,'network_admin_page'));
		add_action( 'network_admin_notices', array(&$this,'options_page_validate_save_notices') );

		add_action('admin_enqueue_scripts', array($this,'register_scripts_styles_admin'));

		//enable stuff if at least one module is active
		if(isset($this->plugin['site_options']['modules']) && is_array($this->plugin['site_options']['modules']) && count($this->plugin['site_options']['modules']) > 0) {
			//initialize what we need
			add_action('init', array($this,'init') );

			//AJAX actions for dashboards
			add_action('wp_ajax_dashboard_display_posts_ajax', array($this, 'dashboard_display_posts_ajax'));
			add_action('wp_ajax_dashboard_display_post_ajax', array($this, 'dashboard_display_post_ajax'));
			add_action('wp_ajax_dashboard_publish_post', array($this, 'dashboard_publish_post'));
			add_action('wp_ajax_dashboard_display_comments_ajax', array($this, 'dashboard_display_comments_ajax'));
			add_action('wp_ajax_dashboard_add_get_comment_ajax', array($this, 'dashboard_add_get_comment_ajax'));
			add_action('wp_ajax_dashboard_moderate_get_comment_ajax', array($this, 'dashboard_moderate_get_comment_ajax'));
			add_action('wp_ajax_dashboard_get_reader_sidebar_ajax', array($this, 'dashboard_get_reader_sidebar_ajax'));
		}
	}

    function setup() {
    	global $msreader_helpers, $msreader_available_modules;

    	//set up all the default options
    	$this->plugin['debug'] = 0;

		$this->plugin['main_file'] = __FILE__;
		$this->plugin['dir'] = MSREADER_PLUGIN_DIR.'msreader-files/';
		$this->plugin['dir_url'] = plugin_dir_url($this->plugin['main_file']).'msreader-files/';
		$this->plugin['basename'] = plugin_basename($this->plugin['main_file']);
		$this->plugin['rel'] = dirname($this->plugin['basename']).'/';

		$this->plugin['registered_modules'] = apply_filters('msreader_register_modules', array(
			'follow' => $this->plugin['dir'].'includes/modules/follow.php',
			'recent_posts' => $this->plugin['dir'].'includes/modules/recent-posts.php',
			'my_posts' => $this->plugin['dir'].'includes/modules/my-posts.php',
			'my_sites' => $this->plugin['dir'].'includes/modules/my-sites.php',
			'popular_posts' => $this->plugin['dir'].'includes/modules/popular-posts.php',
			'featured_posts' => $this->plugin['dir'].'includes/modules/featured-posts.php',
			'trending_tags' => $this->plugin['dir'].'includes/modules/trending-tags.php',
			'filter_blog_author' => $this->plugin['dir'].'includes/modules/filter-blog-author.php',
			'search' => $this->plugin['dir'].'includes/modules/search.php',
			'user_widget' => $this->plugin['dir'].'includes/modules/user-widget.php',
			'rss_feeds' => $this->plugin['dir'].'includes/modules/rss-feeds.php',
			'widget_recent_posts' => $this->plugin['dir'].'includes/modules/widget-recent-posts.php'
		));

		$this->plugin['default_site_options'] = array(
			'location' => 'add_under_dashboard',
			'name' => __( 'Reader', 'wmd_msreader' ),
			'posts_from' => 'public',
			'default_module' => 'recent_posts',
			'modules' => array(),
			'modules_options' => array()
		);
		//enable all standard modules by default
		foreach ($this->plugin['registered_modules'] as $slug => $localization) {
			$this->plugin['default_site_options']['modules'][$slug] = 'true';
		}
		$this->plugin['default_site_options'] = apply_filters('msreader_site_default_options', $this->plugin['default_site_options']);

		$this->plugin['site_options'] = get_site_option('wmd_msreader_options', $this->plugin['default_site_options']);
		//lets predefine new default options
		if(!isset($this->plugin['site_options']['posts_from']))
			$this->plugin['site_options']['posts_from'] = 'public';

		//load necessary files
    	include_once($this->plugin['dir'].'includes/modules.php');
    	include_once($this->plugin['dir'].'includes/helpers.php');

		//load helper functions
		$this->helpers = $msreader_helpers = new WMD_MSReader_Helpers($this->plugin);

		//load modules
		$required_module_info = array('slug', 'class', 'name');
		foreach ($this->plugin['registered_modules'] as $file) {
			//simple global array to pass the module data around
			$module = array();

			if(!file_exists($file))
				continue;
			
			include_once( $file );

			//check if it has all the data
			foreach ($required_module_info as $info)
				if(!array_key_exists($info, $module)) {
					$break = 1;
					break;
				}
			if(isset($break))
				break;

			if(class_exists($module['class'])) {
				$this->available_modules[$module['slug']] = $msreader_available_modules[$module['slug']] = $module;
				
				//load default module options
				$module['default_options'] = isset($module['default_options']) ? $module['default_options'] : array();
				$module['default_options'] = apply_filters('msreader_default_module_options_'.$module['slug'], $module['default_options']);
				$this->plugin['site_options']['modules_options'][$module['slug']] = isset($this->plugin['site_options']['modules_options'][$module['slug']]) ? array_merge($module['default_options'], $this->plugin['site_options']['modules_options'][$module['slug']]) : $module['default_options'];
			}

			//sort modules by slug
			ksort($this->available_modules);
		}
    }

    function load_modules() {
		global $msreader_modules;

    	$modules = apply_filters('msreader_load_modules', $this->available_modules);

    	foreach($modules as $key => $module) {
			if($this->helpers->is_module_enabled($module['slug']))
				$this->modules[$module['slug']] = $msreader_modules[$module['slug']] = new $module['class']($this->plugin['site_options']['modules_options'][$module['slug']], $module['slug']);
    	}

		//filter options after module loading in case module wants to modify it
		$this->plugin['site_options'] = apply_filters('msreader_site_options', $this->plugin['site_options']);
    }

	function init() {
		//add welcome notice
		add_action( 'all_admin_notices', array($this,'welcome_notice') );

		//check if post-indexer is active
		if(!function_exists('post_indexer_post_insert_update') && !class_exists('postindexermodel')) {
			add_action('all_admin_notices', array($this,'post_indexer_notice'));
			return;
		}
		

		//setup main query only on correct pages. Passes $_REQUEST stuff to query model and than query model passes to loaded module
		if((is_admin() && isset($_GET['page']) && $_GET['page'] == 'msreader.php') || (defined('DOING_AJAX') && isset($_REQUEST['source']) && $_REQUEST['source'] == 'msreader') || apply_filters('msreader_requested', 0)) {
			global $msreader_main_query;
			include_once($this->plugin['dir'].'includes/query.php');

			$this->main_query = $msreader_main_query = new WMD_MSReader_Query();

			//turn arg into array
			if(isset($_REQUEST['args']) && !is_array($_REQUEST['args']))
				$_REQUEST['args'] = array($_REQUEST['args']);

			//pass available parameters to main query
			$parameters_to_pass = 
			array(
				'numeric' => array(
					'page', 'limit', 'blog_id', 'post_id', 'comments_page', 'comments_limit', 'last_date'
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

			$default_module = apply_filters('msreader_default_module', $this->plugin['site_options']['default_module']);
			$dynamicly_disabled_modules = apply_filters('msreader_dynamicly_disabled_modules', array());

			//set up which module to display
			if(isset($_REQUEST['module']) && array_key_exists($_REQUEST['module'], $this->modules) && !in_array($_REQUEST['module'], $dynamicly_disabled_modules))
				$load_module = $_REQUEST['module'];
			elseif($this->helpers->is_module_enabled($default_module) && isset($this->modules[$default_module]))
					$load_module = $default_module;
			else {
				reset($this->modules);
				$load_module = key($this->modules);
			}

			$this->main_query->load_module($this->modules[$load_module], 1);
		}

		//add menu pages
		add_action('admin_menu', array($this,'admin_page') );
		add_action('admin_init', array($this,'replace_dashboard_fix'), 101 );

		//add profile option so user can disable reader being dashboard
		add_action( 'profile_personal_options', array($this, 'extra_profile_fields') );
		add_action( 'edit_user_profile', array($this, 'extra_profile_fields') );
		add_action( 'personal_options_update', array($this, 'update_extra_profile_fields') );
		add_action( 'edit_user_profile_update', array($this, 'update_extra_profile_fields') );

		//add comment filter related stuff so private comments are visible
		add_filter('comments_clauses', array($this, 'filter_private_comments') );
	}


	function activate() {
	   	if(!is_multisite())
	    	trigger_error(sprintf(__('Multisite Theme Manager only works in multisite configuration. You can read more about it <a href="%s" target="_blank">here</a>.', 'wmd_prettythemes'), 'http://codex.wordpress.org/Create_A_Network'),E_USER_ERROR);
 		else {
        //save default options
		if($this->plugin['site_options'] == 0)
			update_site_option('wmd_msreader_options', $this->plugin['default_site_options']);
	}
	}

	function plugins_loaded() {
		load_plugin_textdomain( 'wmd_msreader', false, $this->plugin['rel'].'languages/' );
	}

	function post_indexer_notice() {
		global $pagenow;

		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 0;
		if ((($page === 'msreader.php' && $pagenow == 'settings.php') || $pagenow == 'plugins.php') && is_super_admin() )
			echo '<div class="error"><p>'.__('<strong>Reader needs Post Indexer plugin to work.</strong> Currently it is not active. You can get this plugin <a href="https://premium.wpmudev.org/project/post-indexer/">here</a>.', 'wmd_prettyplugins').'</p></div>';
	}

	function welcome_notice() {
		global $pagenow;

		$desired_notice_version = 1;
		$notice_version = get_site_option( 'msreader_notice_viewed', 0 );
		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 0;
		
		if($notice_version < $desired_notice_version && $page === 'msreader.php' && $pagenow == 'settings.php') {
			$notice_version = $desired_notice_version;
			update_site_option( 'msreader_notice_viewed', $notice_version );
		}
		elseif($notice_version < $desired_notice_version && $pagenow == 'plugins.php' && is_super_admin() )
			echo '<div class="updated"><p>'.sprintf(__('Reader plugin has been activated. It can be configured  by going to Network Admin > Settings > <a href="%s">Reader</a>', 'wmd_prettyplugins'), admin_url('network/settings.php?page=msreader.php')).'</p></div>';
	}

	function register_scripts_styles_admin($hook) {
		if($hook == 'dashboard_page_msreader') {
			wp_register_style('wmd-msreader-admin', $this->plugin['dir_url'].'css/admin.css', array(), 38);
			wp_enqueue_style('wmd-msreader-admin');

			wp_register_script('wmd-msreader-admin', $this->plugin['dir_url'].'js/admin.js', array('jquery'), 38);
			wp_enqueue_script('wmd-msreader-admin');

			wp_localize_script( 'wmd-msreader-admin', 'msreader_main_query', array(
				'module' => $this->main_query->module->details['slug'],
				'page' => $this->main_query->page,
				'limit' => $this->main_query->limit,
				'args' => $this->main_query->args,
				'last_date' => $this->main_query->last_date,
				'comments_page' => $this->main_query->comments_page,
				'comments_limit' => $this->main_query->comments_limit,
				'comments_args' => $this->main_query->comments_args,
				'comment_add_data' => $this->main_query->comment_add_data
			) );

			wp_localize_script( 'wmd-msreader-admin', 'msreader_translation', array(
				'confirm' => __('Are you sure you want to do this?', 'wmd_msreader'),
				'confirm_child' => __('Are you sure you want to do this? This action will also affect all replies for this comment.', 'wmd_msreader'),
				'approve' => __('Approve', 'wmd_msreader'),
				'unapprove' => __('Unapprove', 'wmd_msreader'),
			) );
		}
		elseif($hook == 'settings_page_msreader') {
			wp_register_style('wmd-msreader-network-admin', $this->plugin['dir_url'].'css/network-admin.css', array(), 20);
			wp_enqueue_style('wmd-msreader-network-admin');

			wp_register_script('wmd-msreader-network-admin', $this->plugin['dir_url'].'js/network-admin.js', array('jquery'), 20);
			wp_enqueue_script('wmd-msreader-network-admin');
		}
	}

	function replace_dashboard_fix(){
		global $pagenow;

		//Fix for first standard menu sub item being replaced
		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 0;

		$user_location_setting = get_user_meta( get_current_user_id(), 'wmd_msreader_options_location', true );
		$location = $user_location_setting ? $user_location_setting : $this->plugin['site_options']['location'];

		if($page === 'msreader.php' && $pagenow == 'admin.php' || ($location == 'replace_dashboard_home' && $pagenow == 'index.php' && !$page && !is_network_admin() && !defined('IFRAME_REQUEST'))) {
			wp_redirect( admin_url('index.php?page=msreader.php') );
			exit();
		}
	}


	function admin_page() {
		global $menu;
		
		$user_location_setting = get_user_meta( get_current_user_id(), 'wmd_msreader_options_location', true );
		$location = $user_location_setting ? $user_location_setting : $this->plugin['site_options']['location'];
		$menu_name = __(apply_filters('msreader_menu_name', stripslashes($this->plugin['site_options']['name'])), 'wmd_msreader' );
		if($location == 'replace_dashboard_home') {
			global $submenu;
				
			remove_submenu_page('index.php', 'index.php');

			add_dashboard_page($menu_name, $menu_name, 'read', basename($this->plugin['main_file']), array($this,'reader_page'));

			if(isset($submenu['index.php'])) {
				foreach ($submenu['index.php'] as $key => $value) {
					if($value[2] == 'msreader.php') {
						$theme_page = $submenu['index.php'][$key];
						unset($submenu['index.php'][$key]);
						break;
					}
				}
				
				$submenu['index.php'] = array_merge(array('5' => $theme_page), $submenu['index.php']);
			}
		}
		else
			add_dashboard_page($menu_name, $menu_name, 'read', basename($this->plugin['main_file']), array($this,'reader_page'));
	}

	function extra_profile_fields($user) {
	    $reader_location_options = array('' => __( 'Default', 'wmd_msreader' ), 'replace_dashboard_home' => __( 'Admin dashboard frontpage', 'wmd_msreader' ), 'add_under_dashboard' => __( 'Admin dashboard sub-menu', 'wmd_msreader' ));
	    $current = get_user_meta( $user->ID, 'wmd_msreader_options_location', true );
	    ?>
		<table class="form-table">
			<tbody>
				<tr>
					<th><label for="wmd_msreader_options_location"><?php echo _e( 'Reader location', 'wmd_msreader' ); ?></label></th>
					<td>
						<select name="wmd_msreader_options_location" id="wmd_msreader_options_location">
							<?php $this->helpers->the_select_options($reader_location_options, $current); ?>
						</select>
					</td>
				</tr>
			</tbody>
		</table>
	    <?php
	}

	function update_extra_profile_fields($user_id) {
		if ( current_user_can('edit_user',$user_id) )
			update_user_meta($user_id, 'wmd_msreader_options_location', $_POST['wmd_msreader_options_location']);
	}

	function network_admin_page() {
		add_submenu_page('settings.php', __('Reader', 'wmd_msreader'), __('Reader', 'wmd_msreader'), 'manage_site_options', basename($this->plugin['main_file']), array($this,'option_page_network'));
	}

	function reader_page() {
		//get details to display
		$query_details = $this->main_query->get_query_details();
		$empty_message = $this->main_query->module->get_empty_message();

		include($this->plugin['dir'].'views/dashboard-reader/index.php');
	}

	function filter_private_comments($results) {
		//if($pagenow == 'edit-comments.php' && $_GET['comment_status'] == 'private')
		//	$results['where'] = str_replace("( comment_approved = '0' OR comment_approved = '1' )", "( comment_approved = 'private' )", $results['where']);
		if(defined( 'DOING_AJAX' ) && isset($_REQUEST['source']) && $_POST['source'] == 'msreader' && isset($_REQUEST['post_id']) && current_user_can('edit_post', $_REQUEST['post_id']))
			$results['where'] = str_replace("( comment_approved = '0' OR comment_approved = '1' )", "( comment_approved = '0' OR comment_approved = '1' OR comment_approved = 'private' )", $results['where']);

		return $results;
	}

	function dashboard_display_posts_ajax() {
		error_reporting(0);

		$posts = $this->main_query->get_posts();

		if(is_array($posts) && count($posts) > 0) {
			global $post;

			do_action('msreader_dashboard_post_list_before_posts', $posts);

			foreach ($posts as $post) {
				setup_postdata($post);
				
				include($this->plugin['dir'].'views/dashboard-reader/content-post.php');
			}
		}
		elseif($posts != 'error' && !is_array($posts)) {
			$html = $posts;
			include($this->plugin['dir'].'views/dashboard-reader/content-page.php');
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

		if($post) {
			global $more;
			$more = 1;

			setup_postdata($post);

			//set up everything else
			if(!current_user_can('moderate_comments') && !current_user_can('edit_post', $comment->comment_post_ID))
				$this->main_query->comments_args = array('status' => 'approve');

			$comments = $this->main_query->get_comments();

			$comments_limit = $this->main_query->comments_limit;
			$comments_page = $this->main_query->comments_page;

			$current_user_id = get_current_user_id();

			include($this->plugin['dir'].'views/dashboard-reader/content-single.php');
		}
		else 
			echo 0;

		if(isset($restore))
			restore_current_blog();

		die();
	}

	function dashboard_publish_post() {
		error_reporting(0);

		check_ajax_referer( 'publish_post', 'nonce' );

		if(get_current_blog_id() != $this->main_query->blog_id) {
			$restore = 1;
			switch_to_blog($this->main_query->blog_id);
		}

		$status = $this->main_query->publish_post();

		echo ($status > 0) ? __( 'Published', 'wmd_msreader' ) : 0;

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

		ob_start();
		include($this->plugin['dir'].'views/dashboard-reader/comments.php');
		$html = ob_get_contents();
		ob_end_clean();

		echo $html;

		if(isset($restore))
			restore_current_blog();

		die();
	}

	function dashboard_add_get_comment_ajax() {
		error_reporting(0);

		check_ajax_referer( 'add_comment', 'nonce' );

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

		check_ajax_referer( 'moderate_comment', 'nonce' );

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

	function dashboard_get_reader_sidebar_ajax() {
		ob_start();
		include($this->plugin['dir'].'views/dashboard-reader/sidebar.php');
		$html = ob_get_contents();
		ob_end_clean();

		echo $html;
		die();
	}



	function option_page_network() {
		include($this->plugin['dir'].'views/page-settings-network.php');
	}

	function options_page_validate_save_notices() {
		if(isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'wmd_msreader_options-options') && isset($_POST['option_page']) && $_POST['option_page'] == 'wmd_msreader_options') {
			$input = $_POST['wmd_msreader_options'];
			$this->plugin['site_options'] = $validated = $input;

			update_site_option( 'wmd_msreader_options', $validated );

			if($validated)
				echo '<div id="message" class="updated"><p>'.__( 'Successfully saved', 'wmd_msreader' ).'</p></div>';
		}
	}
}
global $wmd_msreader;
$wmd_msreader = new WMD_MSReader;