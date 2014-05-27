<?php
$module = array(
	'name' => __( 'Follow', 'wmd_msreader' ),
	'description' => __( 'Enables following of sites in network', 'wmd_msreader' ),
    'menu_title' => __( 'Following', 'wmd_msreader' ),
	'slug' => 'follow', 
	'class' => 'WMD_MSReader_Module_Follow',
    'default_options' => array(
        'follow_by_default' => 1,
        'widget_links_limit' => 5
    )
);

class WMD_MSReader_Module_Follow extends WMD_MSReader_Modules {

	function init() {
        add_filter( 'msreader_dashboard_reader_sidebar_widgets', array($this,'add_link_to_widget'), 10 );
		add_filter( 'msreader_dashboard_reader_sidebar_widgets', array($this,'add_widget'), 10, 10 );

        add_action( 'admin_bar_menu', array( $this, "follow_link" ), 5 );
        add_action( 'wp_head', array( $this, "add_css" ) );
        add_action( 'admin_head', array( $this, "add_css" ) );

        add_action('admin_init', array($this,'admin_init') );

        add_filter( 'msreader_module_options_'.$this->details['slug'], array($this,'add_options_html'), 10, 2 );

        add_filter('msreader_post_blog', array($this,'add_blog_link'),20,2);
    }

    function add_link_to_widget($widgets) {
        $widgets['reader']['data']['list'][] = $this->create_link_for_main_widget();

        return $widgets;
    }

    function add_blog_link($name, $post) {
        $followed_by_user = $this->get_followed_sites();
        if(!in_array($post->blog_details->blog_id, $followed_by_user))
            return $name.' <a class="add-new-h2 button-small" title="'.__('Follow', 'wmd_msreader').' '.$post->blog_details->blogname.'" href="'.$this->get_module_dashboard_url(array('action' => 'follow', 'blog_id' => $post->blog_details->blog_id)).'"><span class="msreader-follow-icon"></span> Follow</a>';
        else
            return $name;
    }

    function add_css() {
        //echo '<style type="text/css">#wp-admin-bar-msreader-follow .ab-item:before {content: "\f487";}</style>';
        echo 
        '<style type="text/css">
        #wp-admin-bar-msreader-follow.follow .ab-item:before {content: "\f487";}
        #wp-admin-bar-msreader-follow.following .ab-item:before {content: "\f487"; color: #d54e21;}
        #wp-admin-bar-msreader-follow.following:hover .ab-item:before {content: "\f487"; color: #999;}
        #wp-admin-bar-msreader-follow.following:hover .ab-item {color: #999 !important;}';
        if(is_admin())
            echo 
            '.msreader-widget-links-icon:before {font: 400 13px/1 dashicons; content: "\f158"; float:right;}
            .msreader-follow-icon:before {content: "\f487"; font-family: dashicons; font-size:10px; position:relative; top: 1px;}';
        echo 
        '</style>';

        echo 
        '<script type="text/javascript">
        (function($) {
            $(document).ready(function() {
                $("#wp-admin-bar-msreader-follow").hover(
                    function() {
                        $(this).css("width", $(this).width());
                        $(this).find(".current-text").hide();
                        $(this).find(".hover-text").show();
                    }, function() {
                        $(this).find(".hover-text").hide();
                        $(this).find(".current-text").show();
                    }
                );
            });
        })(jQuery);
        </script>';
    }

    function admin_init() {
        //check if we are following/unfollowing anything
        if(isset($_GET['module']) && $_GET['module'] == 'follow' && isset($this->args['action']) && isset($this->args['blog_id']) && ($this->args['action'] == 'follow' || $this->args['action'] == 'unfollow')) {
            $user_follow_data = get_user_option('msreader_follow');
            $user_follow_data = !$user_follow_data ? array('followed' => array(), 'unfollowed' => array()) : $user_follow_data;
            
            $blog_details = get_blog_details($this->args['blog_id']);
            if($blog_details){
                $this->message_type = 1;

                if($this->args['action'] == 'follow') {
                    if(!in_array($this->args['blog_id'], $user_follow_data['followed']))
                        $user_follow_data['followed'][] = $this->args['blog_id'];

                    $unfollowed_key = array_search($this->args['blog_id'], $user_follow_data['unfollowed']);
                    if($unfollowed_key !== false)
                        unset($user_follow_data['unfollowed'][$unfollowed_key]);

                    $this->message = $blog_details->blogname.' '.__( 'is now being followed.', 'wmd_msreader' );
                }
                elseif($this->args['action'] == 'unfollow') {
                    if(!in_array($this->args['blog_id'], $user_follow_data['unfollowed']))
                        $user_follow_data['unfollowed'][] = $this->args['blog_id'];

                    $followed_key = array_search($this->args['blog_id'], $user_follow_data['followed']);
                    if($followed_key !== false)
                        unset($user_follow_data['followed'][$followed_key]);

                    $this->message = $blog_details->blogname.' '.__( 'is no longer followed.', 'wmd_msreader' );
                }

                $current_user_id = get_current_user_id();
                update_user_option($current_user_id, 'msreader_follow', $user_follow_data, true);
            }
            else {
                $this->message_type = 0;
                $this->message = __( 'This action could not be performed.', 'wmd_msreader' );
            }
        }
    }

