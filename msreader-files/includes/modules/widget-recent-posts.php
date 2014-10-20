<?php
$module = array(
	'name' => __( 'Widget - Post List', 'wmd_msreader' ),
	'description' => __( 'Allows usage of WordPress sidebar widget that displays latest posts', 'wmd_msreader' ),
	'slug' => 'widget_recent_posts', 
	'class' => 'WMD_MSReader_Module_WidgetRecentPosts',
    'can_be_default' => false
);

class WMD_MSReader_Module_WidgetRecentPosts extends WMD_MSReader_Modules {

	function init() {
        add_action( 'widgets_init', create_function( '', 'return register_widget("wmd_msreader_post_list");' ) );
        add_action('admin_footer-widgets.php', array($this,'add_js'));
    }

    function add_js() {
        ?>
        <script type="text/javascript">
        (function($) {
            $(document).ready(function() {
                $('.widget-content').on('change', '.msreader_widget_recent_posts_select', function(event){
                    var parent = $(this).parents('.msreader_widget_recent_posts');

                    if(parent.find('.msreader_widget_recent_posts_select').val() == 'myclass')
                        parent.find('.msreader_widget_recent_posts_privacy_warning').show();
                    else
                        parent.find('.msreader_widget_recent_posts_privacy_warning').hide();
                })
            });
        })(jQuery);
        </script>
        <?php
    }

    function widget( $args, $instance ) {
        global $wmd_msreader;
        include_once($wmd_msreader->plugin['dir'].'includes/query.php');

        extract( $args );

        $title = isset($instance['title']) ? apply_filters( 'widget_title', $instance['title'] ) : '';
        $number = (is_numeric($instance['number']) ) ? $instance['number'] : 7;
        $show_date = $instance['show_date'] == 'on' ? true : false;
        $show_excerpt = (isset($instance['show_excerpt']) && $instance['show_excerpt'] == 'on') ? true : false;
        $show_author = (isset($instance['show_author']) && $instance['show_author'] == 'on') ? true : false;

        $query = new WMD_MSReader_Query();

        if(isset($wmd_msreader->modules[$instance['module']]) && isset($instance['user_id']) && $instance['user_id']) {
            $query->limit = $number;
            $query->user = $instance['user_id'];
            $query->load_module($wmd_msreader->modules[$instance['module']]);

            $posts = $query->get_posts();

            if(is_array($posts) && count($posts) > 0) {
                if(isset($before_widget))
                    echo $before_widget;
                 
                if(isset($before_widget))
                    echo $before_title;
                if($title)
                    echo $title;
                if(isset($before_widget))
                    echo $after_title;

                    if(!isset($instance['remove_widget_class']) || !$instance['remove_widget_class'])
                        echo '<div class="widget_recent_entries">';
                            echo '<ul>';

                            foreach ($posts as $post) {
                                $time = strtotime($post->post_date_gmt) ? $post->post_date_gmt : $post->post_date;
                                $time = mysql2date(get_option('date_format'), $time, true);

                                echo '<li>';
                                    echo '<a target="_blank" href="'.$wmd_msreader->modules['widget_recent_posts']->get_site_post_link($post->BLOG_ID, $post->ID).'">'.$post->post_title.'</a>';
                                    if($show_date)
                                        echo ' <span class="post-date">'.$time.'</span>';
                                    if($show_excerpt)
                                        echo ' <div class="post-excerpt rssSummary">'.$post->post_excerpt.'</div>';
                                    if($show_author)
                                        echo ' <cite class="post-author">'.$post->post_author_display_name.'</cite>';
                                echo '</li>';
                            }

                            echo '</ul>';
                    if(!isset($instance['remove_widget_class']) || !$instance['remove_widget_class'])
                        echo '</div>';
                if(isset($after_widget))
                    echo $after_widget;
            }
        } 
    }
}

// Widget for Subscribe
class wmd_msreader_post_list extends WP_Widget {
    //constructor
    function wmd_msreader_post_list() {
        $widget_ops = array( 'description' => __( 'List of most recent Reader related Posts', 'wmd_msreader') );
        parent::WP_Widget( false, __( 'Reader: Recent Posts', 'wmd_msreader' ), $widget_ops );
    }

    /** @see WP_Widget::widget */
    function widget( $args, $instance ) {
        global $msreader_modules;

        $msreader_modules['widget_recent_posts']->widget( $args, $instance );
    }

