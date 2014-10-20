<?php
$module = array(
	'name' => __( 'Follow', 'wmd_msreader' ),
	'description' => __( 'Enables following of sites in network', 'wmd_msreader' ),
    'menu_title' => __( 'Following', 'wmd_msreader' ),
	'slug' => 'follow', 
	'class' => 'WMD_MSReader_Module_Follow',
    'default_options' => array(
        'follow_by_default' => 1,
        'button_visibility' => 'both',
        'button_visibility_for' => 'loggedin'
    )
);

class WMD_MSReader_Module_Follow extends WMD_MSReader_Modules {

	function init() {
        add_filter( 'msreader_dashboard_reader_sidebar_widgets', array($this,'add_link_to_widget'), 10 );

        add_action( 'admin_bar_menu', array( $this, "follow_link" ), 500 );
        add_action( 'wp_head', array( $this, "add_css_js" ) );
        add_action( 'admin_head', array( $this, "add_css_js" ) );
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_scripts'), 20, 1 );

        add_action( 'admin_init', array($this,'follow_control') );

        add_filter( 'msreader_module_options_'.$this->details['slug'], array($this,'add_options_html'), 10, 2 );

        add_filter( 'msreader_post_blog', array($this,'add_blog_link'),20,2);

        add_filter( 'msreader_user_widget_user_info', array($this,'add_following_stats'),20,1);

        add_action( 'wp_ajax_msreader_follow_control', array($this, 'follow_control'), 20, 1 );
    }

    function add_link_to_widget($widgets) {
        if(!$this->helpers->is_module_enabled('user_widget')) {
            $link = $this->get_module_dashboard_url(array('action' => 'manage'));
            $active = ($this->helpers->is_page_link_active($link)) ? 'class="active" ' : '';
            $widgets['reader']['data']['list'][] = $this->create_link_for_main_widget('<a '.$active.'href="'.$this->get_module_dashboard_url(array('action' => 'manage')).'" title="'.__('Manage followed sites', 'wmd_msreader').'"><span class="msreader-widget-links-icon dashicons-admin-generic"></span></a>');
        }
        else
            $widgets['reader']['data']['list'][] = $this->create_link_for_main_widget();
        
        return $widgets;
    }

    function add_blog_link($name, $post) {
        $followed_by_user = $this->get_followed_sites();
        if(!in_array($post->blog_details->blog_id, $followed_by_user)) {
            $text = __('Follow', 'wmd_msreader');
            $hover_text = __('Follow', 'wmd_msreader');
            $class = '';
        }
        else {
            $text = __('Following', 'wmd_msreader');
            $hover_text = __('Unfollow', 'wmd_msreader');
            $class = ' following';    
        }

        return $name.' <a class="add-new-h2 button-small msreader-follow-button'.$class.'" title="'.__('Follow/Unfollow this site in Reader', 'wmd_msreader').'" href="#"><span class="msreader-follow-icon"></span> <span class="current-text">'.$text.'</span><span class="hover-text">'.$hover_text.'</span></a>';
    }

    function add_following_stats($user_info) {
        $link = $this->get_module_dashboard_url(array('action' => 'manage'));
        $active = ($this->helpers->is_page_link_active($link)) ? ' active' : '';

        $followed_by_user = $this->get_followed_sites();

        $user_info['stats']['following'] = 
        '<div class="user-stat-follow user-stat'.$active.'"><a title="View list of followed sites" href="'.$link.'"><h4>'.__( 'Following', 'wmd_msreader' ).'</h4>
        <p>'.count($followed_by_user).'</p></a></div>';

        return $user_info;
    }

    function enqueue_scripts() {
        wp_localize_script('jquery', 'msreader_follow', 
            array(
                'following' => __( 'Following', 'wmd_msreader' ), 
                'follow' => __( "Follow", "wmd_msreader" ), 
                'unfollow' => __( "Unfollow", "wmd_msreader" ) )
            );
    }

