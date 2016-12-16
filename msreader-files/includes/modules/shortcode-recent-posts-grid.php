<?php
$module = array(
	'name' => __( 'Shortcode - Post Grid', 'wmd_msreader' ),
	'description' => __( 'Allows usage of [reader-posts-grid] shorcode to display posts grid from various reader modules.', 'wmd_msreader' ),
	'slug' => 'shortcode_recent_posts_grid', 
	'class' => 'WMD_MSReader_Module_ShortcodePostsGrid',
	'can_be_default' => false,
	'type' => 'wp-shortcode'
);

class WMD_MSReader_Module_ShortcodePostsGrid extends WMD_MSReader_Modules {

	function init() {
		add_shortcode('reader-posts-grid', array($this, 'posts_grid'));

		add_action('wp_enqueue_scripts', array($this,'shortcode_register_scripts_styles'));
	}

    function shortcode_register_scripts_styles() {
		wp_register_script('jquery-slick', plugins_url( 'shortcode-recent-posts-grid/js/slick.min.js', __FILE__ ), array('jquery'), 1 );
		wp_register_style('jquery-slick', plugins_url( 'shortcode-recent-posts-grid/css/slick.css', __FILE__ ), array(), 1 );
		wp_register_style('jquery-slick-reader-theme', plugins_url( 'shortcode-recent-posts-grid/css/slick-theme.css', __FILE__ ), array('jquery-slick'), 1 );
    }

	function posts_grid($attrs, $content) {

		global $wmd_msreader;
		include_once($wmd_msreader->plugin['dir'].'includes/query.php');

		$args = array();
		//sometimes we can have arg in module name

        $attr_module = isset($attrs['module']) ? explode('|', $attrs['module']) : array();

        if(isset($attr_module[1])) {
            $attrs['module'] = $attr_module[0];
            $args = array($attr_module[1]);
        }            

		//lets dig out args data as it works bit differently than other attributes
		if(isset($attrs['args']) && $attrs['args']) {
			//str_replace fixes wp editor auto & conversion to &amp;
			parse_str(str_replace('&amp;', '&', $attrs['args']), $parsed_args);
			if(isset($parsed_args['args']))
				$args = $parsed_args['args'];
			else
				$args = array($attrs['args']);
		}

		if(isset($attrs) && is_array($attrs))
			foreach ($attrs as $attr_key => $attr_value){
				if(substr($attr_key, 0,4) == 'arg-')
					$args[substr($attr_key, 4)] = $attr_value;
			}

		$default_attrs = array(
			'number' => 20,
			'module' => 'recent_posts',
			'hide_image' => false,
			'hide_excerpt' => false,
			'hide_author' => false,
			'hide_date' => false,
			'posts_per_row' => 2,
			'image_height' => '150px',
			'background_color' => '#f9f9f9',
			'text_color' => '#003b4e',
			'hover_background_color' => '#003b4e',
			'hover_link_color' => '#fbaf40',
			'hover_link_content' => '»',
			'target' => '_self',
			'theme' => 'grid',
			'slider_hide_dots' => false,
			'slider_hide_arrows' => false,
			'slider_disable_autoplay' => false,
			'slider_disable_infinite' => false,
			'slider_ui_color' => '#003b4e',
			'slider_animation' => false,
			'slider_height' => false
		);
		$attrs = shortcode_atts($default_attrs, $attrs, 'reader-posts-grid');

		if($attrs['theme'] == 'slider')
			if($attrs['slider_animation'] || $attrs['slider_height'])
				$attrs['posts_per_row'] = 1;

		extract( $attrs );

		$number = (is_numeric($attrs['number']) && $attrs['number'] < 50) ? $attrs['number'] : 50;
		$posts_per_row = (is_numeric($attrs['posts_per_row']) && $attrs['posts_per_row'] > 0) ? $attrs['posts_per_row'] : 3;        

		$user_id = get_the_author_meta('ID');
		if(!$user_id && is_user_logged_in())
			$user_id = get_current_user_id();

		$return = '';

		$query = new WMD_MSReader_Query();

		if(isset($wmd_msreader->modules[$attrs['module']]) && $user_id) {
			$query->limit = $number;
			$query->user = $user_id;
			$query->args = $args;
			$query->load_module($wmd_msreader->modules[$attrs['module']]);

			$posts = $query->get_posts();

			if(is_array($posts) && count($posts) > 0) {
				$sc_id = uniqid();
				
				ob_start();
				$this->shortcode_css_js($posts_per_row, $attrs, $sc_id);
				?>
				<div id="reader-posts-grid-<?php echo $sc_id; ?>" class="reader-posts-grid <?php echo 'reader-posts-grid-theme-'.$attrs['theme']; ?>">
					<ul class="reader-posts-grid-container">
						<?php
						foreach ($posts as $post) {
							if(!$attrs['hide_image']) { 
								$image = preg_replace('~(?:\[/?)[^/\]]+/?\]~s', '', strip_tags($post->featured_media_html, '<img>'));
								$image_url = $this->get_img_src($image);
							}
							else
								$image_url = false;

							if(!$attrs['hide_author'])
								$avatar_url = $this->get_img_src(get_avatar($post->post_author, 48)); 
							else
								$avatar_url = false;

							$post_excerpt = strip_tags($post->post_excerpt);
						?>

							<li class="reader-pg-post<?php if($image_url) echo ' reader-pg-post-w-image';?>">
								<a target="<?php echo $attrs['target']; ?>" href="<?php echo $wmd_msreader->modules[$this->details['slug']]->get_site_post_link($post->BLOG_ID, $post->ID); ?>">
									<?php
									if($image_url)
										echo '<div class="reader-pg-post-media" style="background-image: url('.$image_url.');"></div>';
									?>
									<div class="reader-pg-post-content">
										<div class="reader-pg-post-title"><?php echo $post->post_title; ?></div>
										<?php if(!$attrs['hide_excerpt'] && $post_excerpt) { ?>
											<div class="reader-pg-post-text"><?php echo $post_excerpt; ?></div>
										<?php } ?>
										<?php if($avatar_url || !$attrs['hide_date'] || !$attrs['hide_author']) { ?>
											<div class="reader-pg-post-meta">
												<?php 
												if($avatar_url)
													echo '<div class="reader-pg-post-bg-avatar" style="background-image: url('.$avatar_url.');"></div>';
												?>
												<?php if(!$attrs['hide_date']) { ?>
													<span class="reader-pg-post-time">
														<?php echo $post->post_date_relative; ?>
													</span>
													<?php _e( 'ago', 'wmd_msreader' ); ?>
												<?php } ?>    
												<?php if(!$attrs['hide_author']) { ?>
													<?php _e( 'by', 'wmd_msreader' ); ?><br/>
													<span class="reader-pg-post-author"><?php echo $post->post_author_display_name; ?></span>
												<?php } ?>
											</div>
										<?php } ?>
									</div>
								</a>
							</li>
						<?php
						}
						?>
					</ul>
				</div>
				<?php
				$return = ob_get_clean();
			}
		}
		return $return;
	}

