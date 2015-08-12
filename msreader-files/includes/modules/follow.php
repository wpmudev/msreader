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
    ),
    'type' => 'query'
);

class WMD_MSReader_Module_Follow extends WMD_MSReader_Modules {

    var $user_follow_data;

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

        add_filter( 'msreader_dashboard_reader_sidebar_widgets', array($this,'add_widget'), 20 );
        add_filter( 'msreader_widget_recent_posts_arg_modules', array($this,'widget_recent_posts_add_arg_modules'), 20 );

        add_action( 'admin_init', array($this, 'list_manage'), 20, 1 );

        add_action( 'wp_ajax_msreader_follow_control', array($this, 'follow_control'), 20, 1 );
        add_action( 'wp_ajax_msreader_list_control', array($this, 'list_control'), 20, 1 );
        add_action( 'wp_ajax_msreader_list_html', array($this, 'list_html'), 20, 1 );
    }

    function add_link_to_widget($widgets) {
        if(!$this->helpers->is_module_enabled('user_widget')) {
            $link = $this->get_module_dashboard_url(array('action' => 'manage'));
            $active = ($this->helpers->is_page_link_active($link)) ? 'class="active" ' : '';
            $widgets['reader']['data']['list'][$this->details['slug']] = $this->create_link_for_main_widget('</a><a '.$active.'href="'.$this->get_module_dashboard_url(array('action' => 'manage')).'" title="'.__('Manage followed sites', 'wmd_msreader').'"><span class="msreader-widget-links-element msreader-widget-links-icon dashicons-admin-generic"></span>');
        }
        else
            $widgets['reader']['data']['list'][$this->details['slug']] = $this->create_link_for_main_widget();
        
        return $widgets;
    }

    function add_blog_link($name, $post) {
        return $name.' '.$this->get_follow_button($post->blog_details->blog_id);
    }

    function get_follow_button($blog_id, $content_type = false) {
        $followed_by_user = $this->get_followed_sites();
        $this->user_follow_data = $this->get_user_follow_data();

        if(!in_array($blog_id, $followed_by_user)) {
            $text = __('Follow', 'wmd_msreader');
            $hover_text = __('Follow', 'wmd_msreader');
            $class = '';
            $manage_class = '';
        }
        else {
            $text = __('Following', 'wmd_msreader');
            $hover_text = __('Unfollow', 'wmd_msreader');
            $class = ' following';    
            $manage_class = '';    
        }

        if(isset($this->user_follow_data['association']['blog'.$blog_id]) && count($this->user_follow_data['association']['blog'.$blog_id]) > 0) {
            $text_manage = __('Manage lists', 'wmd_msreader');
            $text_manage_title = __('Manage lists for this site', 'wmd_msreader');
        }
        else {
            $text_manage = __('Add to lists', 'wmd_msreader');
            $text_manage_title = __('Add sites to the lists in Reader', 'wmd_msreader');
        }

        $content = 
        '<a class="add-new-h2 button-small msreader-follow-button'.$class.'" title="'.__('Follow/Unfollow this site in Reader', 'wmd_msreader').'" href="#">
            <span class="msreader-follow-icon"></span> <span class="current-text">'.$text.'</span><span class="hover-text">'.$hover_text.'</span>
        </a>&nbsp;
        <div class="msreader-manage-follow msreader-popup-container">
            <a class="add-new-h2 msreader-show button-small msreader-manage-follow-button'.$manage_class.'" title="'.$text_manage_title.'" href="#">
                <span class="dashicons dashicons-pressthis"></span> <span class="current-text">'.$text_manage.'</span>&nbsp;
            </a>
            <div class="msreader-manage-follow-form msreader-popup">
                <a class="msreader-close-popup msreader-hide dashicons dashicons-no-alt" href="#"></a>
                <div class="post-spinner spinner spinner-save" style="display: none;"></div>
                <div class="msreader-popup-content">

                </div>
            </div>
        </div>';

        return $content;
    }

    function add_following_stats($user_info) {
        $link = $this->get_module_dashboard_url(array('action' => 'manage'));
        $active = ($this->helpers->is_page_link_active($link)) ? ' active' : '';

        $followed_by_user = $this->get_followed_sites(1);

        $user_info['stats']['following'] = 
        '<div class="user-stat-follow user-stat'.$active.'"><a title="View list and manage followed sites" href="'.$link.'"><h4>'.__( 'Following', 'wmd_msreader' ).'</h4>
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
            '.msreader-follow-button {min-width:65px;}
            .msreader-follow-button .hover-text {display:none;}
            .msreader-follow-button:hover .hover-text {display:inline;}
            .msreader-follow-button:hover .current-text {display:none;}
            .msreader-follow-icon:before {content: "\f487"; font-family: dashicons; font-size:10px; position:relative; top: 1px;}
            .msreader-follow-button.following .msreader-follow-icon:before {color: #d54e21;}
            .msreader-follow-button.following:hover .msreader-follow-icon:before {color: #c4c4c4;}
            .msreader-follow-button.following:hover {color: #c4c4c4;}
            #msreader-widget-my-follow-lists .msreader-widget-links-element {float:right;}
            .msreader-widget .blog-info {position: absolute; right: 0; padding-left: 10px; background: #fff;}
            .msreader-manage-follow {display:inline; position:relative;}
            #msreader-follow-manage-widget li {position:relative;}
            .msreader-create-list-button {margin-left:0px;}
            .msreader-manage-follow-button {min-width: 85px;}
            .msreader-create-list {margin-top:5px; display:inline-block; position:relative;}
            .msreader-create-list-form textarea {font-size:10px;}
            .msreader-manage-follow-form {
                bottom: 30px;
                right: 0;
                width:300px;
                min-height: 90px;
                margin-right: -110px;
            }
            .msreader-create-list-form {
                bottom: 30px;
                left: -23px;
                width:300px;
                min-height: 90px;
            }
            .msreader-manage-follow-form.msreader-popup-bottom, .msreader-post-overlay .msreader-manage-follow-form {
                bottom: auto;
                top:30px;
                margin-right: 0;
            }
            .msreader-post-overlay .msreader-manage-follow-form.msreader-popup-left {
                right:auto;
                left:0;
            }
            @media (max-width: 782px) {
                .msreader-manage-follow-form, .msreader-create-list-form {
                    position: relative;
                    width: 100%;
                    bottom: 0 !important;
                    top: auto !important;
                    margin: 10px 0 0 0;
                    left:auto;
                }
                .msreader-post-overlay .msreader-blogname {
                    display:block !important;
                    margin-left: 0px;
                }
                .msreader-post-overlay .msreader-blogname a {
                    margin-left:0px;
                    margin-right:4px;
                }
            }
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
                    var blog_id = button.parents(".msreader-post, .blog-info").attr("data-blog_id");
                    var post_id = button.parents(".msreader-post").attr("data-post_id");

                    follow_control(blog_id, post_id, 0, button, 0);
                });
                $(".msreader-post-overlay").on("click", ".msreader-follow-button", function(event) {
                    event.preventDefault();

                    var button = $(this);
                    var blog_id = msreader_main_query.current_post.attr("data-blog_id");
                    var post_id = msreader_main_query.current_post.attr("data-post_id");

                    follow_control(blog_id, post_id, 0, button, 0);
                });

                var manage_follow_blog_id = 0;
                $(".msreader-posts").on("click", ".msreader-manage-follow-button", function(event) {
                    event.preventDefault();

                    var button = $(this);
                    manage_follow_blog_id = button.parents(".msreader-post, .blog-info").attr("data-blog_id");

                    list_html(manage_follow_blog_id, button);
                });
                $(".msreader-post-overlay").on("click", ".msreader-manage-follow-button", function(event) {
                    event.preventDefault();

                    var button = $(this);
                    manage_follow_blog_id = msreader_main_query.current_post.attr("data-blog_id");
                    
                    list_html(manage_follow_blog_id, button);
                });
                $(".msreader-posts").on("click", ".msreader-manage-follow-save", function(event) {
                    event.preventDefault();

                    var manage_form = $(this).parents('.msreader-manage-follow-form');

                    var button = $(this);
                    var lists_ids = new Array();
                    $.each(manage_form.find('input[name="lists_ids[]"]:checked'), function() {
                        lists_ids.push($(this).val());
                    });
                    var new_list_name = manage_form.find('input[name="new_list_name"]').val();

                    list_control(manage_follow_blog_id, lists_ids, new_list_name, button);
                });
                $(".msreader-post-overlay").on("click", ".msreader-manage-follow-save", function(event) {
                    event.preventDefault();

                    var manage_form = $(this).parents('.msreader-manage-follow-form');

                    var button = $(this);
                    var lists_ids = new Array();
                    $.each(manage_form.find('input[name="lists_ids[]"]:checked'), function() {
                        lists_ids.push($(this).val());
                    });
                    var new_list_name = manage_form.find('input[name="new_list_name"]').val();

                    list_control(manage_follow_blog_id, lists_ids, new_list_name, button);
                });
            });

            function follow_control(blog_id, post_id, action, button, frontend) {
                if(!action && button && button.hasClass('following'))
                        var action = 'unfollow';
                    else
                        var action = 'follow';

                if(blog_id && action) {
                    if(!frontend)
                        $(".msreader_module_follow .msreader-widget .spinner, .msreader-post-header-navigation .spinner, .msreader-post[data-blog_id='"+blog_id+"'][data-post_id='"+post_id+"'] .spinner").show();
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
                        if(!frontend)
                            $(".msreader_module_follow .msreader-widget .spinner, .msreader-post-header-navigation .spinner, .msreader-post[data-blog_id='"+blog_id+"'][data-post_id='"+post_id+"'] .spinner").fadeOut(200, function() {$(this).hide()});

                        if(response && response != 0) {
                            var all_buttons = $(".msreader-post[data-blog_id='"+blog_id+"'] .msreader-follow-button, .msreader-post-overlay .msreader-follow-button, .msreader_module_follow .msreader-widget .blog-info[data-blog_id='"+blog_id+"'] .msreader-follow-button");
                            if(response == 'following') {
                                all_buttons.addClass('following');
                                all_buttons.find('.current-text').text(msreader_follow.following);
                                all_buttons.find('.hover-text').text(msreader_follow.unfollow);

                                if($('.msreader_module_follow .msreader-post').length)
                                    msreader.add_post_to_list(0, 0, $(".msreader-post[data-blog_id='"+blog_id+"']"));
                                else if($('.msreader_module_follow .msreader-widget').length) {
                                    all_buttons.parents('li').css('opacity', 1);
                                }

                                var user_follow_stat = $('.user-stat-follow p');
                                if(user_follow_stat.length)
                                    user_follow_stat.text(parseInt(user_follow_stat.text())+1);
                            }
                            if(response == 'follow') {
                                all_buttons.removeClass('following');
                                all_buttons.find('.current-text').text(msreader_follow.follow);
                                all_buttons.find('.hover-text').text(msreader_follow.follow);

                                if($('.msreader_module_follow .msreader-post').length)
                                    msreader.remove_post_from_list(0, 0, $(".msreader-post[data-blog_id='"+blog_id+"']"));
                                else if($('#msreader-follow-manage-widget').length && $('#msreader-follow-manage-widget').attr('data-follow-list') == 0)
                                    all_buttons.parents('li').css('opacity', 0.5);

                                var user_follow_stat = $('.user-stat-follow p');
                                if(user_follow_stat.length)
                                    user_follow_stat.text(parseInt(user_follow_stat.text())-1);
                            }
                        }
                    });
                }
            }

            function list_control(blog_id, lists_ids, new_list_name, button) {
                var manage_form = button.parents('.msreader-manage-follow-form');
                if(blog_id && (lists_ids || new_list_name)) {
                    manage_form.find('.spinner').show();

                    feature_details = {
                        blog_id: blog_id,
                        lists_ids: lists_ids,
                        new_list_name: new_list_name
                    }
                    args = {
                        source: "msreader",
                        module: "follow",
                        action: "msreader_list_control",
                        args: feature_details,
                        nonce: button.attr('data-nonce')
                    };

                    $.post(ajaxurl, args, function(response) {
                        if(response != false) {
                            $(".msreader-post[data-blog_id='"+blog_id+"'] .msreader-manage-follow-button .current-text, msreader-post-overlay .msreader-manage-follow-button .current-text").text(response);
                            manage_form.find('.spinner').hide();
                            manage_form.find('.msreader-close-popup').click();

                            if($('#msreader-follow-manage-widget').length) {
                                var list_id = $('#msreader-follow-manage-widget').attr('data-follow-list');

                                if(list_id != 0)
                                    if($.inArray(list_id, lists_ids) === -1)
                                        $("#msreader-follow-manage-widget .blog-info[data-blog_id='"+blog_id+"'] .msreader-follow-button").parents('li').find('a').css('opacity', 0.5);
                                    else
                                        $("#msreader-follow-manage-widget .blog-info[data-blog_id='"+blog_id+"'] .msreader-follow-button").parents('li').find('a').css('opacity', 1);
                            }
                            else if($('.msreader_module_follow .msreader-post').length && msreader_main_query.args[0]) {
                                if($.inArray(msreader_main_query.args[0], lists_ids) === -1)
                                    msreader.remove_post_from_list(0, 0, $(".msreader-post[data-blog_id='"+blog_id+"']"));
                                else {
                                    msreader.add_post_to_list(0, 0, $(".msreader-post[data-blog_id='"+blog_id+"']"));
                                }
                            }

                            if(new_list_name.length)
                            msreader.refresh_sidebar();
                        }
                        else {
                            manage_form.find('.msreader-popup-content').html(response);
                        }
                    });
                }
                else
                    manage_form.find('.msreader-close-popup').click();
            }

            function list_html(blog_id, button) {

                if(blog_id) {
                    var manage_form = button.parent().find('.msreader-manage-follow-form');

                    manage_form.find('.spinner').show();

                    feature_details = {
                        blog_id: blog_id
                    }
                    args = {
                        source: "msreader",
                        module: "follow",
                        action: "msreader_list_html",
                        args: feature_details
                    };

                    $.post(ajaxurl, args, function(response) {
                        manage_form.find('.spinner').hide();

                        if(response && response != 0) {
                            manage_form.find('.msreader-popup-content').html(response);
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
        $action = $action ? $action : (isset($this->args['action']) ? $this->args['action'] : false);
        $blog_id = $blog_id ? $blog_id : (isset($this->args['blog_id']) ? $this->args['blog_id'] : false);

        //check if we are following/unfollowing anything
        if(is_numeric($blog_id) && $blog_id && ($action == 'follow' || $action == 'unfollow')) {
            $this->user_follow_data = $this->get_user_follow_data();
            
            $blog_details = get_blog_details($blog_id);
            if($blog_details){
                $this->message_type = 1;

                if($action == 'follow') {
                    if(!in_array($blog_id, $this->user_follow_data['followed']))
                        $this->user_follow_data['followed'][] = $blog_id;

                    $unfollowed_key = array_search($blog_id, $this->user_follow_data['unfollowed']);
                    if($unfollowed_key !== false)
                        unset($this->user_follow_data['unfollowed'][$unfollowed_key]);

                    $this->message = $blog_details->blogname.' '.__( 'is now being followed.', 'wmd_msreader' ).' <a href="'.get_site_url($blog_id).'">'.__( 'Visit the site', 'wmd_msreader' ).'</a>.';
                    
                    if(defined('DOING_AJAX') && isset($_POST['action']) && $_POST['action'] == 'msreader_follow_control')
                        echo 'following';
                }
                elseif($action == 'unfollow') {
                    if(!in_array($blog_id, $this->user_follow_data['unfollowed']))
                        $this->user_follow_data['unfollowed'][] = $blog_id;

                    $followed_key = array_search($blog_id, $this->user_follow_data['followed']);
                    if($followed_key !== false)
                        unset($this->user_follow_data['followed'][$followed_key]);

                    $this->message = $blog_details->blogname.' '.__( 'is no longer followed.', 'wmd_msreader' ).' <a href="'.get_site_url($blog_id).'">'.__( 'Visit the site', 'wmd_msreader' ).'</a>.';

                    if(defined('DOING_AJAX') && isset($_POST['action']) && $_POST['action'] == 'msreader_follow_control')
                        echo 'follow';
                }

                update_user_option(get_current_user_id(), 'msreader_follow', $this->user_follow_data, true);
                $this->increase_cache_init();
            }
            else {
                $this->message_type = 0;
                $this->message = __( 'This action could not be performed.', 'wmd_msreader' );
            }
        }

        if(defined('DOING_AJAX') && isset($_POST['action']) && $_POST['action'] == 'msreader_follow_control')
            die();
    }

    function list_control($blog_id = false, $lists_ids = array(), $new_list_name = '') {
        $lists_ids = $lists_ids ? $lists_ids : (isset($this->args['lists_ids']) ? $this->args['lists_ids'] : array());
        $new_list_name = $new_list_name ? $new_list_name : (isset($this->args['new_list_name']) ? $this->args['new_list_name'] : '');
        $blog_id = $blog_id ? $blog_id : (isset($this->args['blog_id']) ? $this->args['blog_id'] : false);

        $this->message_type = 0;
        $this->message = __( 'This action could not be performed.', 'wmd_msreader' );

        check_ajax_referer( 'manage-follow-save', 'nonce' );

        if(is_numeric($blog_id) && $blog_id) {
            $this->user_follow_data = $this->get_user_follow_data();
            
            $blog_details = get_blog_details($blog_id);
            if($blog_details){
                if($new_list_name)
                    $lists_ids[] = $this->prepare_new_list($new_list_name);
                if(count($lists_ids) > 0) {
                    $this->prepare_lists_for_blogs($lists_ids, array($blog_id), 1);
                }
                else
                    if(isset($this->user_follow_data['association']['blog'.$blog_id]))
                        unset($this->user_follow_data['association']['blog'.$blog_id]);

                update_user_option(get_current_user_id(), 'msreader_follow', $this->user_follow_data, true);
                $this->increase_cache_init();
                
                $this->message_type = 1;
                $this->message = __( 'Blog lists updated.', 'wmd_msreader' );

                if(defined('DOING_AJAX') && isset($_POST['action']) && $_POST['action'] == 'msreader_list_control')
                    if(count($lists_ids) > 0)
                        echo __( 'Manage lists', 'wmd_msreader' );
                    else
                        echo __( 'Add to lists', 'wmd_msreader' );
            }
            else {
                if(defined('DOING_AJAX') && isset($_POST['action']) && $_POST['action'] == 'msreader_list_control')
                    echo false;
            }
        }
        else {
            if(defined('DOING_AJAX') && isset($_POST['action']) && $_POST['action'] == 'msreader_list_control')
                echo false;
        }

        if(defined('DOING_AJAX') && isset($_POST['action']) && $_POST['action'] == 'msreader_list_control')
            die();
    }

    function list_manage() {
        if(isset($_POST['msreader_follow_manage']) && wp_verify_nonce($_POST['msreader_follow_manage'], 'msreader_follow_manage') && isset($_POST['follow_list'])) {
            $this->message_type = 0;
            $this->message = __( 'This action could not be performed.', 'wmd_msreader' );

            $this->user_follow_data = $this->get_user_follow_data();
            $current_user_id = get_current_user_id();

            if(isset($_POST['change_name'])) {
                $this->message_type = 1;
                $this->message = __( 'List name changed.', 'wmd_msreader' );

                $this->user_follow_data['lists'][$_POST['follow_list']] = esc_attr($_POST['name']);

                update_user_option($current_user_id, 'msreader_follow', $this->user_follow_data, true);
            }
            elseif(isset($_POST['delete'])) {
                $this->message_type = 0;
                $this->message = __( 'You need to confirm by marking checkbox before deleting this list.', 'wmd_msreader' );
                if(isset($_POST['delete_confirm']) && $_POST['delete_confirm']) {
                    unset($this->user_follow_data['lists'][$_POST['follow_list']]);

                    foreach ($this->user_follow_data['association'] as $blog_id => $list_ids) {
                        $list_exists_key = array_search($_POST['follow_list'], $list_ids);
                        if($list_exists_key !== false)
                            unset($this->user_follow_data['association'][$blog_id][$list_exists_key]);
                    }

                    update_user_option($current_user_id, 'msreader_follow', $this->user_follow_data, true);

                    $this->message_type = 1;
                    $this->message = __( 'List deleted.', 'wmd_msreader' );

                    wp_redirect(add_query_arg('msg', urlencode($this->message), $this->get_module_dashboard_url(array(), $this->helpers->get_default_module())));
                    exit();                 
                }      
            }
            elseif(isset($_POST['add_sites'])) {
                $blogs_ids = $this->get_blog_ids_from_textarea($_POST['list_blogs_links']);
                if($blogs_ids) {
                    $count = $this->prepare_lists_for_blogs(array($_POST['follow_list']), $blogs_ids);

                    $this->message_type = 1;
                    $this->message = sprintf( _n('%d blog has been added to the list.', '%d blogs have been added to the list.', count($count), 'wmd_msreader'), count($blogs_ids));

                    update_user_option($current_user_id, 'msreader_follow', $this->user_follow_data, true);
                }
                else {
                    $this->message_type = 0;
                    $this->message = sprintf(__( 'We could not detect any site that belongs to %s.', 'wmd_msreader' ), get_site_option( 'site_name', 'network' ));
                }   
            }
        }
        elseif(isset($_POST['msreader_follow_list_create']) && wp_verify_nonce($_POST['msreader_follow_list_create'], 'msreader_follow_list_create')) {
            if(!isset($_POST['new_list_name']) || !$_POST['new_list_name']) {
                $this->message_type = 0;
                $this->message = __( 'List name must be filled.', 'wmd_msreader' );
            }
            else {
                $this->user_follow_data = $this->get_user_follow_data();

                $name_exists_key = array_search($_POST['new_list_name'], $this->user_follow_data['lists']);
                if(!$name_exists_key) {
                    $new_list_id = $this->prepare_new_list($_POST['new_list_name']);

                    if(isset($_POST['list_blogs_links']) && $_POST['list_blogs_links']) {
                        $blogs_ids = $this->get_blog_ids_from_textarea($_POST['list_blogs_links']);
                        $count = $this->prepare_lists_for_blogs(array($new_list_id), $blogs_ids);

                        $this->message_type = 1;

                        $this->message = sprintf( _n('List with %d blog has been created.', 'List with %d blogs have been created.', count($count), 'wmd_msreader'), count($blogs_ids));
                        $redirect_url = $this->get_module_dashboard_url($new_list_id);
                    }
                    else {
                        $this->message_type = 1;
                        $this->message = __( 'List has been created.', 'wmd_msreader' );
                        $redirect_url = $this->get_module_dashboard_url(array('action' => 'manage', 'follow_list' => $new_list_id));
                    }

                    update_user_option(get_current_user_id(), 'msreader_follow', $this->user_follow_data, true);

                    wp_redirect(add_query_arg('msg', urlencode($this->message), $redirect_url));
                    exit();
                }
                else {
                    $this->message_type = 0;
                    $this->message = sprintf(__( 'List already exists. You can manage it <a href="%s">here</a>.', 'wmd_msreader' ), $this->get_module_dashboard_url(array('action' => 'manage', 'follow_list' => $name_exists_key)));
                }      
            }
        }
    }

    function list_html($blog_id = false) {
        $blog_id = $blog_id ? $blog_id : (isset($this->args['blog_id']) ? $this->args['blog_id'] : false);

        if(is_numeric($blog_id) && $blog_id) {
            $this->user_follow_data = $this->get_user_follow_data();

            if(isset($this->user_follow_data['lists']) && count($this->user_follow_data['lists']) > 0) { 
                $blog_lists = isset($this->user_follow_data['association']['blog'.$blog_id]) ? $this->user_follow_data['association']['blog'.$blog_id] : array();
                
            ?>
                <div class="msreader-popup-inside">
                    <h4><?php _e('Select lists for this site', 'wmd_msreader'); ?></h4>
                    <ul class="cat-checklist category-checklist">
                        <?php foreach ($this->user_follow_data['lists'] as $list_id => $list_name) { ?>
                            <li class="list-<?php echo $list_id; ?>">
                                <label class="selectit">
                                    <input value="<?php echo $list_id; ?>" type="checkbox" name="lists_ids[]" <?php if(in_array($list_id, $blog_lists)) echo 'checked'; ?>>
                                    <span class="list-name"><?php echo $list_name; ?></span>
                                </label>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <hr/>
            <?php 
            }
            ?>
            <div class="msreader-popup-inside">
                <h4 class="msreader-show" href="#"><?php _e('Add site to new list', 'wmd_msreader'); ?></h4>
                <input name="new_list_name" id="new-list-name" type="text" placeholder="<?php _e('Type list name here', 'wmd_msreader'); ?>"/>
            </div>
            <hr/>
            <div class="msreader-popup-inside">
                <button data-nonce="<?php echo wp_create_nonce( 'manage-follow-save' ); ?>" class="button button-primary right msreader-manage-follow-save"><?php _e('Save', 'wmd_msreader'); ?></button>
            </div>
        <?php
        }

        if(defined('DOING_AJAX') && isset($_POST['action']) && $_POST['action'] == 'msreader_list_html')
            die();
    }

    function prepare_new_list($new_list_name) {
        if($new_list_name) {
            $this->user_follow_data = $this->get_user_follow_data();

            $name_exists_key = array_search($new_list_name, $this->user_follow_data['lists']);
            if($name_exists_key) {
                return $name_exists_key;
            }
            else {
                end($this->user_follow_data['lists']);
                $last_key = str_replace('list', '', key($this->user_follow_data['lists']));
                $new_key = $last_key + 1;

                $new_key = 'list'.$new_key;
                $this->user_follow_data['lists'][$new_key] = esc_attr($new_list_name);

                ksort($this->user_follow_data['lists'], SORT_NATURAL);

                return $new_key;
            }
        }

        return false;
    }

    function prepare_lists_for_blogs($lists_ids, $blogs_ids, $delete_old = false) {
        $this->user_follow_data = $this->get_user_follow_data();

        if(is_array($lists_ids) && is_array($blogs_ids)) {
            $count = array();
            foreach($blogs_ids as $blog_id) {
                if(!is_numeric($blog_id) || !$blog_id)
                    continue;

                if($delete_old || !isset($this->user_follow_data['association']['blog'.$blog_id]))
                    $this->user_follow_data['association']['blog'.$blog_id] = array();

                foreach ($lists_ids as $list_id)
                    if(array_key_exists($list_id, $this->user_follow_data['lists']) && !in_array($list_id, $this->user_follow_data['association']['blog'.$blog_id])) {
                        $this->user_follow_data['association']['blog'.$blog_id][] = $list_id;

                        $count['blog'.$blog_id] = !isset($count['blog'.$blog_id]) ? 1 : $count['blog'.$blog_id]+1;
                    }
            }

            return $count;
        }

        return false;
    }

    function get_blog_ids_from_textarea($textarea) {
        global $wpdb, $dm_map;

        $is_subdomain_install = is_subdomain_install();
        $current_site = get_current_site();

        $blog_links = explode("\n", str_replace(array(' ', 'http://', 'https://') , '', strtolower($textarea)));
        $blog_links = array_unique($blog_links);
        $blogs_ids = array();

        foreach($blog_links as $blog_link) {
            $blog_link_parts = parse_url(str_replace(array("\r", "\n"), "", 'http://'.$blog_link));

            if(isset($blog_link_parts['host'])) {
                $is_blog_name_only = strpos($blog_link_parts['host'], ".") !== false ? false : true;

                if($is_subdomain_install) {
                    if($is_blog_name_only)
                        $host = $blog_link_parts['host'].'.'.preg_replace('|^www\.|', '', $current_site->domain);
                    else
                        $host = $blog_link_parts['host'];

                    $path = '/';
                }
                else
                    if($is_blog_name_only) {
                        $host = $current_site->domain;
                        $path = $current_site->path.$blog_link_parts['host'].'/';
                    }
                    else {
                        $host = $blog_link_parts['host'];
                        $path = isset($blog_link_parts['path']) ? rtrim($blog_link_parts['path'], '/').'/' : false;
                    }
                

                $blog_id = get_blog_id_from_url( $host, $path );
                if($blog_id)
                    $blogs_ids[] = $blog_id;
                elseif(isset($dm_map) && $dm_map instanceof domain_map) {
                    $blog_id = $wpdb->get_var($wpdb->prepare("SELECT blog_id FROM {$dm_map->dmt} WHERE domain = %s", $blog_link_parts['host']));
                    if($blog_id)
                        $blogs_ids[] = $blog_id;
                }
            }
        }

        return $blogs_ids;
    }

    function add_widget($widgets) {
        $this->user_follow_data = $this->get_user_follow_data();
        
            $lists = array();

        $user_follow_data_lists = $this->user_follow_data['lists'];
        asort($user_follow_data_lists, SORT_NATURAL);
        foreach ($user_follow_data_lists as $list_id => $list_name) {
                $link = $this->get_module_dashboard_url(array('action' => 'manage', 'follow_list' => $list_id));
                $active = ($this->helpers->is_page_link_active($link)) ? 'class="active" ' : '';

                $lists[] = array('args' => $list_id,'title' => $list_name.'</a><a '.$active.'href="'.$link.'" title="'.__('Manage this list', 'wmd_msreader').'"><span class="msreader-widget-links-element msreader-widget-links-icon dashicons-admin-generic"></span>');
            }

        //this is element that will allow to create new lists
        $lists[] = 
            array(
                'title' => 
                    '<div class="msreader-create-list msreader-popup-container">
                        <a class="add-new-h2 msreader-show button-small msreader-create-list-button" title="Create new list" href="#">
                            <span class="dashicons dashicons-pressthis"></span>
                            '.__('Create new list', 'wmd_msreader').'
                        </a>
                        <div class="msreader-create-list-form msreader-popup">
                            <a class="msreader-close-popup msreader-hide dashicons dashicons-no-alt" href="#"></a>
                            <div class="post-spinner spinner spinner-save" style="display: none;"></div>
                            <div class="msreader-popup-content">
                                <form method="post" action="">
                                    '.wp_nonce_field( 'msreader_follow_list_create', 'msreader_follow_list_create', false ).'

                                    <div class="msreader-popup-inside">
                                        <h4 class="msreader-show" href="#">'.__('Name of the new list', 'wmd_msreader').'</h4>
                                        <input name="new_list_name" id="new-list-name" type="text" placeholder="'.__('Type list name here', 'wmd_msreader').'"/>
                                    </div>
                                    <hr/>
                                    <div class="msreader-popup-inside">
                                        <h4 class="msreader-show" href="#">'.__('Links to sites that will be added to this list', 'wmd_msreader').'</h4>
                                        <textarea name="list_blogs_links" placeholder="'.sprintf(__('Add one %s site per line please.', 'wmd_msreader'), get_site_option( 'site_name', 'network' )).'"></textarea>
                                    </div>
                                    <hr/>
                                    <div class="msreader-popup-inside">
                                        <input type="submit" class="button button-primary right" name="create_list" value="'.__('Create', 'wmd_msreader').'"/>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>'
            );

        $widgets['my-follow-lists'] = $this->create_list_widget($lists, array('title' => __('My Lists', 'wmd_msreader')));

        return $widgets;
    }

    function widget_recent_posts_add_arg_modules($arg_modules) {
        if(is_user_logged_in()) {
            $this->user_follow_data = $this->get_user_follow_data();
            $user_follow_data_lists = $this->user_follow_data['lists'];

            asort($user_follow_data_lists, SORT_NATURAL);
            foreach ($user_follow_data_lists as $list_id => $list_name) {
                $arg_modules[] = array('class' => 'my_lists', 'value' => $this->details['slug'].'|'.$list_id, 'title' => __('My Lists', 'wmd_msreader').': '.$list_name);
            }
        }

        return $arg_modules;
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
        $follow_list_key = isset($this->args['follow_list']) ? $this->args['follow_list'] : (isset($this->args[0]) ? $this->args[0] : false);
        if($follow_list_key) {
            $this->user_follow_data = $this->get_user_follow_data();
            if(isset($this->user_follow_data['lists'][$follow_list_key])) {
                $list_name = $this->user_follow_data['lists'][$follow_list_key];
            }          
        }

        if(isset($this->args['action']) && $this->args['action'] == 'manage') {
            if(isset($list_name)) {
                $title = __( 'My Lists', 'wmd_msreader' ).': <span>'.__( 'Manage', 'wmd_msreader' ).' - '.$list_name.'</span>';
            }
            else
                $title = __( 'Followed Sites', 'wmd_msreader' ).': <span>'.__( 'Manage', 'wmd_msreader' ).'</span>';
        }
        elseif(isset($list_name)) {
            $title = __( 'My Lists', 'wmd_msreader' ).': <span>'.$list_name.'</span>';
        }
        else
            $title = __( 'Followed Sites', 'wmd_msreader' );

        return $title;
    }

    function query() {
        global $wpdb;

        $posts = '';

        if(isset($this->args['action']) && ($this->args['action'] == 'manage' || $this->args['action'] == 'unfollow')) {
            $this->details['disable_cache'] = true;

            if(isset($this->args['follow_list'])) {
                $this->user_follow_data = $this->get_user_follow_data();
                if(isset($this->user_follow_data['lists'][$this->args['follow_list']])) {
                    $list_name = $this->user_follow_data['lists'][$this->args['follow_list']];
                    $follow_list = $this->args['follow_list'];
                }

            }

            $followed_by_user = $this->get_followed_sites(1, $follow_list);

            if($followed_by_user) {
                $followed_by_user_ready = array();
                foreach ($followed_by_user as $blog_id) {
                    $blog_details = get_blog_details($blog_id);
                    
                    $followed_by_user_ready[] = array('link' => $this->get_module_dashboard_url(array('blog_id' => $blog_details->blog_id), 'filter_blog_author'),'title' => wp_trim_words($blog_details->blogname, 10), 'after' => ' <span class="blog-info" data-blog_id="'.$blog_details->blog_id.'"><a class="add-new-h2 button-small following" href="'.$blog_details->siteurl.'" title="Visit this site"><span class="dashicons dashicons-admin-links"></span></a> &nbsp;'.$this->get_follow_button($blog_details->blog_id, 'manage_list').'</span>');
                }
                usort($followed_by_user_ready, array($this->helpers, 'array_sort_by_sub_title'));

                if(isset($follow_list)) {
                    $title = ' '.__( 'Manage sites in this list', 'wmd_msreader' );
                    $this->user_follow_data = $this->get_user_follow_data();
                    $list_name = $this->user_follow_data['lists'][$follow_list]; 
                }
                else
                    $title = __( 'Manage followed sites', 'wmd_msreader' );

                $details = $this->create_list_widget($followed_by_user_ready, array('title' => $title));
                
                $posts = 
                '<div id="msreader-follow-manage-widget" class="postbox msreader-widget" data-follow-list="'.(isset($follow_list) ? $follow_list : 0).'">
                    <div class="post-spinner spinner spinner-save" style="display: none;"></div>
                    <h3>'.$details['title'].'</h3>
                    <div class="inside">
                        <ul class="list">';
                        foreach ($details['data']['list'] as $priority => $value) {
                            if(isset($value['link']) && $value['link'])
                                $text = '<a class="site-url" href="'.$value['link'].'" title="'.__( 'View posts from this site', 'wmd_msreader' ).'">'.$value['title'].'</a>';
                            else
                                $text = $value['title'];

                            $posts .= '<li>'.(isset($value['before']) ? $value['before'] : '').$text.(isset($value['after']) ? $value['after'] : '').'</li>';
                        }
                        $posts .= 
                        '</ul>
                    </div>
                </div>';
            }

            if(isset($follow_list)) {
                $posts .= 
                '<form method="post" action="">
                    '.wp_nonce_field( 'msreader_follow_manage', 'msreader_follow_manage', false ).'
                    <input type="hidden" name="follow_list" value="'.esc_attr($this->args['follow_list']).'"/>
                    <div class="postbox msreader-widget">
                        <h3>'.__( 'Add sites to this list', 'wmd_msreader' ).'</h3>
                        <div class="inside">
                            '.__('Links to sites that will be added to this list', 'wmd_msreader').'<br/>
                            <textarea name="list_blogs_links" placeholder="'.sprintf(__('Add one %s site per line please.', 'wmd_msreader'), get_site_option( 'site_name', 'network' )).'"></textarea><br/>
                            <input type="submit" class="button button-secondary button-small" name="add_sites" value="'.__( 'Add sites', 'wmd_msreader' ).'"/>
                        </div>
                    </div>
                    <div class="postbox msreader-widget">
                    <h3>'.__( 'Edit this list', 'wmd_msreader' ).'</h3>
                    <div class="inside">
                        '.__( 'Modify name:', 'wmd_msreader' ).' <input name="name" type="text" value="'.esc_attr($list_name).'"/> <input type="submit" class="button button-secondary button-small" name="change_name" value="'.__( 'Save', 'wmd_msreader' ).'"/><br/>
                            '.__( 'Delete it: <small>Mark this checkbox to confirm', 'wmd_msreader' ).' <input name="delete_confirm" type="checkbox" value="1"/> '.__( 'and click</small>', 'wmd_msreader' ).' <input type="submit" class="button button-secondary button-small" name="delete" value="'.__( 'Delete', 'wmd_msreader' ).'"/><br/>
                        </div>
                    </div>
                </form>';                    
            }
                
        }
        else {
            $limit = $this->get_limit();
            $public = $this->get_public();
            $follow_list = isset($this->args[0]) ? $this->args[0] : false;

            $followed_by_user = $this->get_followed_sites(1, $follow_list);

            $followed_by_user_ids = implode(',', $followed_by_user);

            $query = "
                SELECT posts.BLOG_ID AS BLOG_ID, ID, post_author, post_date, post_date_gmt, post_content, post_title
                FROM $this->db_network_posts AS posts
                INNER JOIN $this->db_blogs AS blogs ON blogs.blog_id = posts.BLOG_ID
                WHERE $public blogs.archived = 0 AND blogs.spam = 0 AND blogs.deleted = 0
                AND post_status = 'publish'
                AND post_password = ''
                AND posts.BLOG_ID IN( $followed_by_user_ids)
                ORDER BY post_date_gmt DESC
                $limit
            ";
            $query = apply_filters('msreader_'.$this->details['slug'].'_query', $query, $this->args, $limit, $public, $followed_by_user_ids);
            
            $posts = $wpdb->get_results($query);
        }

        return $posts;
    }

    function get_empty_message() {
        $followed_by_user = $this->get_followed_sites(1);
        $return = !$followed_by_user ? __( 'You are not following any sites yet.', 'wmd_msreader' ) : __('Nothing here yet!', 'wmd_msreader' );
        if($this->helpers->is_module_enabled('recent_posts') && !$followed_by_user)
            $return .= '<br/> <a href="'.$this->get_module_dashboard_url(array(), 'recent_posts').'">'.__( 'Look for something interesting', 'wmd_msreader' ).'</a>';

        return $return;
    }

    function get_followed_sites($check = 0, $follow_list = 0) {
        if($this->get_user()) {
            $this->user_follow_data = $this->get_user_follow_data();
            if(!$follow_list) {
            $followed_by_default = explode(',', str_replace(' ', '', $this->options['follow_by_default']));
                $followed_by_user = array_diff (array_merge($this->user_follow_data['followed'], $followed_by_default), $this->user_follow_data['unfollowed']);
            }
            else {
                $followed_by_user = array();
                foreach ($this->user_follow_data['association'] as $key => $lists_ids) {
                    if(in_array($follow_list, $lists_ids))
                        $followed_by_user[] = str_replace('blog', '', $key);
                }
            }

            //check to see if those blogs still exists
            if($check)
                foreach ($followed_by_user as $key => $blog_id) {
                    $blog_details = get_blog_details($blog_id);
                    if(!$blog_details || $blog_details->deleted)
                        unset($followed_by_user[$key]);
                }

            return array_unique($followed_by_user);
        }
        else
            return array();
    }

    function get_user_follow_data() {
        if(!isset($this->user_follow_data)) {
            $this->user_follow_data = get_user_option('msreader_follow', $this->get_user());

            $this->user_follow_data = !$this->user_follow_data ? array('followed' => array(), 'unfollowed' => array()) : $this->user_follow_data;

            //this is needed because this future has been added and users may not have this data stored
            if(!isset($this->user_follow_data['lists']))
                $this->user_follow_data['lists'] = array();
            if(!isset($this->user_follow_data['association']))
                $this->user_follow_data['association'] = array();   
        }
        
        return $this->user_follow_data;
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