<?php
$module = array(
	'name' => __( 'Popular Posts', 'wmd_msreader' ),
	'description' => __( 'Displays popular posts', 'wmd_msreader' ),
	'slug' => 'popular_posts', 
	'class' => 'WMD_MSReader_Module_PopularPost',
    'global_cache' => true,
    'default_options' => array(
        'minimum_comment_count' => 5
    ),
    'type' => 'query'
);

class WMD_MSReader_Module_PopularPost extends WMD_MSReader_Modules {
	function init() {
		add_filter( 'msreader_dashboard_reader_sidebar_widgets', array($this,'add_link_to_widget'), 20 );

        add_filter( 'msreader_module_options_'.$this->details['slug'], array($this,'add_options_html'), 10, 2 );
    }

    function add_link_to_widget($widgets) {
		$widgets['reader']['data']['list'][$this->details['slug']] = $this->create_link_for_main_widget();

    	return $widgets;
    }

    function query() {
        global $wpdb;
        
        $limit = $this->get_limit();
        $limit_sample = $this->get_limit($this->limit_sample,1);
        $public = $this->get_public();

        $minimum_comment_count = $this->options['minimum_comment_count'] > 0 ? $this->options['minimum_comment_count']-1 : 0;
        
    	$query = "
            SELECT BLOG_ID, ID, post_author, post_date_gmt, post_date, post_content, post_title, comment_count
            FROM (
                SELECT posts.BLOG_ID AS BLOG_ID, ID, post_author, post_date, post_date_gmt, post_content, post_title, comment_count
                FROM $this->db_network_posts AS posts
                INNER JOIN $this->db_blogs AS blogs ON blogs.blog_id = posts.BLOG_ID
                WHERE $public blogs.archived = 0 AND blogs.spam = 0 AND blogs.deleted = 0
                AND post_status = 'publish'
                AND post_password = ''
                ORDER BY post_date_gmt DESC
                $limit_sample
            ) a
            WHERE comment_count > $minimum_comment_count
            ORDER BY post_date_gmt DESC
            $limit
        ";
        $query = apply_filters('msreader_'.$this->details['slug'].'_query', $query, $this->args, $limit, $public, $limit_sample, $minimum_comment_count);
        $posts = $wpdb->get_results($query);

    	return $posts;
    }

    function add_options_html($blank, $options) {
        return '
            <label for="wmd_msreader_options[name]">'.__( 'Minimum number of comments to the post to treat it as popular', 'wmd_msreader' ).':</label><br/>
            <input type="number" min="1" class="small-text ltr" name="wmd_msreader_options[modules_options]['.$this->details['slug'].'][minimum_comment_count]" value="'.$options['minimum_comment_count'].'" />
        ';
    }
}