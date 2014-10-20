<?php
//Class with default functions for all modules. Fast to use and easy to customize
abstract class WMD_MSReader_Modules {
    var $db_network_posts;
    var $db_network_terms;
    var $db_network_term_rel;
    var $db_blogs;
    var $db_users;

    var $page;
    var $limit;
    var $limit_sample;
    var $args;
    var $cache_init;
    var $main = 0;
    var $query_hashes = array();
    var $user;

    var $options;

    var $message;
    var $message_type;

    var $helpers;

	function __construct($options = array()) {
		global $msreader_available_modules, $wpdb, $msreader_helpers;

		//set module details
        end($msreader_available_modules);
		$this->details = $msreader_available_modules[key($msreader_available_modules)];

        //set options for module
        $this->options = $options;

		//sets default unnecessary data
		if(!isset($this->details['page_title']))
			$this->details['page_title'] = $this->details['name'];
		if(!isset($this->details['menu_title']))
			$this->details['menu_title'] = $this->details['name'];

        if(!isset($this->details['can_be_default']))
            $this->details['can_be_default'] = true;
        if(!isset($this->details['global_cache']))
            $this->details['global_cache'] = false;
        if(!isset($this->details['disable_cache']))
            $this->details['disable_cache'] = false;
        if(!isset($this->details['cache_time']))
            $this->details['cache_time'] = '';

        //set DB details
        $this->db_network_posts = apply_filters('msreader_db_network_posts', $wpdb->base_prefix.'network_posts');
        $this->db_network_terms = apply_filters('msreader_db_network_terms', $wpdb->base_prefix.'network_terms');
        $this->db_network_term_rel = apply_filters('msreader_db_network_relationships', $wpdb->base_prefix.'network_term_relationships');
        $this->db_network_term_tax = apply_filters('msreader_db_network_taxonomy', $wpdb->base_prefix.'network_term_taxonomy');
        $this->db_blogs = $wpdb->base_prefix.'blogs';
        $this->db_users = $wpdb->base_prefix.'users';

        //enable easy use of helpers functions
        $this->helpers = $msreader_helpers;

		//do the custom init by module
		$this->init();

        //enable blog post linking without switch to blog
        if(isset($_GET['msreader_'.$this->details['slug']]) && $_GET['msreader_'.$this->details['slug']] == 'open_post' && isset($_GET['post_id']) && isset($_GET['blog_id'])) {
            add_action('init', array( $this, "open_site_post" ), 20);
        }
    }
    abstract function init();

    function load_module() {
        $this->cache_init = $this->details['global_cache'] ? get_site_option('msreader_cache_init_'.$this->details['slug'], 1) : get_user_option('msreader_cache_init_'.$this->details['slug']);
        $this->cache_init = $this->cache_init == false ? 1 : $this->cache_init;
    }

    //This function needs to be replaced to display proper data - data is automatically cached for this one
    function query() {
		return 'error';
    }

    //by default page title is module title
    function get_page_title() {
        return $this->details['page_title'];
    }

    //by default page title is module title
    function get_empty_message() {
        return __('Nothing here yet!', 'wmd_msreader' );
    }

    function get_featured_media_html($post) {
        $post_content = preg_replace_callback( '|^\s*(https?://[^\s"]+)\s*$|im', array( $this, 'get_excerpt_media' ), $post->post_content );

        $content_images_starts = explode('<img', $post_content);

        if(isset($content_images_starts[1]) && $content_images_starts[1]){
            $content_image_ends = explode('/>', $content_images_starts[1]);
            if(isset($content_image_ends[0]) && $content_image_ends[0])
                $content_media = '<img'.$content_image_ends[0].'/>';
        }
        $content_iframe_starts = explode('<iframe', $post_content);

        if($content_iframe_starts && strlen($content_iframe_starts[0]) < strlen($content_images_starts[0])){
            $content_iframe_ends = explode('</iframe>', $content_iframe_starts[1]);
            if($content_iframe_ends[0])
                $content_media = '<iframe'.$content_iframe_ends[0].'</iframe>';
        }

        if(isset($content_media))
            return $content_media;

        return '';
    }

