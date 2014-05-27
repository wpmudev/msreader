<?php
class WMD_MSReader_Query {
	var $module;

	var $cache_init = 2;
	var $page = 1;
	var $limit = 6;
	var $limit_sample = 100;
	var $args = array();

	var $blog_id;
	var $post_id;
	var $comments_page = 1;
	var $comments_limit = 8;
	var $comments_args = array();
	var $comment_add_data = array();
	var $comment_moderate_data = array();

	function __construct() {
		//add global cache group
		wp_cache_add_global_groups('msreader_global');

		//apply filters to some default variables
		$this->limit = apply_filters('msreader_query_limit_default', $this->limit);
		$this->limit_sample = apply_filters('msreader_query_limit_sample_default', $this->limit_sample);
    }

	function load_module($module) {
		//load module
		$this->module = $module;

		//pass parameters to module
		$this->module->cache_init = $this->cache_init;
		$this->module->page = $this->page;
		$this->module->limit = $this->limit;
		$this->module->limit_sample = $this->limit_sample;
		$this->module->args = $this->args;
	}

	function get_query_details() {
		return array(
				'page_title' => $this->module->get_page_title()
			);
	}

	function get_posts() {
		$posts = array();

		if($this->module) {
			//set up secret code for query
			$query_hash = md5($this->cache_init.$this->module->details['slug'].$this->page.$this->limit.implode('',$this->args));

			//check if its a query used by everybody
			$cache_group = (isset($this->module->details['global_cache']) && $this->module->details['global_cache']) ? 'msreader_global' : 'msreader';
			
			//lets load
			$posts = wp_cache_get('query_'.$query_hash, $cache_group);
			if(!$posts) {
				$blog_details = array();

				$posts = $this->module->query();

				//get some additional details for posts
				if(is_array($posts))
					foreach ($posts as $key => $post)
						$posts[$key] = $this->set_additional_post_data($post);

				wp_cache_set('query_'.$query_hash, $posts, $cache_group, 900);
			}
		}

		return $posts;
	}

	function get_post() {
		if($this->blog_id && $this->post_id) {
			if(get_current_blog_id() != $this->blog_id) {
				$restore = 1;
				switch_to_blog($this->blog_id);
			}

			$post = get_post($this->post_id);
			$post = $this->set_additional_post_data($post);

			return $post;

			if(isset($restore))
				restore_current_blog();
		}
	}

	function get_comments() {
		if($this->blog_id && $this->post_id) {
			if(get_current_blog_id() != $this->blog_id) {
				$restore = 1;
				switch_to_blog($this->blog_id);
			}

			$default_args = array(
				'order' => 'DESC',
				'post_id' => $this->post_id,
				'number' => 999
			);

			if(!isset($this->comments_args['number']) && isset($this->comments_args['ID']))
				$this->comments_args['number'] = 1;

			$args = apply_filters('msreader_query_get_comments_args', array_merge($default_args, $this->comments_args));

			$comments = get_comments($args);

			//add fake comments if we removed some for pagination to be correct
			if(isset($this->comments_args['comments_removed']) && $this->comments_args['comments_removed'] > 0) {
				for($i =0; $i < $this->comments_args['comments_removed']; $i++){
				    $comments = array_merge(array($comments[0]), $comments);
				}			
			}

			if(isset($restore))
				restore_current_blog();

			return $comments;
		}
	}

	function add_comment() {
		if($this->blog_id && $this->post_id) {
			if(get_current_blog_id() != $this->blog_id) {
				$restore = 1;
				switch_to_blog($this->blog_id);
			}

			$comment_post_ID = $this->post_id;

			$post = get_post($comment_post_ID);

			if ( empty( $post->comment_status ) ) {
				do_action( 'comment_id_not_found', $comment_post_ID );
				return false;
			}

			$status = get_post_status($post);

			$status_obj = get_post_status_object($status);

			if ( ! comments_open( $comment_post_ID ) ||  'trash' == $status || (! $status_obj->public && ! $status_obj->private) || post_password_required( $comment_post_ID ) )
				return false;
			
			do_action( 'pre_comment_on_post', $comment_post_ID );

			$comment_content      = isset($this->comment_add_data['comment']) ? trim($this->comment_add_data['comment']) : null;
			if ( '' == $comment_content )
				return false;

			$comment_parent 	  = isset($this->comment_add_data['comment_parent']) ? absint($this->comment_add_data['comment_parent']) : 0;
			$comment_type = '';

			// If the user is logged in
			$user = wp_get_current_user();
			if ( $user->exists() ) {
				$user_id = $user->ID;
				if ( empty( $user->display_name ) )
					$user->display_name = $user->user_login;
				$comment_author = wp_slash( $user->display_name );
				$comment_author_email = wp_slash( $user->user_email );
				$comment_author_url = wp_slash( $user->user_url );
			}

			$commentdata = compact('comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_id');
			$comment_id = wp_new_comment( $commentdata );

			if(isset($restore))
				restore_current_blog();

			return $comment_id;
		}
	}

	function moderate_comment() {
		if($this->blog_id && $this->post_id) {
			if(get_current_blog_id() != $this->blog_id) {
				$restore = 1;
				switch_to_blog($this->blog_id);
			}

			if(!current_user_can('moderate_comment'))
				return false;

			$status = $this->moderate_comment_action($this->comment_moderate_data['action'], $this->comment_moderate_data['comment_id']);

			if(isset($restore))
				restore_current_blog();

			return $status;
		}
	}

	function get_posts_rss() {
	}

	//Helpers

	//set additional details for post
	function set_additional_post_data($post) {
		$post->post_title = stripslashes($post->post_title);
		$post->post_content = stripslashes($post->post_content);

		//get blog details
		if(!isset($blog_details[$post->BLOG_ID]))
			$blog_details[$post->BLOG_ID] = get_blog_details($post->BLOG_ID);
		$post->blog_details = $blog_details[$post->BLOG_ID];

		//set featured image
		$post->featured_media_html = $this->module->get_featured_media_html($post);

		//change excerpt
		$post->post_excerpt = $this->module->get_excerpt($post);

		//set relative time
		$post->relative_time = human_time_diff( get_post_time('U', true, $post), current_time('timestamp') );

		return $post;
	}

	//helper that applies moderation action on comments to replies
	function moderate_comment_action($action, $comment_id = 0) {
		global $wpdb;
		
		$replies = $wpdb->get_results( $wpdb->prepare("SELECT comment_ID FROM $wpdb->comments WHERE comment_parent = %d", $comment_id) );

		foreach ($replies as $reply) {
			$status = $this->moderate_comment_action($action, $reply->comment_ID);
		}

		switch ($action) {
			case 'trash':
				$status = wp_delete_comment($comment_id);
				break;
			case 'spam':
				$status = wp_spam_comment($comment_id);
				break;
			case 'unapprove':
				$status = wp_set_comment_status($comment_id, 0);
				$status = $status ? 'approve' : 'unapprove';
				break;
			case 'approve':
				$status = wp_set_comment_status($comment_id, 1);
				$status = $status ? 'unapprove' : 'approve';
				break;
			default:
				$status = false;
		}

		return $status;
	}
}