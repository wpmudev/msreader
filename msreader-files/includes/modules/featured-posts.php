<?php
$module = array(
	'name' => __( 'Featured Posts', 'wmd_msreader' ),
	'description' => __( 'Enables posts featuring', 'wmd_msreader' ),
	'slug' => 'featured_posts', 
	'class' => 'WMD_MSReader_Module_FeaturedPosts'
);

class WMD_MSReader_Module_FeaturedPosts extends WMD_MSReader_Modules {

	function init() {
        add_filter( 'msreader_dashboard_reader_sidebar_widgets', array($this,'add_link_to_widget'), 10 );

        //add_action( 'admin_bar_menu', array( $this, "feature_link" ), 600 );
        add_action( 'wp_footer', array( $this, "add_css_js" ) );
        add_action( 'admin_head', array( $this, "add_css_js" ) );

        add_filter( 'msreader_read_more_button', array($this,'add_featuring_button'),20,2);
        add_filter( 'msreader_list_post_title', array($this,'add_featured_indicator'), 20, 2 );
        add_filter( 'msreader_dashboard_single_links', array($this, 'dashboard_single_add_featuring_button'), 20, 2 );

        add_filter( 'msreader_set_additional_post_data_dynamic_before', array($this,'additional_post_data'),20,2 );

        add_filter( 'the_content', array($this,'add_featuring_button_in_content'), 20, 1 );

        add_action( 'wp_enqueue_scripts', array($this, 'enqueue_scripts'), 20, 1 );
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_scripts'), 20, 1 );
        add_action( 'wp_ajax_dashboard_feature_post', array($this, 'featured_posts_control'), 20 );
        add_action( 'wp_ajax_nopriv_dashboard_feature_post', array($this, 'featured_posts_control'), 20 );
    }

    function add_link_to_widget($widgets) {
        $featured_posts = get_site_option('msreader_featured_posts', array());

        if(!empty($featured_posts) || is_super_admin())
            $widgets['reader']['data']['list'][] = $this->create_link_for_main_widget();
        
        return $widgets;
    }

    function add_featured_indicator($title, $post) {
        if(isset($post->featured) && $post->featured)
            $title = '<div class="msreader-post-indicator dashicons dashicons-star-filled featured-post" title="'.__( 'This post is featured', 'wmd_msreader' ).'"></div>'.$title;

        return $title;
    }

    function add_featuring_button($button, $post) {
        if(is_super_admin()) {
            $text = (isset($post->featured) && $post->featured) ? __( 'Unfeature', 'wmd_msreader' ): __( 'Feature', 'wmd_msreader' );

            $button .= '<button class="right button button-secondary featured-posts-control">'.$text.'</button>';
        }

        return $button;
    }

    function dashboard_single_add_featuring_button($links, $post) {
        if(is_super_admin()) {
            $text = (isset($post->featured) && $post->featured) ? __( 'Unfeature', 'wmd_msreader' ): __( 'Feature', 'wmd_msreader' );

            $links .= '<button class="featured-posts-control">'.$text.'</button>';
        }

        return $links;
    }

    function add_featuring_button_in_content($content) {
        global $post;

        if(is_super_admin() && !is_admin() && $post->post_type == 'post' && is_main_query() && (is_archive() || is_single() || is_home())) {
            $post->BLOG_ID = get_current_blog_id();
            $post = $this->additional_post_data($post);

            $text = (isset($post->featured) && $post->featured) ? __( 'Unfeature', 'wmd_msreader' ): __( 'Feature', 'wmd_msreader' );

            $content = $content.'<p><small><a style="text-transform:capitalize;" class="featured-posts-control msreader-frontend" href="#" data-blog_id="'.$post->BLOG_ID.'" data-post_id="'.$post->ID.'"  title="'.__( 'Include/Exclude from featured post list in Reader', 'wmd_msreader' ).'">'.$text.'</a></small></p>';  
        }

        return $content;
    }

    function enqueue_scripts() {
        wp_enqueue_script('jquery');

        wp_localize_script('jquery', 'ajaxurl', admin_url( 'admin-ajax.php' ));
        wp_localize_script('jquery', 'msreader', array('saving' => __( 'Saving...', 'wmd_msreader' ), 'post_featured' => __( "This post is featured", "wmd_msreader" ) ));
    }

