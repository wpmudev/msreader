<?php
$module = array(
	'name' => __( 'My Sites', 'wmd_msreader' ),
	'description' => __( 'Displays posts from users sites', 'wmd_msreader' ),
	'slug' => 'my_sites', 
	'class' => 'WMD_MSReader_Module_MySites'
);

class WMD_MSReader_Module_MySites extends WMD_MSReader_Modules {
	function init() {
		add_filter( 'msreader_dashboard_reader_sidebar_widgets', array($this,'add_link_to_widget'), 50 );
    }

    function add_link_to_widget($widgets) {
		$widgets['reader']['data']['list'][] = $this->create_link_for_main_widget();

    	return $widgets;
    }

    function query() {
        $current_user_id = get_current_user_id();
        $limit = $this->get_limit();

        //get sites of current user
        $user_sites = get_blogs_of_user( $current_user_id );
        $user_sites_ids = array();
        foreach ($user_sites as $user_site)
            $user_sites_ids[] = $user_site->userblog_id;
        $user_sites_ids = implode(',', $user_sites_ids);

    	$query = "
            SELECT posts.BLOG_ID AS BLOG_ID, ID, post_author, post_date, post_date_gmt, post_content, post_title
            FROM $this->db_network_posts AS posts
            INNER JOIN $this->db_blogs AS blogs ON blogs.blog_id = posts.BLOG_ID
            WHERE blogs.archived = 0 AND blogs.spam = 0 AND blogs.deleted = 0
            AND post_status = 'publish'
            AND post_password = ''
            AND posts.BLOG_ID IN($user_sites_ids)
            ORDER BY post_date_gmt DESC
            $limit
        ";
        $query = apply_filters('msreader_'.$this->details['slug'].'_query', $query, $this->args, $limit, $user_sites_ids);
        $posts = $this->wpdb->get_results($query);

    	return $posts;
    }
}