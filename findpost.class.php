<?php 
/**************************************************************************************************** 
short-name: fpb

REV HISTORY
[toy 08.29.11] 	changed radio buttons to cbs
[toy 09.15.11] 	adjusted query for more flexibility
*****************************************************************************************************/
// if accessed directly, redirect to home
(__FILE__ == $_SERVER['SCRIPT_FILENAME']) ? die(header('Location: /')) : null;

if (!class_exists('qs_core_base_findpost')):

	class qs_core_base_findpost {
		protected static $_id = 'blurb';
		protected static $_js_root; // root js for this sub-plugin
		protected static $_css_root;
		protected static $version = '1.0-lou'; // version for js and css for this sub-plugin
		protected static $def_post_types = array('post','gallery','video-post'); // list of default post types

		// setup the actions and filters that are needed to get this ub-plugin jump started
		public static function pre_init() {
			$id = self::$_id;					
			
			if (!defined('QSWID_CORE')) define('QSWID_CORE', dirname(__FILE__).'/');
			if (!defined('QSWID_URL')) define('QSWID_URL', plugin_dir_url( $id ).$id."/");


			// determine the root js dir for this sub plugin
			self::$_js_root = QSWID_URL.'js/base/findpost/';
			self::$_css_root = QSWID_URL.'css/';
			// allow other plugins to add their post type the the list of default post types
			self::$def_post_types = apply_filters('find-post-default-post-types', self::$def_post_types);

			// this is techinically a base component of the ICE plugin so it needs to be one of the first plugins to be loaded
			add_action('plugins_loaded', array(__CLASS__, 'a_plugins_loaded'), 1);
			// similarly, any actions available to the admin from this plugin, should be available prior to most other sub-plugins
			add_action('admin_init', array(__CLASS__, 'a_admin_init'), 1);
		}

		public static function a_plugins_loaded() {
		}

		// sets up the actions and filters for this plugin if nad only if we are in the admin
		public static function a_admin_init() {
			// the ajax requests should only be available in the admin
			add_action('wp_ajax_qs-find-posts', array(__CLASS__, 'aj_process_ajax_request'));

			// include the javascript that will allow the usage of the find posts box
			wp_enqueue_script('qs-admin-tools', QSWID_URL.'js/base/admin-tools.js', array('jquery'), self::$version);
			wp_enqueue_script('qs-find-posts-script', self::$_js_root.'admin.js', array('jquery', 'jquery-ui-dialog'), self::$version);

			wp_enqueue_style('qs-jquery-ui-theme-addon', self::$_css_root.'jquery-ui-integration.css', array(), self::$version);
			wp_enqueue_style('qs-jquery-ui-theme', self::$_css_root.'grey-ui/jquery-ui.css', array('qs-jquery-ui-theme-addon'), self::$version);
			add_filter('admin_body_class', array(__CLASS__, 'a_admin_body_class'));

			// the action that will allow other plugins to easily draw a button that will pop the find posts lightbox
			add_action('qs-find-post-button', array(__CLASS__, 'a_draw_find_post_button'));
		}

		public static function a_admin_body_class($current) {
			return $current.' grey-ui';
		}

		public static function a_draw_find_post_button($args='') {
			$defs = array(
				'echo' => true,
				'text' => 'Find Posts',
				'classes' => 'button',
				'post-types' => array(),
				'categories' => array(),
				'permalink-container' => '',
				'id-container' => '',
				'extra-attr' => '',
				'default' => false,
				'unique-id' => uniqid('find-posts-button-'),
				'lock' => array(),
			);
			$args = wp_parse_args($args, $defs);
			
			$atag = "";
			
			if (!is_array($args['post-types']) || empty($args['post-types'])) $args['post-types'] = self::$def_post_types;
			$first = array_shift($args['post-types']);
			array_unshift($args['post-types'], $first);
			$args['default'] = $args['default'] == false ? $first : $args['default'];
			$args['extra-attr'] = wp_parse_args($args['extra-attr'], array());
			$args['classes'] = is_array($args['classes']) ? $args['classes'] : explode(' ', $args['classes']);
			$args = apply_filters('qs-find-posts-button-args', $args);

			$attr = array(
				'href' => '#',
				'class' => $args['classes'],
				'perm-tar' => $args['permalink-container'],
				'id-tar' => $args['id-container'],
				'types' => $args['post-types'],
				'cats' => $args['categories'],
				'id' => $args['unique-id'],
				'def' => $args['default'],	
				'lock' => $args['lock']			
			);
			
			// [toy 13oct11] add all locked field info
			foreach ($args["lock"] as $field) {
				$attr["def_".$field] = $args["def_".$field];
			}

			$attr = array_merge_recursive($attr, $args['extra-attr']);
			$attr['class'][] = 'qs-find-post-button';
			$attr['class'][] = $args['unique-id'];

			$attributes = array();
			foreach ($attr as $k => $v)
				if (!empty($k) && !empty($v) && !is_object($v))
					if (is_array($v))
						$attributes[] = $k.'="'.esc_attr(implode($k=='class'?' ':',',$v)).'"';
					else
						$attributes[] = $k.'="'.esc_attr($v).'"';

			$attributes = apply_filters('qs-find-posts-button-attributes', $attributes, $args);

			$atag .= '<a '.implode(' ', $attributes).'>'.$args['text'].'</a>';
			if ($args['echo']) echo $atag;
			return $atag;
		}

		// processes admin requests for this sub-plugin. all the ajax actions should be accessible through a single 'action', which is 'qs-find-posts'.
		// then we also have a 'sub-action' (sa) which will determine what we expect.
		public static function aj_process_ajax_request() {
			// get the sub-action (sa) and normalize it
			$action = strtolower(trim($_POST['sa']));

			// based on the sub-action, call the appropriate function to generate the requested output
			//print "<h1>a=$action</h1>";
			switch ($action) {
				case 's':
				case 'search':
					// this doesnt print
					// print "here";
					self::_search();
				break;

				case 'f':
				case 'form':
				default:
					self::_form();
				break;
			}
		}

		// draw the base search form, and the default resultset, and sent it back to the javascript which puts it into a lightbox
		// [toy 13oct11]  redone to add lock limitations
		protected static function _form() {
			global $wpdb;

			// normalize the request
			$args = wp_parse_args($_POST, array('types' => array(), 'default' => false));
			$args['types'] = self::_normalize_post_types($args['types']);
			
/* [toy 13oct11] debugging only
			foreach($args as $k=>$v){
				if (is_array($v)) {
					foreach ($v as $k1=>$v1) {
						print "<div>--$k $k1=$v1</div>";
					}
				}
				else {
					print "<div>$k=$v</div>";
				}
			}
			print "<p></p>";
?>
*/
			$post_types = get_post_types();

			// load the categories for each named category sent in the request
			$cArgs = array("orderby"=>"name", "order"=>"asc");
			$cats = get_categories($cArgs);
			
			
			print_r($cats);
			

			// [toy 13oct11] locked default necessities
			$titleDisable = $args["def_title"][0] ? "readonly='readonly'" : "";
			$titleClass = $args["def_title"][0] ? "widefat disabled" : "widefat";
			
			?>
			<div class="find-posts-form" title="Find Posts">
				<div class="search-box-wrapper">
					<form action="" method="post">
						<input type="hidden" name="action" value="qs-find-posts"/>
						<input type="hidden" name="sa" value="search"/>
						<?php wp_nonce_field('qs-find-posts', '_ajax_nonce', false); ?>
                        
                        
						<?php /* draw the list of post types requested, making sure to select the default post type as determined above */ ?>
						<div class="findpost-group-type title-text-input">
                        	<div class="list-header title-text-header">Titles Contains:</div>
                        	<?php if (isset($args['def_title'][0])) { ?>
                             	<div class='locked-title-list'><?php echo $args["def_title"][0]; ?></div>
                                <div class="clear"></div>
                                <input id="s" type="hidden" name="s" class="<?php echo $titleClass; ?>" value="<?php echo $args["def_title"][0]; ?>" <?php echo $titleDisable; ?> autocomplete="off"/>
							<?php } else { ?>
                                <div id="post-text-toggle" class="findpost-group-toggle findpost-group-title-text-toggle"><a id="group-title-text-toggle" class="button group-toggle">^</a></div>
                                <div class="clear"></div>
                                <input id="s" type="text" name="s" class="<?php echo $titleClass; ?>" value="<?php echo $args["def_title"][0]; ?>" <?php echo $titleDisable; ?> autocomplete="off"/>
							<?php  } ?>
						</div>
                        <div class="clear"></div>

						<?php /* draw the list of post types requested, making sure to select the default post type as determined above */ ?>
						<div class="findpost-group-type post-type-list">
                        	<div class="list-header post-type-list-header">Post Types:</div>
							<div id="post-type-toggle" class="findpost-group-toggle findpost-group-type-toggle"><a id="group-post-type-toggle" class="button group-toggle">^</a></div>
                            <div class="clear"></div>
                            <ul id="post-type-ul" class="post-type-list-ul">
                            <?php foreach ($post_types as $post_type){ ?>
                            	<?php if (($post_type!= "revision") && ($post_type != "nav_menu_item") && ($post_type != "evolve")) { ?>
									<?php $pt = get_post_type_object($post_type); ?>
                                    <?php	// [toy] related to blurb.php line 266
											//$checked = in_array($post_type, $args['def_post_type']) ? "checked ": ""; ?>
                                    <li>
                                        <label>
                                            <input type="checkbox" name="post_type[]" value="<?php echo $pt->name ?>" <?php // echo $checked ?>/>
                                            <?php echo ucfirst(strtolower($pt->label)) ?>
                                        </label>
                                    </li>
								<?php } ?>
                            <?php } ?>
                            </ul>
						</div>
                        <div class="clear"></div>
                        
						<?php /* draw the list of categories requested */ ?>
						<div class="findpost-group-type category-list">
							<div class="list-header category-list-header">Categories:</div>
                        	<?php if (isset($args['def_category'])) { 
								foreach($args['def_category'] as $c) {
									foreach($cats as $catID=>$c0) {
										if ($c == $c0->slug) { 
											$catList .= ucfirst(strtolower($c0->name)).", ";
							?> 
                                            <input type="hidden" name="cats[]" value="<?php echo $c0->cat_ID; ?>" />
							<?php 
										} 
									} 
                            	} 
							?>
                             	<div class='locked-cat-list'><?php echo substr($catList,0,-2); ?></div>
                                <div class="clear"></div>

							<?php } else { ?>
                                <div id="category-toggle" class="findpost-group-toggle findpost-group-category-toggle"><a id="group-category-toggle" class="button group-toggle">></a></div>
                                <div class="clear"></div>
                                <ul id="category-ul" class="category-list-ul">
                                <?php foreach ($cats as $catID=>$cat){ ?>    
                                    <li>
                                        <label>
                                            <input type="checkbox" <?php echo checked($args['default'], $cat->slug) ?> name="cats[]" value="<?php echo $cat->cat_ID; ?>"/>
                                            <?php echo ucfirst(strtolower($cat->name)) ?>
                                        </label>
                                    </li>
                                <?php } ?>
                                </ul>
                            <?php } ?>
						</div>
                        <div class="clear spacer"></div>
                        
						<?php /* draw tag search */ ?>
						<div class="findpost-group-type tag-list">
                        	<div class="list-header tag-list-header">Tags:</div>
                        	<?php if (isset($args['def_tags'])) { 
								foreach($args['def_tags'] as $t) {
									$tagList .= strtolower($t) .", ";
                            	} 
							?>
								<input type="hidden" id="tag-input" name="t" value="<?php echo substr($tagList,0,-2); ?>" />
                             	<div class='locked-tag-list'><?php echo substr($tagList,0,-2); ?></div>
                                <div class="clear"></div>

							<?php } else { ?>
                                <div id="tag-toggle" class="findpost-group-toggle findpost-group-tag-toggle"><a id="group-tag-toggle" class="button group-toggle">></a></div>
                                <div class="clear"></div>
                                <input id="tag-input" type="text" name="t" class="widefat" autocomplete="off"/>
							<?php } ?>
						</div>
                        <div class="clear"></div>
 						<div class="spacer"></div>
 						<div class="spacer"></div>
 
                        <div class="find-posts-form-submit"><input type="submit" value="Search"/></div>
						<div class="clear"></div>
					</form>
				</div>
                <div class="clear"></div>
                
				<div class="search-results-wrapper">
				<?php /* draw the default resultset */ ?>
					<?php $rargs = array('post_type' => array($args['default'])); ?>
					<?php echo self::_draw_results_table(self::_get_search_results($rargs), $rargs) ?>
				</div>
			</div>
<?php
			die();
		}

		// initiates the search via ajax request. 
		// normalizes the request data, 
		// and then passes that to the actually function that draws the resultset
		protected static function _search() {
			// normalize the request
			$args = wp_parse_args((array)$_POST, array('s' => '', 'post_type' => array(), '_ajax_nonce' => ''));
			$args['post_type'] = self::_normalize_post_types($args['post_type']);
			// verify that the request came from the find post lightbox, and that someone is not doing something shisty
			if (!wp_verify_nonce($args['_ajax_nonce'], 'qs-find-posts')) die('<span class="error">Could not process your request</span>');
			// send the normalized request to the function that will actually get the results
			$res = self::_get_search_results($args);
			// draw the results in a table format
			echo self::_draw_results_table($res, $args);
			die();
		}

		protected static function _normalize_post_types($post_types) {
			if (!is_array($post_types) || empty($post_types)) $post_types = self::$def_post_types;
			$post_types = apply_filters('qs-findposts-normalize-types', $post_types);
			return $post_types;
		}

		// get a list of ids of the posts that match teh search criteria. this list of ids can then be passed to an appropriate output function
		protected static function _get_search_results($args) {
			// normalize the request
			$args = wp_parse_args($args, array('s' => '', 'post_type' => array()));
			$args['post_type'] = self::_normalize_post_types($args['post_type']);
			
			// workaround for weird php explode issue that i do not agree with
			$args['tags'] = trim($args["t"])=="" ? array() : explode(",",str_replace(", ",",",trim($args["t"])));

			// we do not want any posts that we know for a fact will never be displayed. here is the list of known post statuses that will never show on the frontend
			$bad_statuses = array( 'trash', 'draft', 'auto-draft', 'inherit', 'pending', 'future' );

			$ids = array();
			if (count($args['post_type']) == 1) {
				$ids = apply_filters('qs-findpost-search-results-'.array_shift(array_values($args['post_type'])), $ids, $args, $bad_statuses);
			}
			
			if (empty($ids)) {
				global $wpdb;
				// construct the sql query. start with what we know, the post types and post statuses
				$q = $wpdb->prepare('
					select distinct p.`ID` 
					from wp_posts p
				');

				// prep from
				// if cats or tags were provided - join terms tables
				if (!empty($args['cats']) || !empty($args['tags'])) {
					$q .= $wpdb->prepare('
						inner join wp_term_relationships r on r.object_id=p.ID
						inner join wp_term_taxonomy tax on r.term_taxonomy_id=tax.term_taxonomy_id
						inner join wp_terms t on tax.term_id=t.term_id
					');
				}

				// prep where
				$q .= $wpdb->prepare('
						where
							p.`post_type` in ("'.implode('","',$args['post_type']).'")
							and p.`post_status` not in ("'.implode('","',$bad_statuses).'")
						'
				);
				
				// if cats were provided, just search the title for it
				if (!empty($args['cats'])) $q .= $wpdb->prepare(' and t.`term_id` in ('.implode(',',$args['cats']).')');

				// if tags were provided, just search the title for it
				if (!empty($args['tags'])) $q .= $wpdb->prepare(' and t.`slug` in ("'.implode('","',$args['tags']).'")');

				// if search text was provided, just search the title for it
				if (!empty($args['s'])) $q .= $wpdb->prepare(' and p.`post_title` like %s', '%'.preg_replace('#[^\d\w]+#', '%', $args['s']).'%');

				// finally, we want them in backwards chronological order, and we only want the first page of 100 of them. further than that, and the user needs to be more specific
				$q .= $wpdb->prepare(' order by `post_date` desc limit 100');

				$ids = $wpdb->get_col($q);
			}

			return $ids;
		}

		protected static function _draw_results_table($ids, $args='') {
			// normalize the request
			$args = wp_parse_args($args, array('post_type' => array()));
			$args['post_type'] = self::_normalize_post_types($args['post_type']);

			// [toy 08.29.11] per request change to multichoice
			$cols = array( 'checkbox' => '&nbsp;', 'title' => 'Title', 'date' => 'Date', 'type' => 'Type' );
			//$cols = array( 'radio-button' => '&nbsp;', 'title' => 'Title', 'date' => 'Date', 'type' => 'Type' );

			$cols = apply_filters('qs-findposts-columns', $cols, $args['post_type']);

			$headers = array();
			foreach ($cols as $col => $display) {
				$classes = apply_filters('qs-findposts-column-classes', array($col.'-column'), $args['post_type']);
				$headers[] = '<th class="'.implode(' ', $classes).'">'.$display.'</th>';
			}
			$headers = implode('', $headers);

			// start constructing the table
			$result = '<table class="search-results widefat">'
				.'<thead>'.'<tr>'.$headers.'</tr>'.'</thead>'
				.'<tbody>';

			// foreach id in the list of ids
			foreach ($ids as $id) {
				// load the post
				$post = get_post($id);
				if (is_object($post) && isset($post->ID)) {
					setup_postdata($post); $postmeta=get_post_custom($post->ID);
					$source = substr($post->post_content, 0,50)."... ";
				}

				$result .= '<tr>';
				foreach ($cols as $col => $_ignore) {
					$classes = apply_filters('qs-findposts-column-value-classes', array($col.'-column-value'), $args['post_type']);
					$result .= '<td class="'.implode(' ', $classes).'">';

					switch ($col) {
						// [toy 08.28.11] added checkbox for multichoice
						case 'checkbox':
							$res = '<input type="checkbox" id="cb" name="force_single_selection[]" class="search-result" value="'.$post->ID.'" title="'.get_the_title($post->ID).'"/>'
								.'<input type="hidden" class="permalink-preview-value" value="'.esc_attr(get_permalink($post->ID)).'"/>'
								.'<input type="hidden" class="permalink-edit-link" value="'.esc_attr(get_edit_post_link($post->ID)).'"/>';
						break;
						
						case 'radio-button':
							$res = '<input type="radio" name="force_single_selection" class="search-result" value="'.$post->ID.'" title="'.get_the_title($post->ID).'"/>'
								.'<input type="hidden" class="permalink-preview-value" value="'.esc_attr(get_permalink($post->ID)).'"/>'
								.'<input type="hidden" class="permalink-edit-link" value="'.esc_attr(get_edit_post_link($post->ID)).'"/>';
						break;

						case 'title':
							// trim the title to only 8 words. most of the time that will appear on a single line in the lightbox results list
							$title = preg_split('#\s+#', strip_tags(get_the_title($post->ID)));
							$o_count = count($title);
							$title = array_slice($title, 0, 8);
							$n_count = count($title);
							$title = implode(' ', $title);
							$title = $n_count < $o_count ? $title.'...' : $title;
							$res = '<div title="'.esc_attr(strip_tags(get_the_title($post->ID))).'">'.$title.'</div>';
							//$res .= '<div title="'.esc_attr(strip_tags(get_the_title())).'" class="captionD">'.$source.'</div>';
						break;

						case 'date':
							//$res = preg_replace('#at.*$#si', '', strip_tags(get_the_date($post->ID)));
							$res = '<div title="'.esc_attr(strip_tags(get_the_title())).'" class="captionD">'. date("M d, y", strtotime($post->post_date)) ."</div>";
						break;

						case 'type':
							$res = get_post_type($post->ID);
						break;

						default:
							$res = apply_filters('qs-findposts-column-value', '', $args['post_type'], $col, $id);
						break;
					}

					$result .= $res;
					$result .= '</td>';
				}
				$result .= '</tr>';
			}

			// finish the construction of the table and table footers
			$result .= '</tbody>'
				.'<tfoot>'.'<tr>'.$headers.'</tr>'.'</tfoot>'
			.'</table>';

			return $result;
		}
	}

	// baseic secondary verifications that we are in the wp system and not being accessed directly
	if (defined('ABSPATH') && function_exists('add_action')) {
		qs_core_base_findpost::pre_init();
	}

endif;
