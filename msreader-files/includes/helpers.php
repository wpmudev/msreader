<?php
class WMD_MSReader_Helpers {

	var $plugin;

	function __construct($plugin) {
        $this->plugin = $plugin;
    }

    //plugin specific
	function is_module_enabled($slug, $options = 0) {
		$options = $options ? $options : $this->plugin['site_options'];
		return (isset($this->plugin['site_options']['modules']) && is_array($options['modules']) && array_key_exists($slug, $options['modules'])) || in_array($slug, apply_filters('msreader_activate_modules', array())) ? true : false;
	}

	//is public only
	function is_public_only() {
		return $this->plugin['site_options']['posts_from'] == 'public' ? true : false;
	}

	function get_default_module() {
		return apply_filters('msreader_default_module', $this->plugin['site_options']['default_module']);
	}

	//general
	function is_page_link_active($link, $soft = 0) {
		$link_query = parse_url($link);
		$link_query = isset($link_query['query']) ? $link_query['query'] : '';
		if(isset($_POST['current_url'])) {
			$current_query = parse_url($_POST['current_url']);
			$current_query = isset($current_query['query']) ? $current_query['query'] : '';
		}
		else
			$current_query = $_SERVER['QUERY_STRING'];
		if($soft && strpos($current_query, $link_query) !== false)
			return true;
		elseif($current_query == $link_query)
			return true;
		else
			return false;
	}
	function get_user_roles_per_blog($user_id) {
		global $wpdb;
		$msreader_edublogs_db_user_meta = $wpdb->base_prefix.'usermeta';

		$query = $wpdb->prepare("
		    SELECT meta_key, meta_value
		    FROM $msreader_edublogs_db_user_meta
		    WHERE user_id = %d
		    AND meta_key LIKE %s
		", $user_id, 'wp%_capabilities');
		$user_roles_prepare = $wpdb->get_results($query, ARRAY_A);

		$user_roles = array();

		foreach ($user_roles_prepare as $user_role_prepare) {
		    $blog_id = explode('_', $user_role_prepare['meta_key']);
		    $blog_id = count($blog_id) > 2 ? $blog_id[1] : 1;

		    $meta_value = maybe_unserialize($user_role_prepare['meta_value']);
		    foreach ($meta_value as $role => $status)
		        if($status == true)
		            $user_roles[$blog_id][] = $role;
		};

		return $user_roles;
	}

	function array_sort_by_sub_title($a, $b) {
		return strcmp($a['title'], $b['title']);
	}
    
	function the_select_options($array, $current, $echo = 1) {
		if(empty($array))
			$array = array( 1 => __('True', 'wmd_msreader'), 0 => __('False', 'wmd_msreader') );

		$return = '';
		foreach( $array as $name => $label ) {
			$selected = selected( $current, $name, false );
			$return .= '<option value="'.$name.'" '.$selected.'>'.$label.'</option>';
		}

		if($echo)
			echo $return;
		else
			return $return;
	}

	function ensure_boolen($array) {
		$return = array();

		foreach ( $array as $name => $element ) {
			if( $element == '1' || $element == '0' )
				$return[$name] = (bool)$element;
			else
				$return[$name] = $element;
		}

		return $return;
	}

	function get_db_prefix() {
		global $wpdb;

		if ( !empty($wpdb->base_prefix) ) {
			$db_prefix = $wpdb->base_prefix;
		} else {
			$db_prefix = $wpdb->prefix;
		}

		return $db_prefix;
	}

    function write_log($message) {
    	global $wmd_msreader;

        if(!$this->plugin['debug'])
            return false;

        $file = $this->plugin['dir'] . "/debug.log";

        $handle = fopen( $file, 'ab' );
        $data = date( "[Y-m-d H:i:s]" ) . $message . "\r\n";
        @fwrite($handle, $data);
        @fclose($handle);
    }
}

//Compatibility with older PHP
if (!function_exists('array_replace_recursive')) {
	function array_replace_recursive() {
	    $arrays = func_get_args();

	    $original = array_shift($arrays);

	    foreach ($arrays as $array) {
	        foreach ($array as $key => $value) {
	            if (is_array($value)) {
	                $original[$key] = array_replace_recursive($original[$key], $array[$key]);
	            }

	            else {
	                $original[$key] = $value;
	            }
	        }
	    }

	    return $original;
	}
}