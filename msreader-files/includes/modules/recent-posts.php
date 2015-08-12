<?php
$module = array(
	'name' => __( 'Recent Posts', 'wmd_msreader' ),
	'description' => __( 'Displays recently added posts', 'wmd_msreader' ),
	'slug' => 'recent_posts', 
	'class' => 'WMD_MSReader_Module_RecentPost',
    'global_cache' => true,
    'type' => 'query'
);

class WMD_MSReader_Module_RecentPost extends WMD_MSReader_Modules {
	function init() {
		add_filter( 'msreader_dashboard_reader_sidebar_widgets', array($this,'add_link_to_widget'), 30 );
    }

    function add_link_to_widget($widgets) {
		$widgets['reader']['data']['list'][$this->details['slug']] = $this->create_link_for_main_widget();

    	return $widgets;
    }

    function query() {
        global $wpdb;

        $limit = $this->get_limit();
        $public = $this->get_public();
        
    	$query = "
            SELECT posts.BLOG_ID AS BLOG_ID, ID, post_author, post_date, post_date_gmt, post_content, post_title
            FROM $this->db_network_posts AS posts
            INNER JOIN $this->db_blogs AS blogs ON blogs.blog_id = posts.BLOG_ID
            WHERE $public blogs.archived = 0 AND blogs.spam = 0 AND blogs.deleted = 0
            AND post_status = 'publish'
            AND post_password = ''
            ORDER BY post_date_gmt DESC
            $limit
        ";
        $query = apply_filters('msreader_'.$this->details['slug'].'_query', $query, $this->args, $limit, $public);
        $posts = $wpdb->get_results($query);

    	return $posts;
    }
}