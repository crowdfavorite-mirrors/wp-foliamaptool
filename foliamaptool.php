<?php
/*
Plugin Name: Foliamaptool Easy Google Maps
Plugin URI: http://www.foliamaptool.com
Author URI: http://www.lenius.dk
Description: Foliamaptool makes it easy to insert Google Maps in WordPress posts and pages.
Version: 1.0.1
Author: Carsten Jonstrup
*/

/*
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the license.txt file for details.
*/

// ----------------------------------------------------------------------------------
// Class foliamaptool - plugin class
// ----------------------------------------------------------------------------------
class foliamaptool {
	var $wordpress_tag = 'foliamaptool';    // tag assigned by wordpress.org
	var $version = '1.0.1';
	var $doc_link = 'http://www.foliamaptool.com';
	var $bug_link = 'http://www.foliamaptool.com';
	var $map_defaults = array ('api_key_developer' => '');

	var $div_num = 0;    //
	var $plugin_page = '';



	function foliamaptool(){

        global $wpdb, $wp_version;

		// This plugin doesn't work for feeds!
		if (is_feed())
			return;

		load_plugin_textdomain('foliamaptool', false, dirname(plugin_basename( __FILE__ )) . '/languages');

		// Notices
		add_action('admin_notices', array(&$this, 'hook_admin_notices'));

		// Install and activate
		register_activation_hook(__FILE__, array(&$this, 'hook_activation'));

        add_action('admin_menu', array(&$this, 'hook_admin_menu'));

		// Shortcode processing
		add_shortcode('foliamaptool', array(&$this, 'foliamaptool_shortcodes'));

		// Post save hook for saving maps
		add_action('save_post', array(&$this, 'hook_save_post'));

		// Non-admin scripts & stylesheets
	   	add_action("wp_print_scripts", array(&$this, 'hook_print_scripts'));
		add_action("wp_print_styles", array(&$this, 'hook_print_styles'));
		add_action('wp_head', array(&$this, 'hook_head'));

		// Uninstall
		//register_uninstall_hook(__FILE__, array(&$this, 'hook_uninstall'));
	}

	/**
	* Add admin menu and admin scripts/stylesheets
	* Admin script - post edit and options page
	* Content script - content (and also post-edit map)
	* CSS - content, plugins, post-edit
	*
	*/
	function hook_admin_menu() {
		// Add menu
		$mypage = add_options_page('Foliamaptool', 'Foliamaptool', 'manage_options', 'foliamaptool', array(&$this, 'admin_menu'));
		$this->plugin_page = $mypage;

		// Post edit shortcode boxes - note that this MUST be admin_menu call
		add_meta_box('foliamaptool', 'Foliamaptool', array(&$this, 'meta_box'), 'post', 'normal', 'high');
		add_meta_box('foliamaptool', 'Foliamaptool', array($this, 'meta_box'), 'page', 'normal', 'high');

		// Add scripts & styles for admin pages
		add_action("admin_print_scripts-$mypage", array(&$this, 'hook_admin_print_scripts'));
		add_action("admin_print_scripts-post.php", array(&$this, 'hook_admin_print_scripts'));
		add_action("admin_print_scripts-post-new.php", array(&$this, 'hook_admin_print_scripts'));
		add_action("admin_print_scripts-page.php", array(&$this, 'hook_admin_print_scripts'));
		add_action("admin_print_scripts-page-new.php", array(&$this, 'hook_admin_print_scripts'));

		add_action("admin_print_styles-$mypage", array(&$this, 'hook_admin_print_styles'));
		add_action("admin_print_styles-post.php", array(&$this, 'hook_admin_print_styles'));
		add_action("admin_print_styles-post-new.php", array(&$this, 'hook_admin_print_styles'));
		add_action("admin_print_styles-page.php", array(&$this, 'hook_admin_print_styles'));
		add_action("admin_print_styles-page-new.php", array(&$this, 'hook_admin_print_styles'));
	}

	/**
	* Scripts for non-admin screens
	*
	*/
	function hook_print_scripts() {
		$key = $this->get_array_option('api_key_developer', 'foliamaptool_options');

		// Only load for non-admin, non-feed
		if (is_admin() || is_feed())
			return;

		// Only load if API key isn't empty'
		if (empty($key))
			return;

		// Only load scripts if at least one post has map coordinates (we don't check if map shortcode is present, though)
		if (!$this->has_maps())
			return;

		//wp_enqueue_script('googlemaps', "http://maps.google.com/maps?file=api&v=2&key=$key&hl=$lang");

		if (substr($this->debug, 0, 4) == 'http')
			$script = $this->debug;



        //wp_enqueue_script('foliamaptool', $script);

		// Stylesheet
		wp_enqueue_style('foliamaptool', plugins_url('foliamaptool.css', __FILE__));

		// Localize script texts
		wp_localize_script('foliamaptool', 'mappressl10n', array(
			'go' => __('Go', 'foliamaptool')
		));
	}