    function add_css_js() {
        echo 
        '<style type="text/css">
        #wp-admin-bar-msreader-follow {width: 100px;}
        #wp-admin-bar-msreader-follow .hover-text {display:none;}
        #wp-admin-bar-msreader-follow:hover .hover-text {display:inline;}
        #wp-admin-bar-msreader-follow:hover .current-text {display:none;}
        #wp-admin-bar-msreader-follow .ab-item .ab-icon:before {content: "\f487"; top: 2px;}
        #wp-admin-bar-msreader-follow.following .ab-item .ab-icon:before {color: #d54e21;}
        #wp-admin-bar-msreader-follow.following:hover .ab-item .ab-icon:before {color: #999;}
        #wp-admin-bar-msreader-follow.following:hover .ab-item {color: #999 !important;}';
        if(is_admin()) {
            echo 
            '.msreader-follow-button {min-width:100px;}
            .msreader-follow-button .hover-text {display:none;}
            .msreader-follow-button:hover .hover-text {display:inline;}
            .msreader-follow-button:hover .current-text {display:none;}
            .msreader-follow-icon:before {content: "\f487"; font-family: dashicons; font-size:10px; position:relative; top: 1px;}
            .msreader-follow-button.following .msreader-follow-icon:before {color: #d54e21;}
            .msreader-follow-button.following:hover .msreader-follow-icon:before {color: #c4c4c4;}
            .msreader-follow-button.following:hover {color: #c4c4c4;}
            ';
        }
        echo 
        '</style>';

        if(is_admin()) {
        ?>

        <script type="text/javascript">
        (function($) {
            $(document).ready(function() {
                $(".msreader-posts").on("click", ".msreader-follow-button", function(event) {
                    event.preventDefault();

                    var button = $(this);
                    var blog_id = button.parents(".msreader-post").attr("data-blog_id");
                    var post_id = button.parents(".msreader-post").attr("data-post_id");

                    if(button.hasClass('following'))
                        var action = 'unfollow';
                    else
                        var action = 'follow';

                    follow_control(blog_id, post_id, action, button, 0)
                });
                $(".msreader-post-overlay").on("click", ".msreader-follow-button", function(event) {
                    event.preventDefault();

                    var button = $(this);
                    var blog_id = msreader_main_query.current_post.attr("data-blog_id");
                    var post_id = msreader_main_query.current_post.attr("data-post_id");

                    if(button.hasClass('following'))
                        var action = 'unfollow';
                    else
                        var action = 'follow';

                    follow_control(blog_id, post_id, action, button, 0);
                });
            });

            function follow_control(blog_id, post_id, action, button, frontend) {
                if(blog_id && post_id && action) {
                    if(!frontend)
                        $(".msreader-post-header-navigation .spinner, .msreader-post[data-blog_id='"+blog_id+"'][data-post_id='"+post_id+"'] .spinner").show();
                    else
                        button.text(msreader.saving)

                    feature_details = {
                        blog_id: blog_id,
                        action: action
                    }
                    args = {
                        source: "msreader",
                        module: "follow",
                        action: "msreader_follow_control",
                        args: feature_details
                    };

                    $.post(ajaxurl, args, function(response) {
                        $(".msreader-post-header-navigation .spinner, .msreader-post[data-blog_id='"+blog_id+"'][data-post_id='"+post_id+"'] .spinner").fadeOut(200, function() {$(this).hide()});

                        if(response && response != 0) {
                            var all_buttons = $(".msreader-post[data-blog_id='"+blog_id+"'] .msreader-follow-button, .msreader-post-overlay .msreader-follow-button");
                            if(response == 'following') {
                                all_buttons.addClass('following');
                                all_buttons.find('.current-text').text(msreader_follow.following);
                                all_buttons.find('.hover-text').text(msreader_follow.unfollow);

                                if($('.msreader_module_follow').length)
                                    msreader.add_post_to_list(0, 0, $(".msreader-post[data-blog_id='"+blog_id+"']"));

                                var user_follow_stat = $('.user-stat-follow p');
                                if(user_follow_stat.length)
                                    user_follow_stat.text(parseInt(user_follow_stat.text())+1);
                            }
                            if(response == 'follow') {
                                all_buttons.removeClass('following');
                                all_buttons.find('.current-text').text(msreader_follow.follow);
                                all_buttons.find('.hover-text').text(msreader_follow.follow);

                                if($('.msreader_module_follow').length)
                                    msreader.remove_post_from_list(0, 0, $(".msreader-post[data-blog_id='"+blog_id+"']"));

                                var user_follow_stat = $('.user-stat-follow p');
                                if(user_follow_stat.length)
                                    user_follow_stat.text(parseInt(user_follow_stat.text())-1);
                            }
                        }
                    });
                }
            }
        })(jQuery);
        </script>

        <?php
        }
    }

    function follow_control($action = 0, $blog_id = 0) {
        $action = $action ? $action : (isset($this->args['action']) ? $this->args['action'] : 0);
        $blog_id = $blog_id ? $blog_id : (isset($this->args['blog_id']) ? $this->args['blog_id'] : 0);

        //check if we are following/unfollowing anything
        if(is_numeric($blog_id) && $blog_id && ($action == 'follow' || $action == 'unfollow')) {
            $user_follow_data = get_user_option('msreader_follow');
            $user_follow_data = !$user_follow_data ? array('followed' => array(), 'unfollowed' => array()) : $user_follow_data;
            
            $blog_details = get_blog_details($blog_id);
            if($blog_details){
                $this->message_type = 1;

                if($action == 'follow') {
                    if(!in_array($blog_id, $user_follow_data['followed']))
                        $user_follow_data['followed'][] = $blog_id;

                    $unfollowed_key = array_search($blog_id, $user_follow_data['unfollowed']);
                    if($unfollowed_key !== false)
                        unset($user_follow_data['unfollowed'][$unfollowed_key]);

                    $this->message = $blog_details->blogname.' '.__( 'is now being followed.', 'wmd_msreader' ).' <a href="'.get_site_url($blog_id).'">'.__( 'Visit the site', 'wmd_msreader' ).'</a>.';
                    
                    if(defined('DOING_AJAX') && isset($_POST['action']) && $_POST['action'] == 'msreader_follow_control')
                        echo 'following';
                }
                elseif($action == 'unfollow') {
                    if(!in_array($blog_id, $user_follow_data['unfollowed']))
                        $user_follow_data['unfollowed'][] = $blog_id;

                    $followed_key = array_search($blog_id, $user_follow_data['followed']);
                    if($followed_key !== false)
                        unset($user_follow_data['followed'][$followed_key]);

                    $this->message = $blog_details->blogname.' '.__( 'is no longer followed.', 'wmd_msreader' ).' <a href="'.get_site_url($blog_id).'">'.__( 'Visit the site', 'wmd_msreader' ).'</a>.';

                    if(defined('DOING_AJAX') && isset($_POST['action']) && $_POST['action'] == 'msreader_follow_control')
                        echo 'follow';
                }

                $current_user_id = $this->user;
                update_user_option($current_user_id, 'msreader_follow', $user_follow_data, true);
                $this->increase_cache_init();
            }
            else {
                $this->message_type = 0;
                $this->message = __( 'This action could not be performed.', 'wmd_msreader' );
            }

            if(defined('DOING_AJAX') && isset($_POST['action']) && $_POST['action'] == 'msreader_follow_control')
                die();
        }
    }

    function follow_link() {
        if(
            !is_network_admin() && 
            (
                $this->options['button_visibility_for'] == 'both' || 
                (
                    $this->options['button_visibility_for'] == 'loggedin' && 
                    is_user_logged_in()
                )
            ) &&            
            (
                $this->options['button_visibility'] == 'both' || 
                (
                    $this->options['button_visibility'] == 'front' && 
                    !is_admin()
                ) || 
                (
                    $this->options['button_visibility'] == 'back' && 
                    is_admin()
                )
            )
        ) {
            $current_blog_id = get_current_blog_id();

            if($this->is_site_indexable($current_blog_id)) {
                global $wp_admin_bar;

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
                        //'parent' => 'top-secondary',
                        'title' => '<span class="ab-icon"></span><span class="current-text">'.$text.'</span><span class="hover-text">'.$hover_text.'</span>',
                        'href' => $url,
                        'meta' => array(
                            'class' => $class,
                            'title' => $hover_text.' '.__( 'this site', 'wmd_msreader' )
                        ),
                    ) 
                );
            }
        }
    }

    function get_page_title() {
 		return __( 'Followed Sites', 'wmd_msreader' );
    }

    function query() {
        global $wpdb;

        if(isset($this->args['action']) && ($this->args['action'] == 'manage' || $this->args['action'] == 'unfollow')) {
            $this->details['disable_cache'] = true;

            $followed_by_user = $this->get_followed_sites();

            if($followed_by_user) {
                $followed_by_user_ready = array();
                foreach ($followed_by_user as $blog_id) {
                    $blog_details = get_blog_details($blog_id);
                    
                    $followed_by_user_ready[] = array('link' => $this->get_module_dashboard_url(array('blog_id' => $blog_details->blog_id), 'filter_blog_author'),'title' => $blog_details->blogname, 'after' => ' <small>(<a href="'.$blog_details->siteurl.'" title="Visit this site">'.$blog_details->siteurl.'</a>)</small> <a href="'.$this->get_module_dashboard_url(array('action' => 'unfollow', 'blog_id' => $blog_details->blog_id)).'" title="'.sprintf(__('Unfollow %s', 'wmd_msreader'), $blog_details->blogname).'"><span class="msreader-widget-links-icon dashicons-no"></span></a>');
                }

                $details = $this->create_list_widget($followed_by_user_ready, array('title' => __( 'Manage followed sites', 'wmd_msreader' )));
                
                $posts = 
                '<div class="postbox msreader-widget">
                    <h3>'.$details['title'].'</h3>
                    <div class="inside">
                        <ul class="list">';
                        foreach ($details['data']['list'] as $priority => $value) {
                            if(isset($value['link']) && $value['link'])
                                $text = '<a href="'.$value['link'].'" title="View posts from this site">'.$value['title'].'</a>';
                            else
                                $text = $value['title'];

                            $posts .= '<li>'.(isset($value['before']) ? $value['before'] : '').$text.(isset($value['after']) ? $value['after'] : '').'</li>';
                        }
                        $posts .= 
                        '</ul>
                    </div>
                </div>';
            }
            else
                $posts = '';
        }
        else {
            $limit = $this->get_limit();

            $followed_by_user = $this->get_followed_sites();

            $followed_by_user_ids = implode(',', $followed_by_user);

            $query = "
                SELECT posts.BLOG_ID AS BLOG_ID, ID, post_author, post_date, post_date_gmt, post_content, post_title
                FROM $this->db_network_posts AS posts
                INNER JOIN $this->db_blogs AS blogs ON blogs.blog_id = posts.BLOG_ID
                WHERE blogs.public = 1 AND blogs.archived = 0 AND blogs.spam = 0 AND blogs.deleted = 0
                AND post_status = 'publish'
                AND post_password = ''
                AND posts.BLOG_ID IN( $followed_by_user_ids)
                ORDER BY post_date_gmt DESC
                $limit
            ";
            $query = apply_filters('msreader_'.$this->details['slug'].'_query', $query, $this->args, $limit,  $followed_by_user_ids);
            
            $posts = $wpdb->get_results($query);
        }

        return $posts;
    }

    function get_empty_message() {
        $followed_by_user = $this->get_followed_sites();
        $return = !$followed_by_user ? __( 'You are not following any sites yet.', 'wmd_msreader' ) : __('Nothing here yet!', 'wmd_msreader' );
        if($this->helpers->is_module_enabled('recent_posts') && !$followed_by_user)
            $return .= '<br/> <a href="'.$this->get_module_dashboard_url(array(), 'recent_posts').'">'.__( 'Look for something interesting', 'wmd_msreader' ).'</a>';

        return $return;
    }

    function get_followed_sites($check = 1) {
        if($this->user) {
            $user_follow_data = get_user_option('msreader_follow', $this->user);
            $user_follow_data = !$user_follow_data ? array('followed' => array(), 'unfollowed' => array()) : $user_follow_data;
            $followed_by_default = explode(',', str_replace(' ', '', $this->options['follow_by_default']));
            $followed_by_user = array_diff (array_merge($user_follow_data['followed'], $followed_by_default), $user_follow_data['unfollowed']);

            //check to see if those blogs still exists
            if($check)
                foreach ($followed_by_user as $key => $blog_id) {
                    $blog_details = get_blog_details($blog_id);
                    if(!$blog_details || ($blog_details && $blog_details->deleted)|| ($blog_details && !$blog_details->public)) {
                        unset($followed_by_user[$key]);
                    }
                }

            return array_unique($followed_by_user);
        }
        else
            return array();
    }

    function add_options_html($blank, $options) {
        $button_visibility_on_options = array('both' => __( 'Frontend and backend area', 'wmd_msreader' ), 'front' => __( 'Frontend area', 'wmd_msreader' ), 'back' => __( 'Backend area', 'wmd_msreader' ));
        $button_visibility_for_options = array('both' => __( 'Logged in and logged out users', 'wmd_msreader' ), 'loggedin' => __( 'Logged in users', 'wmd_msreader' ));

        return '
            <label for="wmd_msreader_options_'.$this->details['slug'].'_follow_by_default">'.__( 'IDs of sites followed by default (Comma separated)', 'wmd_msreader' ).':<br/>
            <input type="text" class="smallt-ext ltr" name="wmd_msreader_options[modules_options]['.$this->details['slug'].'][follow_by_default]" value="'.$options['follow_by_default'].'"  id="wmd_msreader_options_'.$this->details['slug'].'_follow_by_default"/></label>
            <br/>
            <label for="wmd_msreader_options_'.$this->details['slug'].'_button_visibility">'.__( 'Display follow button on', 'wmd_msreader' ).':</label><br/>
            <select name="wmd_msreader_options[modules_options]['.$this->details['slug'].'][button_visibility]" id="wmd_msreader_options_'.$this->details['slug'].'_button_visibility">
            '.$this->helpers->the_select_options($button_visibility_on_options, $options['button_visibility'], 0).'
            </select>
            <br/>
            <label for="wmd_msreader_options_'.$this->details['slug'].'_button_visibility_for">'.__( 'Display follow button for', 'wmd_msreader' ).':</label><br/>
            <select name="wmd_msreader_options[modules_options]['.$this->details['slug'].'][button_visibility_for]"  id="wmd_msreader_options_'.$this->details['slug'].'_button_visibility_for">
            '.$this->helpers->the_select_options($button_visibility_for_options, $options['button_visibility_for'], 0).'
            </select>
        ';
    }
}