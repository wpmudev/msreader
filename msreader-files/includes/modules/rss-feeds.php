<?php
$module = array(
	'name' => __( 'RSS Feeds', 'wmd_msreader' ),
	'description' => __( 'Allows users to get links for all post lists available in Reader', 'wmd_msreader' ),
	'slug' => 'rss_feeds', 
	'class' => 'WMD_MSReader_Module_RssFeeds',
    'can_be_default' => false,
    'type' => 'other'
);

class WMD_MSReader_Module_RssFeeds extends WMD_MSReader_Modules {

	function init() {
        if(isset($_GET['msreader_'.$this->details['slug']]) && $_GET['msreader_'.$this->details['slug']] == 'view' && isset($_GET['key']) && isset($_GET['module'])) {
            add_action('msreader_before_main_query_load_module', array( $this, "set_current_user" ), 15);
            add_filter('msreader_requested', '__return_true');
            add_filter('msreader_query_limit_default', array( $this, "msreader_query_limit_default"));
            add_action('init', array( $this, "display_rss" ), 15);
        }

        add_filter('msreader_dashboard_page_title', array( $this, "add_rss_icon" ), 50, 1 );
        add_action( 'admin_head', array( $this, "add_css_js" ) );
        add_action('msreader_dashboard_after_page_title', array( $this, "add_rss_info_box" ), 5);

        add_action( 'wp_ajax_dashboard_get_rss_feed_link', array($this, 'get_rss_feed_link'), 20 );
    }

    function msreader_query_limit_default($current) {
		return 25;
    }

    function add_rss_icon($current_title) {
        global $msreader_main_query;

        $blocked_modules = apply_filters('msreader_rss_feeds_blocked_modules', array());

        if(in_array('query', $msreader_main_query->module->details['type']) && !in_array($msreader_main_query->module->details['slug'], $blocked_modules))
            $current_title = $current_title.'<a class="msreader-rss-feeds-link dashicons dashicons-rss" href="#" title="'.__('Get private RSS feed for this page', 'wmd_msreader').'"></a>';
        
        return $current_title;
    }

    function add_rss_info_box() {
        global $msreader_main_query;

        $blocked_modules = apply_filters('msreader_rss_feeds_blocked_modules', array());

        if(in_array('query', $msreader_main_query->module->details['type']) && !in_array($msreader_main_query->module->details['slug'], $blocked_modules)) {
            $feed_url = $this->get_rss_feed_link(0);
            ?>

            <div class="postbox msreader-rss-feeds-box">
                <a class="dashicons dashicons-no-alt msreader-rss-feeds-box-close" href="#"></a>
                <div class="post-spinner spinner spinner-save" style="display: none;"></div>

                <div class="inside">
                    <h2><?php _e('Private RSS feed for:', 'wmd_msreader'); echo ' '.$msreader_main_query->module->get_page_title(); ?></h2>
                    <p>
                        <input type="text" class="regular-text code" value="<?php echo $feed_url; ?>"/>
                    </p>
                    <p>
                        <?php _e('Please keep in mind that everyone with this feed URL might be able to view post content.', 'wmd_msreader'); ?>
                         <small><?php _e('You can reset ALL private feed URLs by clicking <a href="#" class="msreader-rss-feeds-reset-key">here</a>.', 'wmd_msreader'); ?></small>
                    </p>
                 </div>
            </div>

            <?php
        }
    }