    function follow_link() {
        global $wp_admin_bar;

        $current_blog_id = get_current_blog_id();

        $followed_by_user = $this->get_followed_sites();
        
        if(in_array($current_blog_id, $followed_by_user)) {
            $text = __( 'Following', 'wmd_msreader' );
            $hover_text = __( 'Unfollow', 'wmd_msreader' );
            $url = $this->get_module_dashboard_url(array('action' => 'unfollow', 'blog_id' => $current_blog_id));
            $class = 'following';
        }
        else {
            $text = __( 'Follow', 'wmd_msreader' );
            $hover_text = __( 'Follow', 'wmd_msreader' );
            $url = $this->get_module_dashboard_url(array('action' => 'follow', 'blog_id' => $current_blog_id));
            $class = 'follow';            
        }

        $wp_admin_bar->add_menu( 
            array(
                'id'   => 'msreader-follow',
                'parent' => 'top-secondary',
                'title' => '<span class="ab-icon"></span><span class="current-text">'.$text.'</span><span class="hover-text" style="display:none">'.$hover_text.'</span>',
                'href' => $url,
                'meta' => array(
                    'class' => $class,
                    'title' => $hover_text.' '.__( 'this site', 'wmd_msreader' )
                ),
            ) 
        );
    }

    function add_widget($widgets) {
        $current_user_id = get_current_user_id();
        $limit = 7;

        //TODO is filter module enabled

        $followed_by_user = $this->get_followed_sites();

        //prepare trending tags links
        $followed_by_user_ready = array();
        foreach ($followed_by_user as $blog_id) {
            $blog_details = get_blog_details($blog_id);
        	$followed_by_user_ready[] = array('link' => $this->get_module_dashboard_url(array('blog_id' => $blog_details->blog_id), 'filter_blog_author'),'title' => $blog_details->blogname, 'after' => ' <a href="'.$this->get_module_dashboard_url(array('action' => 'unfollow', 'blog_id' => $blog_details->blog_id)).'" title="'.sprintf(__('Unfollow %s', 'wmd_msreader'), $blog_details->blogname).'"><span class="msreader-widget-links-icon"></span></a>');
        }

        $scripts = 'script';

    	$widgets['follow'] = $this->create_list_widget($followed_by_user_ready, array('title' => __( 'Followed Sites', 'wmd_msreader' ), 'data' => array('html' => '')));

    	return $widgets;
    }

    function get_page_title() {
 		return __( 'Followed Sites', 'wmd_msreader' );
    }

    function query() {
        $limit = $this->get_limit();

        $followed_by_user = $this->get_followed_sites();

         $followed_by_user_ids = implode(',', $followed_by_user);

        $query = "
            SELECT posts.BLOG_ID AS BLOG_ID, ID, post_author, post_date, post_date_gmt, post_content, post_title
            FROM $this->db_network_posts AS posts
            INNER JOIN $this->db_blogs AS blogs ON blogs.blog_id = posts.BLOG_ID
            WHERE blogs.archived = 0 AND blogs.spam = 0 AND blogs.deleted = 0
            AND post_status = 'publish'
            AND post_password = ''
            AND posts.BLOG_ID IN( $followed_by_user_ids)
            ORDER BY post_date_gmt DESC
            $limit
        ";
        $query = apply_filters('msreader_'.$this->details['slug'].'_query', $query, $this->args, $limit,  $followed_by_user_ids);
        
        $posts = $this->wpdb->get_results($query);

        return $posts;
    }

    function get_followed_sites() {
        $user_follow_data = get_user_option('msreader_follow');
        $user_follow_data = !$user_follow_data ? array('followed' => array(), 'unfollowed' => array()) : $user_follow_data;
        $followed_by_default = explode(',', str_replace(' ', '', $this->options['follow_by_default']));
        $followed_by_user = array_diff (array_merge($user_follow_data['followed'], $followed_by_default), $user_follow_data['unfollowed']);
        foreach ($followed_by_user as $key => $blog_id)
            if(!is_numeric($blog_id))
                unset($followed_by_user[$key]);

        return array_unique($followed_by_user);
    }

    function add_options_html($blank, $options) {
        return '
            <label for="wmd_msreader_options[name]">'.__( 'Number of links in "Followed Sites" widget before showing "more" button.', 'wmd_msreader' ).'</label><br/>
            <input type="number" class="small-text ltr" name="wmd_msreader_options[modules_options]['.$this->details['slug'].'][widget_links_limit]" value="'.$options['widget_links_limit'].'" />
            <br/>
            <label for="wmd_msreader_options[name]">'.__( 'IDs of sites followed by default (Comma separated)', 'wmd_msreader' ).'</label><br/>
            <input type="text" class="smallt-ext ltr" name="wmd_msreader_options[modules_options]['.$this->details['slug'].'][follow_by_default]" value="'.$options['follow_by_default'].'" />
        ';
    }
}