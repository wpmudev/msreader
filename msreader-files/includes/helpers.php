<?php
class WMD_MSReader_Helpers {

	var $plugin;

	function __construct($plugin) {
        $this->plugin = $plugin;
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
    
	function the_select_options($array, $current) {
		if(empty($array))
			$array = array( 1 => 'True', 0 => 'False' );

		foreach( $array as $name => $label ) {
			$selected = selected( $current, $name, false );
			echo '<option value="'.$name.'" '.$selected.'>'.$label.'</option>';
		}
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
        fwrite($handle, $data);
        fclose($handle);
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