	function shortcode_css_js($posts_per_row = 3, $attrs = false, $sc_id = false) {
		global $reader_sc_pg_css_set;

		if(!isset($reader_sc_pg_css_sest)) {
			$reader_sc_pg_css_set = true;

			wp_enqueue_script('jquery');
			?>
			<style type="text/css">
				ul.reader-posts-grid-container {
					list-style: none !important;
					padding: 0px !important;
					margin: 0px !important;
				}
				.reader-pg-post {
					text-align: left;
					padding: 10px;
					box-sizing: border-box;
				}
				.reader-pg-post .reader-pg-post-content {
					padding: 15px;
				}
				.reader-pg-post a {
					display: block;
					box-shadow: none;
					background-color: #f9f9f9;
					position: relative;
					color: #003b4e;
					height: 100%;
					text-decoration: none;
				}
				.reader-pg-post a:focus:after,
				.reader-pg-post a:hover:after {
					opacity: .75;
					filter: alpha(opacity=75)
				}
				.reader-pg-post a:focus:before,
				.reader-pg-post a:hover:before {
					opacity: 1;
					filter: alpha(opacity=100)
				}
				.reader-pg-post a:after {
					-webkit-transition: .55s cubic-bezier(0.165, .775, .145, 1.02);
					transition: .55s cubic-bezier(0.165, .775, .145, 1.02);
					position: absolute;
					top: 0;
					left: 0;
					width: 100%;
					height: 100%;
					content: '';
					opacity: 0;
					filter: alpha(opacity=0)
				}
				.reader-pg-post a:before {
					line-height: 48px;
					font-size: 48px;
					-webkit-transform: translateY(-24px);
					transform: translateY(-24px);
					-webkit-transition: .55s cubic-bezier(0.165, .775, .145, 1.02);
					transition: .55s cubic-bezier(0.165, .775, .145, 1.02);
					opacity: 0;
					filter: alpha(opacity=0);
					position: absolute;
					z-index: 2;
					top: 50%;
					left: 0;
					width: 100%;
					text-align: center
				}
				.reader-pg-post .reader-pg-post-media {
					background-size: cover;
					background-position: center center;
					overflow: hidden;
					background-color: #eee;
					padding-bottom: 75%;
					box-sizing: border-box;
				}
				.reader-pg-post .reader-pg-post-title {
					text-transform: uppercase;
					font-size: 15px;
				}
				.reader-pg-post .reader-pg-post-text {
					margin-top: 10px;
					font-size: 12px;
					line-height: 18px;
					max-height: 18em;
					position: relative;
					overflow: hidden;
				}
				.reader-pg-post-w-image .reader-pg-post-text {
					max-height: 6em;
				}
				.reader-pg-post .reader-pg-post-text:after {
					content: "";
					text-align: right;
					position: absolute;
					bottom: 0;
					right: 0;
					width: 70%;
					height: 1.5em;
					background: linear-gradient(to right, rgba(255, 255, 255, 0), #f9f9f9 50%);
				}
				.reader-pg-post .reader-pg-post-meta {
					font-size: 14px;
					line-height: 23px;
					margin-top: 10px;
				}
				.reader-pg-post .reader-pg-post-author {
					font-size: 16px;
					text-transform: uppercase
				}
				.reader-pg-post .reader-pg-post-bg-avatar {
					float: left;
					margin: 0 10px 0 0;
					border-radius: 24px;
					background-size: cover;
					background-position: center center;
					width: 48px;
					height: 48px
				}
			</style>
			<script type="text/javascript">
				var reader_post_grid = false;
				document.addEventListener("DOMContentLoaded", function(event) {
					var ReaderPostsGrid = function($) {
						this.max_height = function(selector) {
							var maxHeight = 0;

							$(selector).each(function(){
								var thisH = $(this).height();
								if (thisH > maxHeight) { maxHeight = thisH; }
							});

							$(selector).height(maxHeight);
						}
						this.equal_height = function(container) {
							var currentTallest = 0,
							currentRowStart = 0,
							rowDivs = new Array(),
							$el,
							topPosition = 0;
							$(container).each(function() {
								$el = $(this);
								$($el).css('height', 'auto');
								topPostion = $el.position().top;

								if (currentRowStart != topPostion) {
									for (currentDiv = 0 ; currentDiv < rowDivs.length ; currentDiv++) {
										rowDivs[currentDiv].css('height', currentTallest);
									}
									rowDivs.length = 0; // empty the array
									currentRowStart = topPostion;
									currentTallest = $el.outerHeight();
									rowDivs.push($el);
								} else {
									rowDivs.push($el);
									currentTallest = (currentTallest < $el.outerHeight()) ? ($el.outerHeight()) : (currentTallest);
								}
								for (currentDiv = 0 ; currentDiv < rowDivs.length ; currentDiv++) {
									rowDivs[currentDiv].css('height', currentTallest);
								}
							});
						}

						return this;
					}

					reader_post_grid = ReaderPostsGrid(jQuery);
				});
			</script>
			<?php
			if($attrs['theme'] == 'slider') {
				wp_enqueue_script('jquery-slick', array('jquery'));
				wp_enqueue_style('jquery-slick');
				wp_enqueue_style('jquery-slick-reader-theme');
				?>
				<style type="text/css">
				.reader-posts-grid-theme-slider .reader-posts-grid-container {
					display: none;
				}
				.reader-posts-grid-container.slick-initialized {
					display: block;
				}
				.reader-posts-grid .slick-dots {
					position: static;
					display: block;
				}
				</style>
				<?php
			}
			else {
			?>
				<style type="text/css">
					.reader-posts-grid {
						overflow: hidden;
					}
					.reader-posts-grid-container {
						box-sizing: border-box;
						margin-left: -10px;
						margin-right: -10px;
					}
					@media screen and (min-width: 782px) {
						.reader-pg-post {
							float: left;
						}
					}
				</style>
				<script type="text/javascript">
				document.addEventListener("DOMContentLoaded", function(event) {
					(function($) {
						reader_post_grid.equal_height('.reader-pg-post');
						$(window).resize(function() {
							reader_post_grid.equal_height('.reader-pg-post');
						});
					})(jQuery);
				});
				</script>
			<?php
			}
		}

		if($sc_id && $attrs) {
		?>
			<style type="text/css">
				<?php if($attrs['theme'] == 'slider') { ?>
					#reader-posts-grid-<?php echo $sc_id; ?> .slick-prev:before, 
					#reader-posts-grid-<?php echo $sc_id; ?> .slick-next:before,
					#reader-posts-grid-<?php echo $sc_id; ?> .slick-dots li.slick-active button:before,
					#reader-posts-grid-<?php echo $sc_id; ?> .slick-dots li button:before {
						color: <?php echo $attrs['slider_ui_color'];?> !important;
					}				
				<?php } ?>
				#reader-posts-grid-<?php echo $sc_id; ?> .reader-pg-post a {
					background-color: <?php echo $attrs['background_color'];?>;
					color: <?php echo $attrs['text_color'];?>;
				}
				#reader-posts-grid-<?php echo $sc_id; ?> .reader-pg-post a:after {
					background-color: <?php echo $attrs['hover_background_color'];?>;
				}
				#reader-posts-grid-<?php echo $sc_id; ?> .reader-pg-post a:before {
					color: <?php echo $attrs['hover_link_color'];?>;
					content: "<?php echo $attrs['hover_link_content'];?>";
				}
				#reader-posts-grid-<?php echo $sc_id; ?> .reader-pg-post .reader-pg-post-media {
					max-height: <?php echo $attrs['image_height'];?>;
				}
				.reader-pg-post .reader-pg-post-text:after {
					background: linear-gradient(to right, rgba(255, 255, 255, 0), <?php echo $attrs['background_color'];?> 50%);
				}
				<?php if($attrs['theme'] == 'slider') {} else {?>
				@media screen and (min-width: 782px) {
					#reader-posts-grid-<?php echo $sc_id; ?> .reader-pg-post {
						width: <?php echo 100/$posts_per_row; ?>%;
					}
					#reader-posts-grid-<?php echo $sc_id; ?> .reader-pg-post:nth-child(<?php echo $posts_per_row; ?>+1) {
						clear: left;
					}
				}
				<?php } ?>
			</style>
			<?php if($attrs['theme'] == 'slider') { ?>
				<script type="text/javascript">
					document.addEventListener("DOMContentLoaded", function(event) {
						(function($) {
							$('#reader-posts-grid-<?php echo $sc_id; ?> .reader-posts-grid-container').slick({
								dots: <?php echo !$attrs['slider_hide_dots'] ? 'true' : 'false' ?>,
								arrows: <?php echo !$attrs['slider_hide_arrows'] ? 'true' : 'false' ?>,
								autoplay: <?php echo !$attrs['slider_disable_autoplay'] ? 'true' : 'false' ?>,
								infinite: <?php echo !$attrs['slider_disable_infinite'] ? 'true' : 'false' ?>,
								fade: <?php echo $attrs['slider_animation'] ? 'true' : 'false'; ?>,
								pauseOnDotsHover: true,
								adaptiveHeight: <?php echo $attrs['slider_height'] ? 'true' : 'false'; ?>,
								slidesToShow: <?php echo $posts_per_row; ?>,
								slidesToScroll: <?php echo $posts_per_row; ?>,
								responsive: [
									{
										breakpoint: 782,
										settings: {
											slidesToShow: 1,
											slidesToScroll: 1
										}
									}
								]
							}); 
						})(jQuery);
					});
				</script>
			<?php } ?>
		<?php
		}
	}

	function get_img_src($html) {
		$image_url = false;
		if($html) {
			$image_url_pieces = explode("src=\"", $html);
			if(!isset($image_url_pieces[1])) {
				$image_url_pieces = explode("src='", $html);
				$quote_type = "'";
			}
			else
				$quote_type = "\"";

			if(isset($image_url_pieces[1])) {
				$image_url_pieces = explode($quote_type, $image_url_pieces[1]);
				if(isset($image_url_pieces[1]))
					$image_url = $image_url_pieces[0];
			}
		}

		if($image_url)
			return $image_url;
		else
			return false;
	}
}