    function add_css_js() {
        echo 
        '<style type="text/css">';
        /*
        if(!is_admin() && is_single())
            echo 
            '#wp-admin-bar-msreader-feature .ab-item .ab-icon:before {content: "\f155"; top: 2px;}
            #wp-admin-bar-msreader-feature.following .ab-item .ab-icon:before {color: #d54e21;}
            #wp-admin-bar-msreader-feature.following:hover .ab-item .ab-icon:before {color: #999;}
            #wp-admin-bar-msreader-feature.following:hover .ab-item {color: #999 !important;}';
        */
        if(is_admin())
            echo 
            '.msreader-follow-icon:before {content: "\f487"; font-family: dashicons; font-size:10px; position:relative; top: 1px;}
            .msreader-post-actions button.featured-posts { width:80px;}
            .msreader-post-actions button.featured-posts .hover-text {display:none;}';
        
        echo 
        '</style>';

        ?>

        <script type="text/javascript">
        (function($) {
            $(document).ready(function() {
                $(".msreader-posts").on("click", ".msreader-post-actions button.featured-posts-control", function(event) {
                    event.preventDefault();

                    var button = $(this);
                    var blog_id = button.parents(".msreader-post").attr("data-blog_id");
                    var post_id = button.parents(".msreader-post").attr("data-post_id");

                    featured_posts_control(blog_id, post_id, button, 0)
                });
                $(".msreader-post-overlay").on("click", ".msreader-post-header-navigation .links .featured-posts-control", function(event) {
                    event.preventDefault();

                    var button = $(this);
                    var blog_id = msreader_main_query.current_post.attr("data-blog_id");
                    var post_id = msreader_main_query.current_post.attr("data-post_id");

                    featured_posts_control(blog_id, post_id, button, 0);
                });

                $("body").on("click", ".featured-posts-control", function(event) {
                    event.preventDefault();

                    var button = $(this);
                    var blog_id = button.attr("data-blog_id");
                    var post_id = button.attr("data-post_id");  

                    featured_posts_control(blog_id, post_id, button, 1)      
                });
            });

            function featured_posts_control(blog_id, post_id, button, frontend) {
                if(blog_id && post_id) {
                    if(!frontend)
                        $(".msreader-post-header-navigation .spinner, .msreader-post[data-blog_id='"+blog_id+"'][data-post_id='"+post_id+"'] .spinner").show();
                    else
                        button.text(msreader.saving)

                    feature_details = {
                        blog_id: blog_id,
                        post_id: post_id
                    }
                    args = {
                        source: "msreader",
                        module: "featured_posts",
                        action: "dashboard_feature_post",
                        args: feature_details
                    };

                    $.post(ajaxurl, args, function(response) {
                        if(!frontend)
                            $(".msreader-post-header-navigation .spinner, .msreader-post[data-blog_id='"+blog_id+"'][data-post_id='"+post_id+"'] .spinner").fadeOut(200, function() {$(this).hide()});

                        if(response && response != 0) {
                            if(!frontend)
                                $(".msreader-post-header-navigation .featured-posts-control, .msreader-post[data-blog_id='"+blog_id+"'][data-post_id='"+post_id+"'] .msreader-post-actions .featured-posts-control").text(response);
                            else
                                button.text(response);

                            if(!frontend) {
                                if(response == 'unfeature') {
                                    var featured_post_indicator = $(".msreader-post[data-blog_id='"+blog_id+"'][data-post_id='"+post_id+"'] .msreader-post-indicator.featured-post");
                                    if(featured_post_indicator.length)
                                        featured_post_indicator.show();
                                    else
                                        $(".msreader-post[data-blog_id='"+blog_id+"'][data-post_id='"+post_id+"'] h2").prepend('<div class="msreader-post-indicator dashicons dashicons-star-filled featured-post" title="'+msreader.post_featured+'"></div>');
                                    if($('.msreader_module_featured_posts').length)
                                        msreader.add_post_to_list(blog_id, post_id);
                                }
                                else {
                                    if(response == 'feature') {
                                        $(".msreader-post[data-blog_id='"+blog_id+"'][data-post_id='"+post_id+"'] .msreader-post-indicator.featured-post").hide();
                                    }
                                    if($('.msreader_module_featured_posts').length)
                                        msreader.remove_post_from_list(blog_id, post_id);
                                }
                            }
                        }
                    });
                }
            }
        })(jQuery);
        </script>

        <?php
    }