	/**
	* Stylesheets for non-admin pages
	*
	*/
	function hook_print_styles() {
		// Only load for non-admin, non-feed
		if (is_admin() || is_feed())
			return;

		// Only load stylesheets if at least one post has map coordinates (we don't check if map shortcode is present, though)
		if (!$this->has_maps())
			return;

		wp_enqueue_style('foliamaptool', plugins_url("foliamaptool.css", __FILE__));
    }


	/**
	* Scripts only for our specific admin pages
	*
	*/
	function hook_admin_print_scripts() {

		// We need maps API to validate the key on options page; key may be being updated in $_POST when we hit this event
		if (isset($_POST['api_key_developer']))
			$key = $_POST['api_key_developer'];
		else
			$key = $this->get_array_option('api_key_developer', 'foliamaptool_options');

		$lang = $this->get_array_option('language', 'foliamaptool_options');

		//if (!empty($key))
		//	wp_enqueue_script('googlemaps', "http://maps.google.com/maps?file=api&v=2&key=$key&hl=$lang");

        wp_enqueue_script('foliamaptool_admin', plugins_url('foliamaptool_admin.js', __FILE__), array('jquery-ui-core', 'jquery-ui-dialog'));


        //wp_enqueue_script('foliamaptool', plugins_url('foliamaptool.js', __FILE__), array('jquery-ui-core', 'jquery-ui-dialog'));


		wp_localize_script('foliamaptool_admin', 'mappressl10n', array(
			'edit' => __('Edit', 'foliamaptool'),
			'save' => __('Save', 'foliamaptool'),
			'cancel' => __('Cancel', 'foliamaptool'),
			'del' => __('Delete', 'foliamaptool')
		));




		//$script = plugins_url('foliamaptool.js', __FILE__);

		// Add action to load our geocoder and icons declarations that can't be enqueued
		add_action('admin_head', array(&$this, 'hook_head'));

	}

	function hook_admin_print_styles() {
	    wp_enqueue_style('foliamaptool', plugins_url('foliamaptool.css', __FILE__));
	}

	/**
	* Add js declarations since they can't be 'enqueued', needed by both admin and regular pages
	*
	*/
	function hook_head() {
		$key = $this->get_array_option('api_key_developer', 'foliamaptool_options');

		// For non-admin pages ONLY: load scripts only if at least one post has map coordinates (we don't check if map shortcode is present, though)
		if (!is_admin() && !$this->has_maps())
			return;

		// Do nothing if no API key available
		if (empty($key))
			return;

		// Load geocoder

	}

	function hook_activation() {
		// upgrade
		$current_version = $this->get_array_option('version','foliamaptool_options');

		// Save current version #
		$this->update_array_option('version', $this->version);

        //debug
        //mail("cj@folia.dk","Foliamaptool activated",$_SERVER["SERVER_NAME"]);

	}

	/**
	* Delete all option on uninstall
	*
	*/
	function hook_uninstall() {
		update_options('foliamaptool', '');
	}

	function hook_save_post($post_id) {
		// This hook gets triggered on autosaves, but WP doesn't populate all of the _POST variables (sigh)
		// So ignore it unless at least one of our fields is set.
		if (!isset($_POST['mapp_zoom']))
			return;

		delete_post_meta($post_id, '_mapp_map');
		delete_post_meta($post_id, '_mapp_pois');

		// Process map header fields.  Filter out empty strings so as not to affect shortcode_atts() calls later
		if (!empty($_POST['mapp_size']))
			$map['size'] = $_POST['mapp_size'];
		if (!empty($_POST['mapp_maptype']))
			$map['maptype'] = $_POST['mapp_maptype'];
		if (!empty($_POST['mapp_width']))
			$map['width'] = $_POST['mapp_width'];
		if (!empty($_POST['mapp_height']))
			$map['height'] = $_POST['mapp_height'];
		if (!empty($_POST['mapp_zoom']))
			$map['zoom'] = $_POST['mapp_zoom'];
		if (!empty($_POST['mapp_center_lat']))
			$map['center_lat'] = $_POST['mapp_center_lat'];
		if (!empty($_POST['mapp_center_lng']))
			$map['center_lng'] = $_POST['mapp_center_lng'];

		update_post_meta($post_id, '_mapp_map', $map);


		if (!empty($pois))
			update_post_meta($post_id, '_mapp_pois', $pois);
	}

