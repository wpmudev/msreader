<?php
class WMD_MSReader_Query {
	var $module;

	var $page = 1;
	var $limit = 10;
	var $limit_sample = 100;
	var $args = array();
	var $last_date;
	var $user = 0;
	var $blog = 0;

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

		//set initial last date as right now
		$this->last_date = time();
    }

	function load_module($module, $is_main_query = 0) {
		//load module
		$this->module = $module;

		//pass parameters to module
		$this->module->main = $is_main_query ? 1 : 0;
		$this->module->page = $this->page;
		$this->module->limit = $this->limit;
		$this->module->limit_sample = $this->limit_sample;
		$this->module->args = $this->args;
		$this->module->last_date = $this->last_date;
		$this->module->user = ($this->user && is_numeric($this->user)) ? $this->user : get_current_user_id();
		$this->module->blog = ($this->blog && is_numeric($this->blog)) ? $this->blog : get_current_blog_id();

		$this->module->load_module();

		//check if its a query used by everybody
		$store_user_id = !$this->module->details['global_cache'] ? $this->module->user : '';
		//check if its a query related to currently displayed blog
		$store_blog_id = $this->module->details['blog_specific_cache'] ? $this->module->blog : '';
		//set up secret code for query
		$this->module->query_hashes['get_posts'] = md5($this->module->cache_init.$this->module->details['slug'].$this->page.$this->limit.http_build_query($this->args).$store_user_id.$store_blog_id);	
	}

	function get_query_details() {
		return array(
				'page_title' => $this->module->get_page_title()
			);
	}

	function get_posts() {
		$posts = array();

		if($this->module) {
		
			$query_prefix = apply_filters('msreader_query_prefix', 'query');
		
			//lets load
			$posts = (!$this->module->details['disable_cache']) ? wp_cache_get($query_prefix.'_'.$this->module->query_hashes['get_posts'], 'msreader_global') : 0;
			if(!$posts) {
				$blog_details = array();

				$posts = $this->module->query();

				//get some additional details for posts
				$posts = $this->set_additional_posts_data($posts, $blog_details);

				if(!$this->module->details['disable_cache'])
					wp_cache_set($query_prefix.'_'.$this->module->query_hashes['get_posts'], $posts, 'msreader_global', $this->module->details['cache_time'] ? $this->module->details['cache_time'] : 900);
			}

			$posts = $this->set_additional_posts_data_dynamic($posts);
		}

		return $posts;
	}

	function get_posts_count() {
		$count = 0;

		if($this->module) {
			if(!$this->module->details['allow_count'])
				return false;

			$query_prefix = apply_filters('msreader_query_count_prefix', 'query_count');
		
			//lets load
			$count = (!$this->module->details['disable_cache']) ? wp_cache_get($query_prefix.'_'.$this->module->query_hashes['get_posts'], 'msreader_global') : 0;

			if(!$count) {
				$count = $this->module->query(true);

				if(!$this->module->details['disable_cache'])
					wp_cache_set($query_prefix.'_'.$this->module->query_hashes['get_posts'], $count, 'msreader_global', $this->module->details['cache_time'] ? $this->module->details['cache_time'] : 900);
			}
		}

		return $count;
	}

	function get_post() {
		if($this->blog_id && $this->post_id) {
			if(get_current_blog_id() != $this->blog_id) {
				$restore = 1;
				switch_to_blog($this->blog_id);
			}

			$post = get_post($this->post_id);
			$post = array($post);
			$post = $this->set_additional_posts_data($post);
			$post = $this->set_additional_posts_data_dynamic($post);

			return $post[0];

			if(isset($restore))
				restore_current_blog();
		}
	}

	function publish_post() {
		if($this->blog_id && $this->post_id) {
			if(get_current_blog_id() != $this->blog_id) {
				$restore = 1;
				switch_to_blog($this->blog_id);
			}

			if(current_user_can('publish_posts')) {
				//i changed it to update but it is not triggering functions then i changed it back to wp publish but now it may cause title issue
				wp_update_post( array('ID' => $this->post_id, 'post_status' => 'publish') );
				//wp_publish_post( $this->post_id );
				$status = true;
				do_action('msreader_publish_post');
			}
			else
				$status = false;
			

			if(isset($restore))
				restore_current_blog();

			return $status;
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

			//add fake comments if we removed some for pagination to be correct - we should fix it by searching by date
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

			$comments_closed = ( ! comments_open( $comment_post_ID ) ||  'trash' == $status || (! $status_obj->public && ! $status_obj->private) || post_password_required( $comment_post_ID ) ) ? true : false;
			if($comments_closed && !current_user_can('edit_post', $post->ID))
				return false;
			
			do_action( 'pre_comment_on_post', $comment_post_ID );

			$comment_content      = isset($this->comment_add_data['comment']) ? trim($this->comment_add_data['comment']) : null;
			if ( '' == $comment_content )
				return false;

			$comment_parent = isset($this->comment_add_data['comment_parent']) ? absint($this->comment_add_data['comment_parent']) : 0;
			$comment_type = (isset($this->comment_add_data['private']) && !empty($this->comment_add_data['private'])) ? 'private' : '';
			if($comment_parent) {
				$comment_parent_comment = get_comment($comment_parent);
				if($comment_parent_comment->comment_approved == 'private')
					$comment_type = 'private';
			}

			

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
			if($comment_type != 'private' && !$comments_closed)
				$comment_id = wp_new_comment( $commentdata );
			else {
				$commentdata['comment_approved'] = 'private';
				$comment_id = wp_insert_comment( $commentdata );
				do_action( 'comment_post', $comment_id, $commentdata['comment_approved'] );
			}


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

			$status = $this->moderate_comment_action($this->comment_moderate_data['action'], $this->comment_moderate_data['comment_id'], $this->post_id);
			
			if(isset($restore))
				restore_current_blog();

			return $status;
		}

		return 0;
	}

	//Helpers

	//set additional details for post
	function set_additional_posts_data($posts, $blog_details = array()) {
		if(is_array($posts)) {
			$posts = apply_filters('msreader_set_additional_posts_data_before', $posts, $this->module);

			foreach ($posts as $key => $post) {
				if($post) {
					$posts[$key] = apply_filters('msreader_set_additional_post_data_before', $post, $this->module);

					$posts[$key]->post_title = stripslashes($post->post_title);
					$posts[$key]->post_content = stripslashes($post->post_content);

					//get blog details
					if(!isset($post->BLOG_ID))
						$posts[$key]->BLOG_ID = get_current_blog_id();
					if(!isset($blog_details[$post->BLOG_ID]))
						$blog_details[$post->BLOG_ID] = get_blog_details($post->BLOG_ID);
					$posts[$key]->blog_details = $blog_details[$post->BLOG_ID];

					//set featured image
					$posts[$key]->featured_media_html = $this->module->get_featured_media_html($post);

					//change excerpt
					$posts[$key]->post_excerpt = $this->module->get_excerpt($post);

					//user details
					$posts[$key]->post_author_display_name = get_the_author_meta( 'display_name', $post->post_author );
					$posts[$key]->post_author_avatar_html = get_avatar($post->post_author, 48);

					$posts = apply_filters('msreader_set_additional_post_data_after', $posts, $this->module);
				}
			}

			$posts = apply_filters('msreader_set_additional_posts_data_after', $posts, $this->module);
		}

		return $posts;
	}

	//set additional details for post that cant be cached
	function set_additional_posts_data_dynamic($posts) {
		if(is_array($posts)) {
			$posts = apply_filters('msreader_set_additional_posts_data_dynamic_before', $posts, $this->module);

			foreach ($posts as $key => $post) {
				if($post) {
					$posts[$key] = apply_filters('msreader_set_additional_post_data_dynamic_before', $post, $this->module);

					$time = strtotime($post->post_date_gmt) ? $post->post_date_gmt : $post->post_date;
					$posts[$key]->post_date_relative = human_time_diff( strtotime($time), time() );
					$posts[$key]->post_date_stamp = strtotime($post->post_date_gmt);

					$posts[$key] = apply_filters('msreader_set_additional_post_data_dynamic_after', $post, $this->module);
				}
			}

			$posts = apply_filters('msreader_set_additional_posts_data_dynamic_after', $posts, $this->module);
		}

		return $posts;
	}

	//helper that applies moderation action on comments to replies
	function moderate_comment_action($action, $comment_id = 0, $post_id = 0) {
		global $wpdb;
		
		$status = false;
		if(current_user_can('moderate_comment') || current_user_can('edit_post', $post_id)) {		
			$replies = $wpdb->get_results( $wpdb->prepare("SELECT comment_ID FROM $wpdb->comments WHERE comment_parent = %d", $comment_id) );

			//lets delete permanently private comments to not leave junk behind
			if($action == 'trash') {
				$comment = get_comment($comment_id);
				if($comment->comment_approved == 'private')
					$action = 'delete';
			}		

			foreach ($replies as $reply) {
					$status = $this->moderate_comment_action($action, $reply->comment_ID, $post_id);
			}

			switch ($action) {
				case 'delete':
					$status = wp_delete_comment($comment_id, 1);
					break;
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
				}
		}

		return $status;
	}
}