<?php
$module = array(
	'name' => __( 'Trending Tags', 'wmd_msreader' ),
	'description' => __( 'Displays trending tags', 'wmd_msreader' ),
	'slug' => 'trending_tags', 
	'class' => 'WMD_MSReader_Module_TrendingTags',
    'can_be_default' => false,
    'default_options' => array(
        'widget_links_limit' => 5
    )
);

class WMD_MSReader_Module_TrendingTags extends WMD_MSReader_Modules {

	function init() {
		add_filter( 'msreader_dashboard_reader_sidebar_widgets', array($this,'add_widget'), 20 );

        add_filter( 'msreader_module_options_'.$this->details['slug'], array($this,'add_options_html'), 10, 2 );
    }

    function add_widget($widgets) {
        $limit_sample_total = 100;
    	$limit_sample = $this->get_limit($limit_sample_total, 1);
    	$limit_links = $this->options['widget_links_limit']; 
    	$limit = $this->get_limit($limit_links, 1);

		$query_hash = md5($this->cache_init.$this->details['slug'].$limit_sample_total.$limit_links);
		$cache_group = 'msreader_global';
		$top_tags = wp_cache_get('widget_'.$query_hash, $cache_group);

		if(!$top_tags) {
	    	$query = "
	            SELECT id, slug, name, count(id) AS count
	            FROM (
		            SELECT a.term_taxonomy_id AS id, c.slug AS slug, c.name AS name
		            FROM $this->db_network_term_rel AS a
		            INNER JOIN $this->db_network_term_tax AS b ON b.term_taxonomy_id = a.term_taxonomy_id
		            INNER JOIN $this->db_network_terms AS c ON c.term_id = a.term_taxonomy_id
		            WHERE b.taxonomy = 'post_tag'
		            $limit_sample
	            ) a
	            GROUP BY id
	            ORDER BY count DESC
	            $limit
	        ";
            $query = apply_filters('msreader_'.$this->details['slug'].'_widget', $query, $this->args, $limit, $limit_sample);
	        $top_tags = $this->wpdb->get_results($query, ARRAY_A);

	        wp_cache_set('query_'.$query_hash, $top_tags, $cache_group, 3600);
    	}

        //prepare trending tags links
        $top_tags_ready = array();
        foreach ($top_tags as $tag)
        	$top_tags_ready[] = array('args' => $tag['id'],'title' => $tag['name']);

    	$widgets['trending-tags'] = $this->create_list_widget($top_tags_ready);

    	return $widgets;
    }

    function get_page_title() {
    	$tax_id = $this->args[0];

    	$query = $this->wpdb->prepare("
			SELECT name
			FROM $this->db_network_terms
			WHERE term_id = %d
			LIMIT 1
        ", $tax_id);
        $query = apply_filters('msreader_'.$this->details['slug'].'_page_title', $query, $this->args, $tax_id);
        $tag = $this->wpdb->get_row($query, ARRAY_A);

		return $this->details['page_title'].': <span>'.$tag['name'].'</span>';
    }

    function query() {
        $limit = $this->get_limit();
        $tax_id = $this->args[0];
        
    	$query = $this->wpdb->prepare("
            SELECT posts.BLOG_ID AS BLOG_ID, ID, post_author, post_date, post_date_gmt, post_content, post_title
            FROM $this->db_network_posts AS posts
            INNER JOIN $this->db_blogs AS blogs ON blogs.blog_id = posts.BLOG_ID
            INNER JOIN $this->db_network_term_rel AS b ON (b.object_id = posts.ID AND b.blog_id = posts.BLOG_ID)
            WHERE blogs.archived = 0 AND blogs.spam = 0 AND blogs.deleted = 0
            AND post_status = 'publish'
            AND post_password = ''
            AND b.term_taxonomy_id = %d
            ORDER BY post_date_gmt DESC
            $limit
        ", $tax_id);
        $query = apply_filters('msreader_'.$this->details['slug'].'_query', $query, $this->args, $limit, $tax_id);
        $posts = $this->wpdb->get_results($query);

    	return $posts;
    }

    function add_options_html($blank, $options) {
        return '
            <label for="wmd_msreader_options[name]">'.__( 'Number of links in "Trending Tags" widget', 'wmd_msreader' ).'</label><br/>
            <input type="number" class="small-text ltr" name="wmd_msreader_options[modules_options]['.$this->details['slug'].'][widget_links_limit]" value="'.$options['widget_links_limit'].'" />
        ';
    }
}