    function get_excerpt($post) {
        $max_sentences = 5;
        $max_paragraphs = 3;

        if(!shortcode_exists('gallery')) {
        	add_shortcode( 'gallery' , create_function('', 'return false;'));
            $fake_gallery_shortcode_added = 1;
        }
        $post_content = strip_shortcodes( $post->post_content );

        if(isset($fake_gallery_shortcode_added))
            remove_shortcode( 'gallery' );

        if(class_exists('DOMDocument')) {
            $allowed_tags = array('<strong>','<blockquote>','<em>','<p>', '<span>', '<a>');
            
            $post_content = wpautop(strip_tags($post_content, implode('', $allowed_tags)));
                       
            $dom = new DOMDocument();
            $dom->loadHTML('<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /></head><body>'.$post_content.'</body></html>');
            $elements = $dom->documentElement;
            $all_elements = $elements->getElementsByTagName('*');

            $current_paragraphs = 0;
            $current_sentences = 0;
            $limit_reached = 0;
            $remove_childs = array();
            foreach($all_elements as $key => $child) {
                if($child->nodeName == 'html' || $child->nodeName == 'body' || $child->nodeName == 'meta' || $child->nodeName == 'head')
                    continue;

                if($limit_reached || str_replace(array(' ', '&nbsp;'), '', trim($child->textContent)) == '')
                    $remove_childs[] = $child;
                else {
                    //count sentences 
                    $content_sentences = explode('.', $child->textContent);
                    $count_content_sentences = count($content_sentences);

                    if($count_content_sentences) {
                        //ditch fake sentences
                        $count_fake_sentences = 0;
                        foreach ($content_sentences as $sentence) {
                            $sentence_length = strlen($sentence);
                            if(!$sentence || strlen(str_replace (' ', '', $sentence)) == $sentence_length )
                                $count_fake_sentences ++;
                        }
                        $current_sentences = $current_sentences + $count_content_sentences - $count_fake_sentences;
                    }
                    else
                        if(str_word_count($child->textContent) > 3)
                            $current_sentences ++;

                    //count paragraph
                    if($child->nodeName == 'p')
                        $current_paragraphs ++;

                    //check if limit reached
                    if(!$limit_reached && $child->nodeValue && ($current_paragraphs >= $max_paragraphs || $current_sentences >= $max_sentences)) {
                        $child->nodeValue = $child->nodeValue.'...';
                        $last_child = $child;
                        $limit_reached = 1;
                    }
                }
            }
            foreach ($remove_childs as $child) {
                $child->parentNode->removeChild($child);
            }

            $body = $dom->getElementsByTagName('body');
            $body = $body->item(0);
            $return = $dom->saveXML($body);
            $return = str_replace('<body>', '', $return);
            $return = str_replace('</body>', '', $return);
        }
        else {
            $allowed_tags = array('<strong>','<blockquote>','<em>','<p>','<a>');

            $post_content = strip_tags($post_content, implode('', $allowed_tags));

            $content_sentences = explode('.', strip_tags($post_content, implode('',$allowed_tags)));
            $content_text_length = strip_tags($post->post_content);



            if(strlen($content_text_length) > 1000 && count($content_sentences) == 1) {
                $return = substr($content_text_length, 0, 1000).'...'; 
            }
            else {

                //ditch fake sentences
                $count_content_sentences_real = 0;
                $content_sentences_clean = array();
                foreach ($content_sentences as $sentence) {
                    $sentence_length = strlen($sentence);
                    if(
                        !(!$sentence || 
                        strlen(str_replace (' ', '', $sentence)) == $sentence_length || 
                        substr($sentence, 0, 1) != ' ')
                    )
                        $count_content_sentences_real ++;

                    $content_sentences_clean[] = $sentence;

                    if($count_content_sentences_real == $max_sentences)
                        break;
                }

                //limit to max sentences
                $return = implode('.', $content_sentences_clean);

                //limit to total paragraphs
                $return = str_replace('<p></p>', '', $return);
                $paragraphs = explode('</p>', $return);
                $return = implode('</p>', array_slice($paragraphs, 0, $max_paragraphs));

                //check if content was stripped
                if($count_content_sentences_real == $max_sentences || count($paragraphs) > $max_paragraphs)
                    $return .= '...';   

                //close all allowed tags
                foreach ($allowed_tags as $tag) {
                    $opening_tag = str_replace('>', '', $tag);
                    $closing_tag = str_replace('<', '</', $tag);
                    $open_close_difference = substr_count($return, $opening_tag) - substr_count($return, $closing_tag);
                    for($i =0; $i < $open_close_difference; $i++)
                        $return .=  $closing_tag;
                }
            }
        }

        //remove remaining possibly unsafe JS
        $return = str_replace('href="javascript:', 'href="', $return);
        $return = str_replace("href='javascript:", "href='", $return);
        $return = str_replace('onclick="', 'data-disabled="', $return);
        $return = str_replace("onclick='", "data-disabled='", $return);

        return $return;
    }

