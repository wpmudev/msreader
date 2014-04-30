<?php
//Class with default functions for all modules. Fast to use and easy to customize
abstract class WMD_MSReader_Modules {
	var $module;

    var $wpdb;
    var $db_network_posts;

    var $page;
    var $limit;
    var $args;

	function __construct() {
		global $msreader_available_modules, $wpdb;

		//set module details
		end($msreader_available_modules);
		$this->details = $msreader_available_modules[key($msreader_available_modules)];

		//sets default unnecessary data
		if(!isset($this->details['page_title']))
			$this->details['page_title'] = $this->details['name'];
		if(!isset($this->details['menu_title']))
			$this->details['menu_title'] = $this->details['name'];

        //set DB details
        $this->wpdb = $wpdb;
        $this->db_network_posts = apply_filters('msreader_db_network_posts', $this->wpdb->base_prefix.'network_posts');
        $this->db_network_terms = apply_filters('msreader_db_network_terms', $this->wpdb->base_prefix.'network_terms');
        $this->db_network_term_rel = apply_filters('msreader_db_network_relationships', $this->wpdb->base_prefix.'network_term_relationships');
        $this->db_network_term_tax = apply_filters('msreader_db_network_taxonomy', $this->wpdb->base_prefix.'network_term_taxonomy');

		//do the custom init by module
		$this->init();
    }
    abstract function init();

    //This function needs to be replaced to display proper data - data is automatically cached for this one
    function query() {
		return 'error';
    }

    function get_featured_image_html($post) {
        $content_images_starts = explode('<img', $post->post_content);

        if($content_images_starts){
            $content_image_ends = explode('/>', $content_images_starts[1]);
            $content_image = '<img'.$content_image_ends[0].'/>';
        }

        if(isset($content_image))
            return '<center>'.$content_image.'</center>';

        return '';
    }

    function get_excerpt($post) {
        $max_sentences = 3;

        $content_sentences = explode('.', strip_tags($post->post_content, '<p><strong><a><blockquote><em>'));
        $count_fake_sentences = 0;
        foreach ($content_sentences as $sentence) {
            if(!$sentence)
                $count_fake_sentences ++;
        }

        $return = implode('.', array_slice($content_sentences, 0, $max_sentences + $count_fake_sentences));
        if(count($content_sentences)-$count_fake_sentences > $max_sentences)
            $return .= '...';

        return apply_filters('the_content', $return);
    }

    //get limit string
    function get_limit($limit = 0, $page = 0) {
        $limit = !$limit ? $this->limit : $limit;
        $page = !$page ? $this->page : $page;

        if(is_numeric($limit) && is_numeric($page)) {
            $start = ($limit*$page)-$limit;

            return 'LIMIT '.$start.','.$limit;
        }
        else
            return 'LIMIT 0,10';
    }

    //by default page title is module title
    function get_page_title() {
		return $this->details['page_title'];
    }

    //easily adds link to main widget
    function create_link_for_main_widget() {
		$link = array(
				'title' => $this->details['menu_title'], 
				'link' => add_query_arg(array('module' => $this->details['slug'], 'args' => false))
			);

		return $link;
    }

    //lets you create links widget for module by providing array with arrays with "arg"(argument that will be added at the end), "title" or optionaly full link by "link"
    function create_links_widget($links) {
    	foreach ($links as $position => $data) {
    		if(isset($data['args'])){
    			$data['link'] = add_query_arg(array('module' => $this->details['slug'], 'args' => $data['args']));
    			$links[$position] = $data;
    		}
    	}
		$widget = array(
    		'title' => $this->details['menu_title'], 
    		'data' => array(
    			'links' => $links
    		)
    	);

		return $widget;
    }
} 