<?php
$module = array(
	'name' => __( 'User Info', 'wmd_msreader' ),
	'description' => __( 'Displays additional information about current user in main reader page widget', 'wmd_msreader' ),
	'slug' => 'user_widget', 
	'class' => 'WMD_MSReader_Module_UserWidget',
    'can_be_default' => false
);

class WMD_MSReader_Module_UserWidget extends WMD_MSReader_Modules {

	function init() {
		add_filter( 'msreader_dashboard_reader_sidebar_widgets', array($this,'add_widget'), 20 );

        add_action('save_post', array($this,'flush_user_post_count'));
        add_action('delete_post', array($this,'flush_user_post_count'));

        add_action('admin_head', array( $this, "add_css" ));
    }

    function add_css() {
        if(is_admin()) {
            echo 
            '<style type="text/css">
                #msreader-widget-reader .user-info { padding:12px; border-bottom: 1px solid #eee; margin-bottom:6px; }
                #msreader-widget-reader a {text-decoration: none;}
                #msreader-widget-reader h3 {padding: 7px 0 10px 0; margin-bottom:7px;}
                #msreader-widget-reader .user-avatar {float: left; margin:0 12px 7px 0; width:48px; height:48px; position:relative; }
                #msreader-widget-reader .change-avatar-text {transition: opacity 0.5s ease; opacity: 0; position:absolute; top:0; left: 0; background:#fff; font-size: 12px; color:#2ea2cc; height: 37px; width:48px; text-align:center; ;padding: 11px 0 0 0; font-weight: bolder;}
                #msreader-widget-reader .change-avatar-text:hover {opacity: 0.95}
                #msreader-widget-reader ul.user-stats {clear: both; text-align:center;}
                #msreader-widget-reader ul.user-stats li {display:inline-block; width:127px; text-align:center;}
                #msreader-widget-reader h4 {margin: 15px 0 15px 0;}
                #msreader-widget-reader .user-posts {float: right; margin-top: 10px;}
                #msreader-widget-reader p {margin: 5px 0 0 0;}
            </style>';
        }
    }

    function add_widget($widgets) {
        global $current_user;
        get_currentuserinfo();
        $current_blog_id = get_current_blog_id();
        $user_sites = get_blogs_of_user( $current_user->ID );
        $primary_site = get_user_meta($current_user->ID, 'primary_blog', true);

        unset($widgets['reader']['title']);
        
        $user_site_url = isset($user_sites[$primary_site]) ? $user_sites[$primary_site]->siteurl : reset($user_sites)->siteurl;

        $user_info = array();

        if(function_exists('avatars_page_edit_blog_avatar'))
            $user_info['main']['avatar'] = '<div class="user-avatar"><a title="Change avatar" href="'.admin_url('users.php?page=user-avatar').'"><div class="change-avatar-text">'.__( 'Change Avatar', 'wmd_msreader' ).'</div>'.get_avatar($current_user->ID, 48).'</a></div>';
        else
            $user_info['main']['avatar'] = '<div class="user-avatar">'.get_avatar($current_user->ID, 48).'</div>';
        $user_info['main']['name'] = '<div class="user-name"><h3><a title="Edit profile" href="'.admin_url('profile.php').'">'.$current_user->display_name.'</a></h3></div>';
 
        //$user_info['main']['url'] = '<div class="user-site-url"><small><a title="Visit site" href="'.$user_site_url.'">'.str_replace('https://', '', str_replace('http://', '', $user_site_url)).'</a></small></div>';

        $user_info['stats']['my-sites'] = 
        '<a title="View your sites" href="'.admin_url('my-sites.php').'"><h4>'.__( 'My Sites', 'wmd_msreader' ).'</h4>
        <p>'.count($user_sites).'</p></a>';

        /*
        $user_info['stats']['my-posts'] = 
        '<a title="View your posts" href="'.$this->get_module_dashboard_url(array('author_id' => $current_user->ID), 'filter_blog_author').'"><h4>'.__( 'My Posts', 'wmd_msreader' ).'</h4>
        <p>'. $this->get_user_post_count($current_user->ID).'</p></a>';
        */

        $user_info = apply_filters('msreader_user_widget_user_info', $user_info);

        $content = '<div class="user-info">'.implode('', $user_info['main']).'<ul class="user-stats"><li>'.implode('</li><li>', $user_info['stats']).'</li></ul></div>'; 

        $widgets['reader']['data'] = array_merge(array('html' => $content), $widgets['reader']['data']);

    	return $widgets;
    }

    function get_user_post_count($user_id = 0) {
        global $wpdb;

        if(!$user_id)
            $user_id = get_current_user_id();

        $user_post_count = wp_cache_get('user_post_count_'.$user_id, 'msreader_global');

        if(!$user_post_count) {
            $query = "
                SELECT count(*)
                FROM $this->db_network_posts AS posts
                INNER JOIN $this->db_blogs AS blogs ON blogs.blog_id = posts.BLOG_ID
                WHERE blogs.archived = 0 AND blogs.spam = 0 AND blogs.deleted = 0
                AND post_status = 'publish'
                AND post_password = ''
                AND post_author = $user_id
            ";
            $query = apply_filters('msreader_'.$this->details['slug'].'_user_post_count', $query, $this->args, $user_id);
            $user_post_count = $wpdb->get_var($query);

            wp_cache_set('user_post_count_'.$user_id, $user_post_count, 'msreader_global', 14400);
        }

        return $user_post_count;
    }
    function flush_user_post_count() {
        $user_id = get_current_user_id();

        wp_cache_delete('user_post_count_'.$user_id, 'msreader_global');
    }
}