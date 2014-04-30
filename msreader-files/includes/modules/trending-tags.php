<?php
$module = array(
	'name' => __( 'Trending Tags', 'wmd_msreader' ),
	'description' => __( 'Displays trending tags', 'wmd_msreader' ),
	'slug' => 'trending-tags', 
	'class' => 'WMD_MSReader_Module_TrendingTags'
);

class WMD_MSReader_Module_TrendingTags extends WMD_MSReader_Modules {

	function init() {
		add_filter( 'msreader_dashboard_reader_sidebar_widgets', array($this,'add_widget'), 20 );
    }

    function add_widget($widgets) {
    	$limit_sample = $this->get_limit($this->limit_sample, 1);
    	$limit_links = apply_filters('msreader_module_'.$this->details['slug'].'_widget_links_limit', 5); 
    	$limit = $this->get_limit($limit_links, 1);

		$query_hash = md5($this->details['slug'].'widget'.$this->limit_sample.$limit_links);
		$cache_group = 'msreader_global';
		$top_tags = wp_cache_get('query_'.$query_hash, $cache_group);

		if(!$top_tags) {
	    	$query = "
	            SELECT id, slug, name, count(id) AS count
	            FROM (
		            SELECT a.term_taxonomy_id AS id, c.slug AS slug, c.name AS name
		            FROM $this->db_network_term_rel AS a
		            INNER JOIN $this->db_network_term_tax AS b ON b.term_taxonomy_id = a.term_taxonomy_id
		            INNER JOIN $this->db_network_terms AS c ON c.term_id = a.term_taxonomy_id
		            WHERE b.taxonomy = 'post_tag'
		            $limit_sub
	            ) a
	            GROUP BY id
	            ORDER BY count DESC
	            $limit
	        ";
	        $top_tags = $this->wpdb->get_results($query, ARRAY_A);

	        wp_cache_set('query_'.$query_hash, $top_tags, $cache_group);
    	}

        //prepare trending tags links
        $top_tags_ready = array();
        foreach ($top_tags as $tag)
        	$top_tags_ready[] = array('args' => $tag['id'],'title' => $tag['name']);

    	$widgets['trending-tags'] = $this->create_links_widget($top_tags_ready);

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
        $tag = $this->wpdb->get_row($query, ARRAY_A);

		return $this->details['page_title'].' - '.$tag['name'];
    }

    function query() {
        $limit = $this->get_limit();
        $tax_id = $this->args[0];
        
    	$query = $this->wpdb->prepare("
            SELECT a.BLOG_ID AS BLOG_ID, ID, post_author, post_date_gmt, post_content, post_title
            FROM $this->db_network_posts AS a
            INNER JOIN $this->db_network_term_rel AS b ON (b.object_id = a.ID AND b.blog_id = a.BLOG_ID)
            WHERE b.term_taxonomy_id = %d
            ORDER BY post_date_gmt DESC
            $limit
        ", $tax_id);
        $posts = $this->wpdb->get_results($query);

    	return $posts;
    }
}