    function get_excerpt_media($match) {
        $return = wp_oembed_get( $match[1], array() );
        return "\n$return\n";
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


    //get limit string
    function get_module_dashboard_url($args = array(), $module_slug = '') {
        global $msreader_modules;

        $module_slug = $module_slug ? $module_slug : $this->details['slug'];

        if(array_key_exists($module_slug, $msreader_modules)) {
            $blog_id = (is_user_member_of_blog() || is_super_admin()) ? get_current_blog_id() : get_user_meta(get_current_user_id(), 'primary_blog', true);
            
            $url = get_admin_url($blog_id, 'index.php?page=msreader.php&module='.$module_slug);
            if(is_array($args) && count($args) > 0)
                $url = add_query_arg(array('args' => $args), $url);
        }
        else
            $url = '';

        $url = apply_filters('msreader_module_dashboard_url_'.$this->details['slug'], $url, $args);
        $url = apply_filters('msreader_module_dashboard_url', $url, $args);

        return $url;
    }

    //easily adds link to main widget
    function create_link_for_main_widget($title_after = '') {
		$link = array(
				'title' => $this->details['menu_title'].$title_after, 
				'link' => add_query_arg(array('module' => $this->details['slug'], 'args' => false))
			);

		return $link;
    }

    //lets you create links widget for module by providing array with arrays with "arg"(argument that will be added at the end), "title" or optionaly full link by "link"
    function create_list_widget($links, $widget_details = array()) {
    	foreach ($links as $position => $data) {
    		if(isset($data['args']))
    			$data['link'] = add_query_arg(array('module' => $this->details['slug'], 'args' => $data['args']));
            if(isset($data['link']) && !$data['link'])
                unset($data['link']);

            $links[$position] = $data;
    	}
		$widget = array(
    		'title' => $this->details['menu_title'], 
    		'data' => array(
    			'list' => $links
    		)
    	);

        $widget = array_replace_recursive($widget, $widget_details);

		return $widget;
    }

    function increase_cache_init() {
        $this->cache_init = $this->details['global_cache'] ? get_site_option('msreader_cache_init_'.$this->details['slug'], 1) : get_user_option('msreader_cache_init_'.$this->details['slug']);
        $this->cache_init = $this->cache_init == false ? 1 : $this->cache_init;

        $this->cache_init++;

        if($this->details['global_cache'])
            update_site_option( 'msreader_cache_init_'.$this->details['slug'], $this->cache_init );
        else
            update_user_option( get_current_user_id(), 'msreader_cache_init_'.$this->details['slug'], $this->cache_init, true );
    }

    function add_module_slug_to_array($array) {
        $array[] = $this->details['slug'];

        return $array;
    }
    function is_site_indexable($blog_id) {
        global $postindexeradmin;

        if(
            get_blog_status($blog_id, 'public') && (
                !isset($postindexeradmin) || 
                (
                    isset($postindexeradmin->model) && 
                    method_exists($postindexeradmin->model,'is_blog_indexable') && 
                    $postindexeradmin->model->is_blog_indexable( $blog_id )
                )
            )
        )
            return true;
        else
            return false;
    }

    function get_site_post_link($blog_id, $post_id) {
        return 
        apply_filters(
            'msreader_rss_feeds_post_link', 
            esc_url(
                add_query_arg(
                    array(
                        'msreader_'.$this->details['slug'] => 'open_post',
                        'blog_id' => $blog_id,
                        'post_id' => $post_id
                    ), 
                    network_site_url()
                )
            ), $blog_id, $post_id, network_site_url()
        );
    }

    function open_site_post() {
        wp_redirect(get_blog_permalink( $_GET['blog_id'], $_GET['post_id'] ));
        exit();
    }
} 