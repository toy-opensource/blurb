<?php (__FILE__ == $_SERVER['SCRIPT_FILENAME']) ? die(header('Location: /')) : null;
/********************************************************************************************************
Plugin Name: 	Custom Blurb
Description: 	Allows for the selection of posts (any type) and ordering of these posts, which will be used to display a widget containing a list of these stories in a customisable blurbish template.
Version: 		1.0
Author: 		Loushou/toy
License: 		GPL2
Text Domain: 	custom-blurb
Notes:			inspired by the most-recent widget

REV HISTORY
[toy 8.29.11]	orig version
[toy 10.1.11]	added find defaults
*********************************************************************************************************/
// reqs and incs
require_once 'findpost.class.php';

if (!class_exists('blurb_widget')):

	class blurb_widget extends WP_Widget {
		protected static $_id = 'blurb';
		protected static $_template = 'default';
		protected static $_name = 'Custom Blurb Widget';
		protected static $_defaults = array(
			'posts_to_show' => 2,
			'thumbs_to_show' => 0,
			'thumbnail' => 606,
			'title' => '',
			'stories' => array(),
			'see_more' => ''
		);
		protected $_findDefaults = array(
			"title"=>"",
			"post_type"=>"bird",
			"category"=>"",
			"tags"=>""
			// category names must be slugs
			// multiple cats or tags "10,11,13,20" "white,blue,snowboard"
		);
		protected $template="";
		protected $core = "";
		protected $url = "";

		public static function pre_init() {
			// vars
			$id = self::$_id;					
			$base = self::$_template;					
			$webPath = plugin_dir_url( $id ).$id;
			
			if (is_admin()) {
				wp_enqueue_style("custom-blurb", "$webPath/css/frontend.css", array(), '1.0-lou');
			}
			else {				

				/* [toy 29jul12] future feature	
				// check if we even need this widget
				// (restrict load of this widget to certain pages)
				/*
				$path = $_SERVER["REQUEST_URI"];
				$pathParts = explode("/", substr($path, 1));

				if(!(isset($pathParts[0]) && ($pathParts[0] ==""))) {
					return;
				}
				*/
				
				// add the css and js for this template if they exist
				$pos = strpos(dirname(__FILE__), "wp-content");
				
				$cssWebPath = "$webPath/css/templates/";
				$cssFilePath = dirname(__FILE__)."/css/templates/";
				$cssToInclude = $cssWebPath . $base . ".css";
							
				$jsWebPath = "$webPath/js/templates/";
				$jsFilePath = dirname(__FILE__)."/js/templates/";
				$jsToInclude = $jsWebPath . $base . ".js";
				if (file_exists($cssFilePath. $base . ".css")) { wp_enqueue_style( $id, $cssToInclude); }
				if (file_exists($jsFilePath. $base . ".js")) { wp_enqueue_script( $id, $jsToInclude, array("jquery")); }
							
				// add all the wp ajax stuff
				if (isset($_REQUEST['action'])) {
					do_action('wp_ajax_'.$_REQUEST['action']);
					do_action( 'wp_ajax_nopriv_' . $_REQUEST['action'] );
				}
			}

			// [toy 29jul12] so dont forget how to do this for ajax
			//add_action('wp_ajax_loadCarousel', array(__CLASS__, 'loadCarousel'));
			//add_action('wp_ajax_nopriv_loadCarousel', array(__CLASS__, 'loadCarousel'));

			add_action('widgets_init', array(__CLASS__, 'a_widget_init'));
		}

		public static function a_widget_init() {			
			//if (!self::$widget_enabled) return false;
			return register_widget(__CLASS__);
		}

		public function blurb_widget() {
			// class vars
			$this->template = "widget/templates/". self::$_template .".php";
			$this->core = dirname(__FILE__).'/';
			$this->url = home_url().str_replace(ABSPATH, '/', $widgetCore);

			parent::WP_Widget(false, self::$_name);

		}
				
		protected function _ni(&$instance) {
			$instance = wp_parse_args((array)$instance, $this->_defaults);
			$instance['stories'] = empty($instance['stories']) ? array() : (array)$instance['stories'];
			$instance['posts_to_show'] = intval($instance['posts_to_show']);
			$instance['thumbs_to_show'] = intval($instance['thumbs_to_show']);
			$instance = $this->_st($instance);
			return $instance;
		}

		public function update( $new_instance, $old_instance ) {
			$instance = $this->_ni($old_instance);
			$instance = wp_parse_args((array)$new_instance, (array)$instance);
			$instance = $this->_st($instance);
			return $instance;
		}

		protected function _get_posts(&$instance) {
			$this->_ni($instance);			
			$tcounter = 0;
			$ordered = array();
			
			// [toy 13oct11] if there are no posts
			// get most recent
			// in the order that the list is defined, add the posts to the output array
			if (count($instance["stories"])==0) {
				$numposts = (isset($instance['posts_to_show']) && ($instance['posts_to_show']>0) ? $instance['posts_to_show'] : $this->_defaults["posts_to_show"]);
				$posts = get_posts(array("numberposts"=>$numposts));

				foreach ($posts as $post) {
					if ($tcounter++ < $instance["thumbs_to_show"]){
						$thumb_id = get_post_thumbnail_id($post->ID);
						if (empty($thumb_id)) {
							$imgs = get_posts('post_type=attachment&post_status=any&post_parent='.$post->ID);
							if (is_array($imgs) && !empty($imgs)) {
								$img = array_shift($imgs);
								$thumb_id = $img->ID;
							}
							else {
								$post->thumb_URL=$instance["default_image_url"];
							}
						}
						if (!empty($thumb_id)) $post->thumb = get_post($thumb_id);
					}
					$ordered[] = $post;
				}
			}
			else {
				foreach ($instance['stories'] as $post_id) {
					$post = get_post($post_id);
				
					if ($tcounter++ < $instance["thumbs_to_show"]){
						$thumb_id = get_post_thumbnail_id($post->ID);
						if (empty($thumb_id)) {
							$imgs = get_posts('post_type=attachment&post_status=any&post_parent='.$post->ID);
							if (is_array($imgs) && !empty($imgs)) {
								$img = array_shift($imgs);
								$thumb_id = $img->ID;
							}
							else {
								$post->thumb_URL=$instance["default_image_url"];
							}
						}
						if (!empty($thumb_id)) $post->thumb = get_post($thumb_id);
					}
					$ordered[] = $post;
				}

				// shorten the result array to the 'max length' the user specified
				$ordered = array_slice($ordered, 0, $instance['posts_to_show']);
			}

			return $ordered;
		}

		public function widget($args, $instance) {
			$this->_ni($instance);

			$posts = $this->_get_posts($instance);
			
			if (empty($posts)) return;
			
			$this->_display_widget($posts, $instance, $args);
		}

		protected function _display_widget(&$posts, $instance, $args) {
			extract($args);
			extract($instance);
			include $this->template;
		}

		protected function _find_file($file) {
			$loc = locate_template(array($file), false);
			if (empty($loc))
				if (file_exists($this->core.$file)) $loc = $this-core.$file;
			return $loc;
		}

		public function form($inst) {
			global $user_ID;
			
			$instance = $this->_ni($inst);
			$userSettings = get_user_meta($user_ID,"wp_user-settings");
			
			// do a broad type collection
			$types = get_post_types();
			$post_types = array();
			foreach ($types as $type) {
				$t = strtolower($type);
				
				// take out the ones that are not relevant
				switch($t) {
					case "revision": 
					case "nav_menu_item": 
					case "evolve": 
						break;
					default: 
						$post_types[] = $type;
						break;
				}
			}			
			
			?>
				<div class="qs-custom-story-settings qs-<?php echo $this->_template; ?>-template" id="<?php echo $this->get_field_id('qs-custom-story-wrapper') ?>">
					<p>
						<label for="<?php echo $this->get_field_name('title'); ?>"><?php _e('Display title:', 'qs-cs'); ?></label>
						<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text"
							value="<?php echo attribute_escape($instance['title']); ?>"/>
					</p>
					<p>
						<label for="<?php echo $this->get_field_name('see_more'); ?>"><?php _e('See more stories url:', 'qs-cs'); ?></label>
						<input class="widefat" id="<?php echo $this->get_field_id(''); ?>" name="<?php echo $this->get_field_name('see_more'); ?>" type="text"
							value="<?php echo attribute_escape($instance['see_more']); ?>"/>
					</p>
					<p>
						<label for="<?php echo $this->get_field_name('default_image_url'); ?>"><?php _e('Default image url:', 'qs-cs'); ?></label>
						<input class="widefat" id="<?php echo $this->get_field_id(''); ?>" name="<?php echo $this->get_field_name('default_image_url'); ?>" type="text"
							value="<?php echo attribute_escape($instance['default_image_url']); ?>"/>
					</p>
					<p>
						<label for="<?php echo $this->get_field_name('posts_to_show'); ?>"><?php _e('Number of posts to show:', 'qs-cs'); ?></label>
						<input class="widefat" id="<?php echo $this->get_field_id('posts_to_show'); ?>"
							name="<?php echo $this->get_field_name('posts_to_show'); ?>" type="text"
							value="<?php echo attribute_escape($instance['posts_to_show']); ?>" style="width: 30%" />
					</p>
					<p>
						<label for="<?php echo $this->get_field_name('thumbs_to_show'); ?>"><?php _e('Number of thumbs to show:', 'qs-cs'); ?></label>
						<input class="widefat" id="<?php echo $this->get_field_id('thumbs_to_show'); ?>"
							name="<?php echo $this->get_field_name('thumbs_to_show'); ?>" type="text"
							value="<?php echo attribute_escape($instance['thumbs_to_show']); ?>" style="width: 30%" />
					</p>

					<div class="post-bucket-controls">
						<?php
						$args = array(
							'classes' => 'button add-post',
							'text' => 'Add',
							'post-types' => $post_types,
							'extra-attr' => array('list' => '#'.$this->get_field_id('post-bucket'))
						);

						// [toy 13oct11] add lockdown fields to args
						// taking this out becuase this was implemented to restrict for specific usage
						// but leaving it in case someone else wants to use it
						/*
						$defFields = array();
						foreach ($this->_findDefaults as $k=>$v) {
							if ((isset($v)) && ($v!="")) {
								array_push($defFields, $k);
								$args["def_".$k] = $v;
							}
							else{
								// mandatory defaults, ie post_type
								switch($k) {
									case "post_type":
										array_push($defFields, $k);
										$args["def_".$k] = "post";
										break;
								}
							}
						}
						$args["lock"] = $defFields;
						*/
											
						do_action('qs-find-post-button', $args);
						?>
					</div>

					<div class="post-bucket" id="<?php echo $this->get_field_id('post-bucket') ?>"
							new-ids="<?php echo $this->get_field_id('stories-') ?>" new-names="<?php echo $this->get_field_name('stories') ?>[]">
						<?php foreach ($instance['stories'] as $story_id): ?>
							<?php $story = get_post($story_id); ?>
							<?php /* we need some auto cleanup in case a post is selected at one point and then it is removed, after being selected */ ?>
							<?php if (!is_object($story) || empty($story)) continue; ?>
							<div class="post-in-bucket" post-id="<?php echo $story_id ?>">
								<span class="remove-post-from-bucket">X</span>
								<input type="hidden" id="<?php echo $this->get_field_id('stories-'.$story_id) ?>" name="<?php echo $this->get_field_name('stories') ?>[]" value="<?php echo $story_id ?>"/>
								<span class="bucket-post-title"><?php echo get_the_title($story_id) ?></span>
							</div>
						<?php endforeach; ?>
					</div>

					<div class="<?php echo $this->get_field_id('post-list') ?>"></div>

					<?php // give the post list drag n drop sortability ?>
					<script language="javascript" type="text/javascript">
						(function($){$(function() { $('.post-bucket').sortable(); });})(jQuery);
					</script>
				</div>
			<?php
		}

		protected function _st($data) {
			if (is_object($data) || is_array($data)) {
				foreach ($data as &$element) $element = $this->_st($element);
			} else {
				$data = strip_tags($data);
			}
			return $data;
		}
		
		protected function removeImagesfromContent($content) {
			$pattern = '/(<img.+?>)/';
			$replacements='';
			return(preg_replace($pattern, $replacements, $content));
		}
		protected function removeBlankLinksfromContent($content) {
			$pattern = '/(<a .+?><\/a>)/';
			$replacements='';
			return(preg_replace($pattern, $replacements, $content));
		}
	}

	if (defined('ABSPATH') && function_exists('add_action')) {
		blurb_widget::pre_init();
	}

endif;
