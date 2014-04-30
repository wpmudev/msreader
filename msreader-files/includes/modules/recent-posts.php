<?php
$module = array(
	'name' => __( 'Recent Posts', 'wmd_msreader' ),
	'description' => __( 'Displays my posts', 'wmd_msreader' ),
	'slug' => 'recent-posts', 
	'class' => 'WMD_MSReader_Module_RecentPost'
);

class WMD_MSReader_Module_RecentPost extends WMD_MSReader_Modules {
	function init() {
		add_filter( 'msreader_dashboard_reader_sidebar_widgets', array($this,'add_link_to_widget'), 40 );
    }

    function add_link_to_widget($widgets) {
		$widgets['reader']['data']['links'][] = $this->create_link_for_main_widget();

    	return $widgets;
    }

    function query() {
        $current_user_id = get_current_user_id();
        $limit = $this->get_limit();
        
    	$query = "
            SELECT BLOG_ID, ID, post_author, post_date_gmt, post_content, post_title
            FROM $this->db_network_posts
            WHERE post_status = 'publish'
            ORDER BY post_date_gmt DESC
            $limit
        ";
        $posts = $this->wpdb->get_results($query);

    	return $posts;
    }
}