	/**
	* Hook: admin notices
	* Used for upgrade notification
	*/
	function hook_admin_notices() {
		global $pagenow;

		// Check if API key entered; it may be in process of being updated
		if (isset($_POST['api_key_developer']))
			$key = $_POST['api_key_developer'];
		else
			$key = $this->get_array_option('api_key_developer', 'foliamaptool_options');

		if (empty($key)) {
			echo "<div id='error' class='error'><p>"
			. __("FoliaMapTool isn't ready yet.  Please enter your FoliaMapTool on the ", 'foliamaptool')
			. "<a href='options-general.php?page=foliamaptool'>"
			. __("FoliaMapTool options screen.", 'foliamaptool') . "</a></p></div>";

			return;
		}
	}

	function has_maps() {
		global $posts;

		$found = false;

		if (empty($posts))
			return false;

		foreach($posts as $key=>$post)
			if (get_post_meta($post->ID, '_mapp_pois', true))
				$found = true;

		return $found;
	}

	function all_maps() {
		global $wpdb;

	}

	function get_debug() {
		$result = $this->all_maps();
		return array($result, count($result));
	}

	/**
	* Shortcode form for post edit screen
	*
	*/
	function meta_box($post) {
		$map = get_post_meta($post->ID, '_mapp_map', true);
		$pois = get_post_meta($post->ID, '_mapp_pois', true);

		// Load the edit map
		// Note that mapTypes is hardcoded = TRUE (so user can change type, even if not displayed in blog)
		$map['maptypes'] = 1;
		$this->map($map, $pois, true);

		// The <div> will be filled in with the list of POIs
		//echo "<div id='admin_poi_div'></div>";
	}

	function output_map_sizes($selected = "", $width = 0, $height = 0) {

	}

	/**
	* Map a shortcode in a post.  Called by WordPress shortcode processor.
	*
	* @param mixed $atts - shortcode attributes
	*/
	function foliamaptool_shortcodes($atts='') {
		global $id;

		if (is_feed())
			return;

		$map = get_post_meta($id, '_mapp_map', true);
		$pois = get_post_meta($id, '_mapp_pois', true);

		$result = $this->map($map, $pois, false);

		return $result;
	}

	function map($map, $pois, $editable = false) {

        $apiKey = $this->get_array_option('api_key_developer', 'foliamaptool_options');


       // $maps = json_decode(file_get_contents("http://api.FoliamapTool.com/v3/maps/?apiKey=".$apiKey."&format=json"));


		$map_args = $this->map_defaults;
		$map_args = shortcode_atts($map_args, $this->get_array_option('map_options'));
		$map_args = shortcode_atts($map_args, $map);

        $args = array("mapname" => $map_name, "editable" => $map_args['editable']);

        $editable = false;

	     // If we couldn't encode just give up
		if (empty($args))
			return;


			?>
				<div>


					<p><b><?php _e('My maps', 'foliamaptool') ?></b></p>


                    <?php
                    echo "<pre>";
                    //print_r($maps->data);
                    echo "<select id=\"foliamaptool_maps\">";
                    echo "<option><?php _e('Select', 'foliamaptool') ?></option>";

                    echo "</select>";

                    echo "</pre>";
                    ?>


                    <script>

                    populateMaps('<?php echo $apiKey;?>');


                    </script>

                    <br/><br/>

					<p class="submit" style="padding: 0; float: none" >
						<input type="button" id="foliamaptool_insert" value="<?php _e('Insert map shortcode in post &raquo;', 'foliamaptool'); ?>" />
					</p>

                    <p><i> <?php _e('Please switch to html view before inserting code ', 'foliamaptool'); ?></i></p>

				</div>


			<?php

	}

    function script($script) {
        $cr = "\r\n";

        // Note: commented lines can be removed only when WordPress fixes TRAC #3670
        $html = "$cr <script type='text/javascript'>"
//            . "$cr /* <![CDATA[ */ $cr"
            . $script
//            . "$cr /* ]]> */ "
            . "$cr </script> $cr";
        return $html;
    }

	/**
	* Get option value.  Options are stored under a single key
	*/
	function get_array_option($option, $subarray='') {

      $options = get_option('foliamaptool');



      if (empty($options))
			return false;

		if ($subarray) {
			if (isset($options[$subarray][$option]))
				return $options[$subarray][$option];
			else
				return false;
		}

		// No subarray
		if (isset($options[$option]))
			return $options[$option];
		else
			return false;

		// If we get here it's an error
		return false;
	}

	/**
	* Set option value.  Options are stored as an array under a single key
	*/
	function update_array_option($option, $value) {
		$options = get_option('foliamaptool');
		$options[$option] = $value;
		update_option('foliamaptool', $options);
	}

	/**
	* Delete option value from option array.
	*
	*/
	function delete_array_option($option) {
		$options = get_option('foliamaptool');
		if (isset($options[$option])) {
			unset ($options[$option]);
			update_option('foliamaptool', $options);
			return true;
		}

		return false;
	}