    /** @see WP_Widget::update */
    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title']  = strip_tags($new_instance['title']);
        $instance['number']  = strip_tags($new_instance['number']);
        $instance['number'] = $instance['number'] > 20 ? 20 : ($instance['number'] < 1 ? 1 : 7);
        $instance['show_date']  = strip_tags($new_instance['show_date']);
        $instance['show_excerpt']  = strip_tags($new_instance['show_excerpt']);
        $instance['show_author']  = strip_tags($new_instance['show_author']);
        $instance['module']  = strip_tags($new_instance['module']);
        if(!$instance['user_id'])
            $instance['user_id'] = get_current_user_id();

        return $instance;
    }

    /** @see WP_Widget::form */
    function form( $instance ) {
        global $msreader_helpers, $wmd_msreader;
        $options = $wmd_msreader->plugin['site_options'];

        $title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
        $number = (isset( $instance['number'] ) && is_numeric($instance['number'])) ? esc_attr( $instance['number'] ) : 7;
        $show_date = isset( $instance['show_date'] ) ? esc_attr( $instance['show_date'] ) : '';
        $show_excerpt = isset( $instance['show_excerpt'] ) ? esc_attr( $instance['show_excerpt'] ) : '';
        $show_author = isset( $instance['show_author'] ) ? esc_attr( $instance['show_author'] ) : '';
        $current_module = isset( $instance['module'] ) ? esc_attr( $instance['module'] ) : '';

        $user_id = (isset($instance['user_id']) && $instance['user_id']) ? $instance['user_id'] : get_current_user_id();
        $user_name = get_userdata($user_id);
        $user_name = $user_name->user_login; 
        ?>
        <div class="msreader_widget_recent_posts">
            <p>
                <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'wmd_msreader' ) ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of posts to show:', 'wmd_msreader' ) ?></label>
                <input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" min="1" max="20" value="<?php echo $number; ?>" size="3">
            </p>
            <p>
                <input class="checkbox" type="checkbox" id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" <?php checked( 'on', $show_date);?>>
                <label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Display post date?', 'wmd_msreader' ) ?></label>
            </p>
            <p>
                <input class="checkbox" type="checkbox" id="<?php echo $this->get_field_id( 'show_excerpt' ); ?>" name="<?php echo $this->get_field_name( 'show_excerpt' ); ?>" <?php checked( 'on', $show_excerpt);?>>
                <label for="<?php echo $this->get_field_id( 'show_excerpt' ); ?>"><?php _e( 'Display post excerpt?', 'wmd_msreader' ) ?></label>
            </p>
            <p>
                <input class="checkbox" type="checkbox" id="<?php echo $this->get_field_id( 'show_author' ); ?>" name="<?php echo $this->get_field_name( 'show_author' ); ?>" <?php checked( 'on', $show_author);?>>
                <label for="<?php echo $this->get_field_id( 'show_author' ); ?>"><?php _e( 'Display post author?', 'wmd_msreader' ) ?></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'module' ); ?>"><?php _e( "Reader's post source:", 'wmd_msreader' ) ?></label>
                <?php
                $blocked_modules = apply_filters('msreader_widget_recent_posts_blocked_modules', array());

                if($options['modules'] && is_array($options['modules'])) {
                    echo '<select id="'.$this->get_field_id( 'module' ).'" class="msreader_widget_recent_posts_select" name="'.$this->get_field_name( 'module' ).'">';
                    foreach ($wmd_msreader->available_modules as $slug => $module) {
                        if( (isset($wmd_msreader->available_modules[$module['slug']]['can_be_default']) && $wmd_msreader->available_modules[$module['slug']]['can_be_default'] == false) || in_array($module['slug'], $blocked_modules))
                            continue;

                        $module_title = isset($module['menu_title']) ? $module['menu_title'] : $module['name'];

                        $display = !$wmd_msreader->helpers->is_module_enabled($module['slug']) ? ' style="display: none;"' : '';
                        echo '<option class="msreader_widget_recent_posts_select_option_'.$module['slug'].'" value="'.$module['slug'].'" '.$display.selected( $current_module, $module['slug'], false ).'>'.$module_title.'</option>';
                    }
                    echo '</select>';
                }
                ?>
                <br/>
                <small><?php printf(__( "From %s's Reader", "wmd_msreader" ), $user_name); ?>.</small>
            </p>
            <p class="msreader_widget_recent_posts_privacy_warning" style="color:red;<?php echo $current_module != 'myclass' ? 'display:none' : '';?>">
                <?php _e( 'Please keep in mind privacy of users.', 'wmd_msreader' ) ?>
            </p>
        </div>
        <?php
    }
}