    function featured_posts_control() {
        if(isset($this->args['blog_id']) && isset($this->args['post_id']) && is_super_admin()) {
            $post_details = get_blog_post($this->args['blog_id'], $this->args['post_id']);
            if($post_details){
                $featured_posts = get_site_option('msreader_featured_posts', array());
                
                $post_blog_key = $this->args['blog_id'].'-'.$this->args['post_id'];
                $key_exists = array_search($post_blog_key, $featured_posts);

                if($key_exists !== false) {
                    unset($featured_posts[$key_exists]);
                    update_site_option('msreader_featured_posts', $featured_posts);

                    echo 'feature';
                } 
                else {
                    $featured_posts[] = $post_blog_key;
                    update_site_option('msreader_featured_posts', $featured_posts);

                    echo 'unfeature';
                }

                $this->increase_cache_init();
            }
        }

        die();
    }

    //UNUSED
    function feature_link() {
        if(is_super_admin() && is_single()) {
            global $wp_admin_bar;

            $current_blog_id = get_current_blog_id();

            $followed_by_user = $this->get_featured_posts();
            
            if(in_array($current_blog_id, $followed_by_user)) {
                $text = __( 'Featured', 'wmd_msreader' );
                $hover_text = __( 'Unfeature', 'wmd_msreader' );
                $url = $this->get_module_dashboard_url(array('action' => 'unfollow', 'blog_id' => $current_blog_id));
                $class = 'following';
            }
            else {
                $text = __( 'Feature', 'wmd_msreader' );
                $hover_text = __( 'Follow', 'wmd_msreader' );
                $url = $this->get_module_dashboard_url(array('action' => 'follow', 'blog_id' => $current_blog_id));
                $class = 'follow';            
            }

            $wp_admin_bar->add_menu( 
                array(
                    'id'   => 'msreader-feature',
                    //'parent' => 'top-secondary',
                    'title' => '<span class="ab-icon"></span><span class="current-text">'.$text.'</span><span class="hover-text" style="display:none">'.$hover_text.'</span>',
                    'href' => $url,
                    'meta' => array(
                        'class' => $class,
                        'title' => $hover_text.' '.__( 'this site', 'wmd_msreader' )
                    ),
                ) 
            );
        }
    }

    function additional_post_data($post) {
        $featured_posts = get_site_option('msreader_featured_posts', array());
        $post_blog_key = $post->BLOG_ID.'-'.$post->ID;
        
        $post->featured = in_array($post_blog_key, $featured_posts) ? true : false;

        return $post;
    }

    function query() {
        global $wpdb;

        $limit = $this->get_limit();

        $featured_posts = get_site_option('msreader_featured_posts', array());
        $featured_posts_where = array();
        foreach ($featured_posts as $key) {
            $key = explode('-', $key);
            if(is_numeric($key[0]) && is_numeric($key[1])) 
                $featured_posts_where[] = '(posts.BLOG_ID = '.$key[0].' AND posts.ID = '.$key[1].')';
        }
        $featured_posts_where = count($featured_posts_where) ? 'AND ('.implode(' OR ', $featured_posts_where).')' : '';

        if($featured_posts_where) {
            $query = "
                SELECT posts.BLOG_ID AS BLOG_ID, ID, post_author, post_date, post_date_gmt, post_content, post_title
                FROM $this->db_network_posts AS posts
                INNER JOIN $this->db_blogs AS blogs ON blogs.blog_id = posts.BLOG_ID
                WHERE blogs.public = 1 AND blogs.archived = 0 AND blogs.spam = 0 AND blogs.deleted = 0
                AND post_status = 'publish'
                AND post_password = ''
                $featured_posts_where
                ORDER BY post_date_gmt DESC
                $limit
            ";
            $query = apply_filters('msreader_'.$this->details['slug'].'_query', $query, $this->args, $limit,  $featured_posts);

            $posts = $wpdb->get_results($query);
        }
        else
            $posts = array();

        return $posts;
    }

    function get_empty_message() {
        $return = __( 'Nothing here yet!', 'wmd_msreader' );
        if(is_super_admin())
            $return .= __( '...But it looks like you are super admin which means you can feature posts by clicking "Feature" button.', 'wmd_msreader' );
        if($this->helpers->is_module_enabled('recent_posts'))
            $return .= '<br/> <a href="'.$this->get_module_dashboard_url(array(), 'recent_posts').'">'.__( 'Look for something interesting.', 'wmd_msreader' ).'</a>';

        return $return;
    }
}