	/**
	* Options page
	*
	*/
	function admin_menu() {
		if ( !current_user_can('manage_options') )
			die ( __( "ACCESS DENIED: You don't have permission to do this.", 'foliamaptool') );



		// Save options
		if (isset($_POST['save'])) {
			check_admin_referer('foliamaptool');




			foreach($_POST as $key=>$value)
				if (!empty($_POST[$key]) || $_POST[$key] === '0')
					$new_options[$key] = strip_tags(mysql_real_escape_string ($_POST[$key]));

			$this->update_array_option('foliamaptool_options', $new_options);



			$message = __('Settings saved', 'foliamaptool');
		}

		$map_options = shortcode_atts($this->map_defaults, $this->get_array_option('foliamaptool_options'));


       // print_r($map_options);


		$help_msg = $this->get_array_option('help_msg');
	?>
	<div class="wrap">
		<div id="icon-options-general" class="icon32"><br /></div>

			<h2><?php _e('FoliaMapTool Options', 'foliamaptool') ?></h2>
			<?php
				if (!empty($message))
					echo "<div id='message' class='updated fade'><p>$message</p></div>";
				if (!empty($error))
					echo "<div id='error' class='error'><p>$error</p></div>";
			?>
			<div>
				<a target='_blank' href='<?php echo $this->bug_link ?>'><?php _e('Report a bug', 'foliamaptool')?></a>
				| <a target='_blank' href='<?php echo $this->doc_link ?>'><?php _e('FoliaMapTool help', 'foliamaptool')?></a>
                | <a target='_blank' href='<?php echo $this->doc_link ?>'><?php _e('Get Api Key', 'foliamaptool')?></a>
			</div>

            <hr/>

			<form method="post" action="">
				<?php wp_nonce_field('foliamaptool'); ?>

				<h4><?php _e('FoliaMapTool API Key', 'foliamaptool');?></h4><p>

				<table class="form-table">
					<tr valign='top'>
						<td id='api_block'><input type='text' id='api_key' name='api_key_developer' size='50' value='<?php echo $map_options['api_key_developer']; ?>'/>
						<p id='api_message'></p>
						</td>
					</td>
					<script type='text/javascript'>
						mappCheckAPI()
					</script>
				</table>

				<p class="submit"><input type="submit" class="submit" name="save" value="<?php _e('Save Changes', 'foliamaptool') ?>"></p>
			</form>
		</div>
		<p><small>&copy; 2010, <a href="http://www.foliamaptool.com">Foliamaptool.com</a></small></p>
	</div>
	<?php
	}


	/**
	* Options - display option as a field
	*/
	function option_string($label, $name, $value='', $size='', $comment='', $class='') {
		if (!empty($class))
			$class = "class='$class'";

		echo "<tr valign='top'><th scope='row'>" . $label . "</th>";
		echo "<td $class><input type='text' id='$name' name='$name' value='$value' size='$size'/> $comment</td>";
		echo "</tr>";
	}

	/**
	* Options - display option as a radiobutton
	*/
	function option_radiobutton($label, $name, $value='', $keys, $comment='') {
		echo "<tr valign='top'><th scope='row'>" . $label . "</th>";
		echo "<td>";

		foreach ((array)$keys as $key => $description) {
			if ($key == $value)
				$checked = "checked";
			else
				$checked = "";
			echo "<input type='radio' id='$name' name='$name' value='" . htmlentities($key, ENT_QUOTES, 'UTF-8') . "' $checked />" . $description . "<br>";
		}
		echo $comment . "<br>";
		echo "</td></tr>";
	}

	/**
	* Options - display option as a checkbox
	*/
	function option_checkbox($label, $name, $value='', $comment='') {
		if ($value)
			$checked = "checked='checked'";
		else
			$checked = "";
		echo "<tr valign='top'><th scope='row'>" . $label . "</th>";
		echo "<td><input type='hidden' id='$name' name='$name' value='0' /><input type='checkbox' name='$name' value='1' $checked />";
		echo " $comment</td></tr>";
	}

	/**
	* Options - display as dropdown
	*/
	function option_dropdown($label, $name, $value, $keys, $comment='') {
		echo "<tr valign='top'><th scope='row'>" . $label . "</th>";
		echo "<td><select name='$name'>";

		foreach ((array)$keys as $key => $description) {
			if ($key == $value)
				$selected = "selected";
			else
				$selected = "";

			echo "<option value='" . htmlentities($key, ENT_QUOTES, 'UTF-8') . "' $selected>$description</option>";
		}
		echo "</select>";
		echo " $comment</td></tr>";
	}
}  // End plugin class





// Create new instance of the plugin
$foliamaptool = new foliamaptool();
?>