    function add_css_js() {
        echo 
        '<style type="text/css">
        .msreader-rss-feeds-link {background: #FE9900; color: #fff; padding: 1px; margin: 4px 0 0 15px; box-shadow: 0 1px 1px rgba(0,0,0,.04); border: 1px solid #e5e5e5; width: auto; height: auto;}
        .msreader-rss-feeds-link:hover, .msreader-rss-feeds-link.active {background: #ffbf00; color: #fff;}
        .msreader-rss-feeds-box {background: #fffaf2; display: none;}
        .msreader-rss-feeds-box input {width:92%;}
        .msreader-rss-feeds-box-close {position: absolute; right: 10px; top: 10px; z-index: 10;}
        </style>';
        ?>

        <script type="text/javascript">
        (function($) {
            $(document).ready(function() {
                $("#msreader-dashboard").on("click", ".msreader-rss-feeds-link", function(event) {
                    event.preventDefault();

                    var button = $(this);
                    var box = $('.msreader-rss-feeds-box');

                    if(button.hasClass('active')) {
                        button.removeClass('active');
                        box.slideUp();
                    }
                    else {
                        button.addClass('active');
                        box.slideDown();

                        if($('.msreader-rss-feeds-box input').val() == '') {
                            box.find(".spinner").show();

                            rss_feed_details = {module: msreader_main_query.module, args: msreader_main_query.args, generate: 1};
                            args = {
                                source: "msreader",
                                module: "rss_feeds",
                                action: "dashboard_get_rss_feed_link",
                                args: rss_feed_details
                            };

                            $.post(ajaxurl, args, function(response) {
                                box.find(".spinner").fadeOut(200, function() {$(this).hide()});

                                if(response && response != 0) {
                                    box.find("input").val(response);
                                }
                            });
                        }
                    }
                });
                $("#msreader-dashboard").on("click", ".msreader-rss-feeds-box-close", function(event) {
                    event.preventDefault();

                    $('.msreader-rss-feeds-box').slideUp();
                    $('.msreader-rss-feeds-link').removeClass('active');
                });
                $(".msreader-rss-feeds-box input").on("focus", function(event){
                    $(this)
                        .one('mouseup', function () {
                            $(this).select();
                            return false;
                        })
                        .select();
                });
                $("#msreader-dashboard").on("click", ".msreader-rss-feeds-reset-key", function(event) {
                    event.preventDefault();
                    var box = $('.msreader-rss-feeds-box');
                    var url_input = box.find("input");

                    box.find(".spinner").show();

                    rss_feed_details = {module: box.attr('data-module'), regenerate: 1, initial_url: url_input.val()};
                    args = {
                        source: "msreader",
                        module: "rss_feeds",
                        action: "dashboard_get_rss_feed_link",
                        args: rss_feed_details
                    };

                    $.post(ajaxurl, args, function(response) {
                        box.find(".spinner").fadeOut(200, function() {$(this).hide()});

                        if(response && response != 0) {
                            url_input.val(response);
                        }
                    });
                });
            });
        })(jQuery);
        </script>

        <?php
    }

    function set_current_user() {
        global $wpdb, $msreader_main_query;    

        $user_id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM $wpdb->usermeta where meta_key = 'msreader_rss_feeds_key' AND meta_value = %s", $_GET['key']));

        if($user_id)
            $msreader_main_query->user = $user_id;
        else
            exit();
    }

    function display_rss() {
        error_reporting(0);
        global $msreader_main_query;

        $posts = $msreader_main_query->get_posts();

        header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);
        echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; ?>

        <rss version="2.0"
            xmlns:content="http://purl.org/rss/1.0/modules/content/"
            xmlns:wfw="http://wellformedweb.org/CommentAPI/"
            xmlns:dc="http://purl.org/dc/elements/1.1/"
            xmlns:atom="http://www.w3.org/2005/Atom"
            xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
            xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
            <?php
            /**
             * Fires at the end of the RSS root to add namespaces.
             *
             * @since 2.0.0
             */
            do_action( 'rss2_ns' );
            ?>
        >

        <channel>
            <title><?php echo __('Reader','wmd_msreader'); echo ': '.$msreader_main_query->module->details['name'].' - '; bloginfo_rss('name'); ?></title>
            <description><?php echo __('Reader','wmd_msreader'); echo ': '.$msreader_main_query->module->details['name'].' - '; bloginfo_rss('name'); ?></description>
            <atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
            <link><?php echo get_admin_url( get_user_meta($this->get_user(), 'primary_blog', true), 'index.php?page=msreader.php'); ?></link>
            <lastBuildDate><?php echo mysql2date('D, d M Y H:i:s GMT', isset($posts[0]->post_date) ? $posts[0]->post_date : '', false); ?></lastBuildDate>
            <sy:updatePeriod><?php echo apply_filters( 'rss_update_period', 'hourly' ); ?></sy:updatePeriod>
            <sy:updateFrequency><?php echo apply_filters( 'rss_update_frequency', 1 ); ?></sy:updateFrequency>
            <?php

            do_action( 'rss2_head');
            
            global $post;
            foreach ($posts as $post) {
                setup_postdata($post);
            ?>
            <item>
                <title><?php the_title_rss() ?></title>
                <link><?php echo $this->get_site_post_link($post->BLOG_ID, $post->ID); ?></link>
                <pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false); ?></pubDate>
                <dc:creator><![CDATA[<?php the_author() ?>]]></dc:creator>

                <guid isPermaLink="false"><?php the_guid(); ?></guid>

                <description><![CDATA[<?php echo str_replace(']]>', ']]&gt;', $post->post_excerpt); ?>]]></description>
            <?php
            /**
             * Fires at the end of each RSS2 feed item.
             *
             * @since 2.0.0
             */
            do_action( 'rss2_item' );
            ?>
            </item>
         <?php } ?>
        </channel>
        </rss>

        <?php

        exit();
    }

    function get_rss_feed_link($generate = 0, $regenerate = 0) {
        if(defined('DOING_AJAX'))
            error_reporting(0);

        if(isset($this->args['module']))
            $module = $this->args['module'];
        else {
            global $msreader_main_query;
            $module = $msreader_main_query->module->details['slug'];
        }

        $generate = (isset($this->args['generate']) && $this->args['generate']) ? 1 : $generate;
        $regenerate = (isset($this->args['regenerate']) && $this->args['regenerate']) ? 1 : $regenerate;

        $user_id = get_current_user_id();

        $user_feed_key = get_user_meta($user_id, 'msreader_rss_feeds_key', true);
        if((empty($user_feed_key) && $generate) || $regenerate) {
            $user_id = get_current_user_id();
            $user_data = get_userdata($user_id);

            $user_feed_key = md5(uniqid().$user_data->user_email);

            update_user_meta( $user_id, 'msreader_rss_feeds_key', $user_feed_key );
        }

        if($user_feed_key) {
            if($regenerate) {
                $feed_link_args = array('key' => $user_feed_key);

                $initial_url = $this->args['initial_url'];
            }
            else {
                if(isset($this->args['args']) && count($this->args['args']))
                    $module_args = $this->args['args'];
                elseif(isset($msreader_main_query->module->args) && count($msreader_main_query->module->args))
                    $module_args = $msreader_main_query->module->args;

                $feed_link_args = array(
                        'module' => $module,
                        'key' => $user_feed_key, 
                        'msreader_rss_feeds' => 'view'
                    );
                if(isset($module_args))
                    $feed_link_args['args'] = $module_args;

                $initial_url = site_url();
            }

            $feed_link = add_query_arg($feed_link_args, $initial_url);
        }
        else
            $feed_link = '';

        if(defined('DOING_AJAX')) {
            echo $feed_link;
            die();
        }
        else
            return $feed_link;
    }
}