<?php

if ( ! defined( 'ABSPATH' ) ) exit;

require_once( plugin_dir_path( __FILE__ ) . '/log-widget.php' );
require_once( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php');

if(isset($_GET['logoff'])){
	delete_option('rd_username_field');
	delete_option('rd_password_field');
	delete_option('rd_channel_checkboxes');
	delete_option('rd_category_checkboxes');
	delete_option('rd_taxonomy_radiobuttons');
	delete_option('rd_type_radiobuttons');
	delete_option('rd_status_radiobuttons');
	delete_option('rd_image_radiobuttons');
	delete_option('rd_author_radiobuttons');
	delete_option('rd_cronI_radiobuttons');
	delete_option('rd_purgeI_radiobuttons');
	header("Location: options-general.php?page=Reuters_Direct_Settings");
	exit();
}

class Reuters_Direct_Settings {

	private static $instance = null;
	public $parent = null;
	public $base = '';
	public $settings = array();
	private $user_token;
	private $logger;

	// CONSTRUCTOR FUNCTION
	public function __construct ( $parent ) {
		$this->parent = $parent;
		$this->base = 'rd_';

		// Initialise settings
		add_action( 'admin_init', array( $this, 'initSettings' ) );

		// Register Reuters Direct
		add_action( 'admin_init' , array( $this, 'registerSettings' ) );

		// Add settings page to menu
		add_action( 'admin_menu' , array( $this, 'addMenuItem' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->parent->file ) , array( $this, 'addSettingsLink' ) );

		// Add dashboard for logs
		add_action( 'wp_dashboard_setup', array( $this, 'removeDashWidget' ) );
        add_action( 'wp_dashboard_setup', array( $this, 'addDashWidget' ) );

        // Add KLogger class
        $logger = new Katzgrau\KLogger\Logger(__DIR__.'/logs', Psr\Log\LogLevel::DEBUG, array ('dateFormat' => 'Y-m-d G:i:s'));
		$this->logger = $logger;
	}

	// MAIN INSTANCE
	public static function instance ( $parent ) {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self( $parent );
		}
		return self::$instance;
	}

	// INTIALIZE SETTINGS PAGE
	public function initSettings () {
		// Download System Info
		if(isset($_POST['Submit'])){
			if($_POST['Submit'] == 'System Info'){
				header('Content-Disposition: attachment; filename="system-info.txt"');
				header('Content-type: text/plain');
				echo $_POST['system-info'];
				die();
			}
		}

		$this->settings = $this->settingsFields();

		$style_url = plugins_url() . '/reuters-direct/assets/css/style.css';
		wp_enqueue_style('style', $style_url, array(), $this->parent->version);

		$script_url = plugins_url() . '/reuters-direct/assets/js/script.js';
		wp_enqueue_script('script', $script_url, array(), $this->parent->version);
	}

	// FUNCTION TO ADD PAGE
	public function addMenuItem () {
		$page = add_options_page( __( 'Reuters Direct', 'reuters-direct' ) , __( 'Reuters Direct', 'reuters-direct' ) , 'manage_options' , 'Reuters_Direct_Settings' ,  array( $this, 'settingsPage' ) );
	}

	// FUNCTION TO ADD PAGE LINK
	public function addSettingsLink ( $links ) {
		$settings_link = '<a href="options-general.php?page=Reuters_Direct_Settings"></a>';
  		array_push( $links, $settings_link );
  		return $links;
	}

	// FUNCTION TO BUILD SETTING FIELDS
	private function settingsFields () {

		$settings['login'] = array(
			'title'					=> __( 'Login', 'reuters-direct' ),
			'description'			=> __( 'Welcome to Reuters WordPress Direct, a full-featured news aggregator for Reuters content.<br><br>This plugin requires a Reuters Web Services-API user to authenticate and ingest content.<br>Please reach out to our <a href="https://liaison.reuters.com/contact-us/" target="_blank">Customer Support</a> for setting up new access.', 'reuters-direct' ),
			'page'				  	=> __( 'Reuters_Direct_Login' ),
			'fields'				=> array(
				array(
					'id' 			=> 'username_field',
					'label'			=> __( 'Username' , 'reuters-direct' ),
					'description'	=> __( 'This is a standard text field.', 'reuters-direct' ),
					'type'			=> 'text',
					'placeholder'	=> __( 'Enter Username', 'reuters-direct' )
				),
				array(
					'id' 			=> 'password_field',
					'label'			=> __( 'Password' , 'reuters-direct' ),
					'description'	=> __( 'This is a standard password field.', 'reuters-direct' ),
					'type'			=> 'password',
					'placeholder'	=> __( 'Enter Password', 'reuters-direct' )
				)
			)
		);

		$settings['settings'] = array(
			'title'					=> __( 'Settings', 'reuters-direct' ),
			'description'			=> __( '', 'reuters-direct' ),
			'page'				  	=> __( 'Reuters_Direct_Settings' ),
			'fields'				=> array(
				array(
					'id'			=> 'channel_checkboxes',
					'label'			=> __( 'Select Channels' , 'reuters-direct' ),
					'description'	=> __( 'This is a multiple checkbox field for channel selection.', 'reuters-direct' ),
					'type' 			=> 'channel_checkboxes',
					'default'		=> array()
				),
				array(
					'id'			=> 'category_checkboxes',
					'label'			=> __( 'Select Category Codes' , 'reuters-direct' ),
					'description'	=> __( 'This is a multiple checkbox field for category code selection.', 'reuters-direct' ),
					'type' 			=> 'category_checkboxes',
					'options'		=> array('SUBJ'=>'subj', 'N2'=>'N2', 'MCC'=>'MCC', 'MCCL'=>'MCCL', 'RIC'=>'RIC', 'A1312'=>'A1312', 'Agency_Labels'=>'Agency_Labels', 'User_Defined'=>'User_Defined'),
					'default'		=> array(''),
					'info'			=> array('IPTC subject codes (These are owned by the IPTC, see their website for various lists)
The key distinctions between N2000 and IPTC are that N2000 includes region and country codes while IPTC do not. IPTC codes can also be structured or nested.
', 'N2000 codes also known as Reuters Topic and Region codes. These are alphabetic and inclusion means some relevance to the story. You can use this code to identify stories located in a certain location and or topic. These codes are derived from the IPTC subject codes below. Use Note: Using these codes, will generate a fair amount of additional category codes as stories are coded with multiple N2 codes.', 'These are Media Category Codes or MCC codes. Often referred to as ‘desk codes’. Derived from the ANPA-1312 format. These codes are added manually by Editorial Staff at Reuters.', 'These are the same as MCC codes however, these codes are applied automatically by Open Calais after the content of the story has been analyzed.', 'Reuters Instrument Code -  Stock Symbol + Index.', 'These are legacy ANPA codes.', 'Agency Labels are pre-defined verticals introduced to help you segregate the ingested content and help map them to generic pre-defined categories such as TopNews and Entertainment.','Categorize content on a per channel basis and map those channels to new or pre-existing WordPress categories.')
				),
				array(
					'id' 			=> 'taxonomy_radiobuttons',
					'label'			=> __( 'Set Taxonomy', 'reuters-direct' ),
					'description'	=> __( 'This is a radio button field for taxonomy selection.', 'reuters-direct' ),
					'type'			=> 'taxonomy_radiobuttons',
					'options'		=> get_taxonomies('', 'names'),
					'default'		=> 'category'
				),
				array(
					'id' 			=> 'type_radiobuttons',
					'label'			=> __( 'Set Post Type', 'reuters-direct' ),
					'description'	=> __( 'This is a radio button field for post type selection.', 'reuters-direct' ),
					'type'			=> 'type_radiobuttons',
					'options'		=> get_post_types('', 'names'),
					'default'		=> 'post'
				),
				array(
					'id' 			=> 'status_radiobuttons',
					'label'			=> __( 'Set Post Status', 'reuters-direct' ),
					'description'	=> __( 'This is a radio button field for post status selection.', 'reuters-direct' ),
					'type'			=> 'status_radiobuttons',
					'options'		=> array( 'publish' => 'Publish (Online Reports)', 'draft' => 'Draft (Online Reports)', 'publish images' => 'Publish (Online Reports with images only)'),
					'default'		=> 'draft'
				),
				array(
					'id' 			=> 'image_radiobuttons',
					'label'			=> __( 'Set Image Rendition', 'reuters-direct' ),
					'description'	=> __( 'This is a radio button field for image rendition selection.', 'reuters-direct' ),
					'type'			=> 'image_radiobuttons',
					'options'		=> array( 'rend:thumbnail' => 'Small JPEG: 150 pixels (Pictures & Online Reports)', 'rend:viewImage' => 'Medium JPEG: 640 pixels (Pictures) 450 pixels (Online Reports)', 'rend:baseImage' => 'Large JPEG: 3500 pixels (Pictures) 800 pixels (Online Reports)', 'rend:filedImage' => 'Base JPEG: 3500 pixels (Online Reports only)' ),
					'default'		=> 'rend:viewImage'
				),
				array(
					'id' 			=> 'author_radiobuttons',
					'label'			=> __( 'Set Post Author', 'reuters-direct' ),
					'description'	=> __( 'This is a radio button field for post author selection.', 'reuters-direct' ),
					'type'			=> 'author_radiobuttons',
					'options'		=> array( 'Reuters' => 'Reuters', 'Default User' => 'Default User' ),
					'default'		=> 'Reuters'
				),
				array(
					'id' 			=> 'cronI_radiobuttons',
					'label'			=> __( 'Set Cron Interval', 'reuters-direct' ),
					'description'	=> __( 'This is a radio button field for cron interval selection.', 'reuters-direct' ),
					'type'			=> 'cronI_radiobuttons',
					'options'		=> wp_get_schedules(),
					'default'		=> 'every_fifteen_minutes'
				),
				array(
					'id' 			=> 'purgeI_radiobuttons',
					'label'			=> __( 'Set Purge Interval', 'reuters-direct' ),
					'description'	=> __( 'This is a radio button field for purge interval selection.', 'reuters-direct' ),
					'type'			=> 'purgeI_radiobuttons',
					'options'		=> array('30' => '30 days', '60' => '60 days', '90' => '90 days', 'none' => 'None'),
					'default'		=> 'none'
				)
			)
		);

		$settings = apply_filters( 'Reuters_Direct_Settings_fields', $settings );

		return $settings;
	}

	// FUNCTION TO REGISTER
	public function registerSettings () {
		if( is_array( $this->settings ) ) {
			foreach( $this->settings as $section => $data ) {

				add_settings_section( $section, null, array($this, 'settingsSection'), $data['page'] );

				foreach( $data['fields'] as $field ) {

					// Validation callback for field
					$validation = '';
					if( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field
					$option_name = $this->base . $field['id'];
					register_setting( $data['page'], $option_name, $validation );

					// Add field to page
					add_settings_field( $field['id'], $field['label'], array( $this, 'displayFields' ), $data['page'], $section, array( 'field' => $field ) );
				}
			}
		}
	}

	// FUNCTION TO ADD DESCRIPTION
	public function settingsSection ( $section ) {
		$html = '<p>' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo $html;
	}

	// FUNCTION TO GENERATE HTML WITH FIELDS
	public function displayFields ( $args ) {
		$field = $args['field'];
		$html = '';
		$option_name = $this->base . $field['id'];
		$option = get_option( $option_name );
		$data = '';
		if( isset( $field['default'] ) ) {
			$data = $field['default'];
			if( $option ) {
				$data = $option;
			}
		}

		switch( $field['type'] ) {

			case 'text':
				$html .= '<div class="settings" style="margin-bottom:0px;"><div id="rd_formheader">Login</div><table class="setting_option" style="padding-bottom:0px;"><tr><td class="login_field">Username</td></tr><tr><td><input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . $data . '"/></td></tr></table></div>';
				break;

			case 'password':
				$html .= '<div class="settings"><table class="setting_option" style="padding-top:0px;"><tr><td class="login_field">Password</td></tr><tr><td><input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . $data . '"/></td></tr></table></div>';
				break;

			case 'channel_checkboxes':
			    $channel_xml = '';
				$channel_url = "http://rmb.reuters.com/rmd/rest/xml/channels?&token=$this->user_token";
			  	$response = wp_remote_get($channel_url, array('timeout' => 10));

			  	if (!is_wp_error($response)){
				   $channel_xml = simplexml_load_string(wp_remote_retrieve_body($response));
				}
				else{
				   $this->logger->error($response->get_error_message());
				}
				$OLR = $TXT = $GRA = $PIC = array();
				foreach ($channel_xml->channelInformation as $channel_data)
				{
		   			$channel = (string) $channel_data->description;
		   			$alias = (string) $channel_data->alias;
		   			if(@count($channel_data->category))
		   			{
						$category = (string) $channel_data->category->attributes()->id;
						if($category == "OLR")
						{
							$OLR[$channel] = $alias .':OLR:' . $channel;
						}
						else if($category == "TXT")
						{
							$TXT[$channel] = $alias.':TXT:' . $channel;
						}
						else if($category == "PIC")
						{
							$PIC[$channel] = $alias.':PIC:' . $channel;
						}
						else if($category == "GRA")
						{
							$GRA[$channel] = $alias.':GRA:' . $channel;
						}
					}
				}
				$html .= '<div class="settings"><div id="rd_formheader">News Feed</div> <div id="channel_filter"> <span class="label" style="font-weight:bold !important;"><strong style="font-weight:bold !important; margin-left:3px;">Filter by:</strong></span> <a id="OLR" name="Online Reports" href="#" onclick="setFilter(1);" class="category selected">Online Reports</a> <span>|</span> <a id="TXT" name="Text" href="#" onclick="setFilter(2);" class="category">Text</a> <span>|</span> <a id="PIC" name="Pictures" href="#" onclick="setFilter(3);" class="category">Pictures</a> <span>|</span> <a id="GRA" name="Graphics" href="#" onclick="setFilter(4);" class="category">Graphics</a></div>';
				ksort($OLR);
				$html .= '<table id="OLRChannels" class= "channels" style="display: none;">';
				if(!$OLR){
					$html .= '<tr><td>No subscribed channels</td></tr>';
				}
				foreach ($OLR as $channel => $detail)
				{
					$channel_categories = "";
					$checked = false;
					$saved_detail = array_values(preg_grep( '/'.$detail.'*/', $data ));
					if($saved_detail){
						$checked = true;
						$channel_detail = explode(':', $saved_detail[0]);
						$channel_categories = $channel_detail[3];
					}
					$html .= '<tr class="channel_detail"><td><label for="' . esc_attr( $channel ) . '"><input class="channel_info" type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $detail ) . '" id="' . esc_attr( $channel ) . '" /> ' . $channel . '</label><td><input class="category_info" data-role="tagsinput" type="text" value="'.$channel_categories.'" placeholder="Category Name or ID"/></td></tr>';
				}
				$html .= '</table>';
				ksort($TXT);
				$html .= '<table id="TXTChannels" class= "channels" style="display: none;">';
				if(!$TXT){
					$html .= '<tr><td>No subscribed channels</td></tr>';
				}
				foreach ($TXT as $channel => $detail)
				{
					$channel_categories = "";
					$checked = false;
					$saved_detail = array_values(preg_grep( '/'.$detail.'*/', $data ));
					if($saved_detail){
						$checked = true;
						$channel_detail = explode(':', $saved_detail[0]);
						$channel_categories = $channel_detail[3];
					}
					$html .= '<tr class="channel_detail"><td><label for="' . esc_attr( $channel ) . '"><input class="channel_info" type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $detail ) . '" id="' . esc_attr( $channel ) . '" /> ' . $channel . '</label><td><input class="category_info" data-role="tagsinput" type="text" value="'.$channel_categories.'" placeholder="Category Name or ID"/></td></tr>';
				}
				$html .= '</table>';
				ksort($PIC);
				$html .= '<table id="PICChannels" class= "channels" style="display: none;">';
				if(!$PIC){
					$html .= '<tr><td>No subscribed channels</td></tr>';
				}
				$count = 1;
				foreach ($PIC as $channel => $alias)
				{
					$checked = false;
					if( in_array( $alias, $data ) )
					{
						$checked = true;
					}
					if($count%2){$html .= '<tr>';}
				   	$html .= '<td><label for="' . esc_attr( $field['id'] . '_' . $alias ) . '"><input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $alias ) . '" id="' . esc_attr( $field['id'] . '_' . $alias ) . '" /> ' . $channel . '</label></td>';
					if(!$count%2){$html .= '</tr>';}
					$count++;
				}
				$html .= '</table>';
				ksort($GRA);
				$html .= '<table id="GRAChannels" class= "channels" style="display: none;">';
				if(!$GRA){
					$html .= '<tr><td>No subscribed channels</td></tr>';
				}
				$count = 1;
				foreach ($GRA as $channel => $alias)
				{
					$checked = false;
					if( in_array( $alias, $data ) )
					{
						$checked = true;
					}
					if($count%2){$html .= '<tr>';}
				   	$html .= '<td><label for="' . esc_attr( $field['id'] . '_' . $alias ) . '"><input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $alias ) . '" id="' . esc_attr( $field['id'] . '_' . $alias ) . '" /> ' . $channel . '</label></td>';
					if(!$count%2){$html .= '</tr>';}
					$count++;
				}
				$html .= '</table></div>';
				break;

			case 'category_checkboxes':
				$html .= '<div class="settings" style="margin-bottom:0;"><div id="rd_formheader">Category</div><table class="setting_option">';
				$count = 1;
				$info = $field['info'];
				$info_count = 0;
				foreach( $field['options'] as $k => $v )
				{
					$checked = false;
					if( in_array( $v, $data ) )
					{
						$checked = true;
					}
					if($count%2){$html .= '<tr>';}
					$html .= '<td><label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $v ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" />' . $k . '</label><p id="' . $k . '" class="category_info">' . $info[$info_count] . '</p></td>';
					if(!$count%2){$html .= '</tr>';}
					$count++;
					$info_count++;
				}
				$html .= '</table></div>';
				break;

			case 'taxonomy_radiobuttons':
				$html .= '<div class="settings" style="margin-bottom:0;"><div id="rd_formheader">Taxonomy</div><table class="setting_option">';
				$count = 1;
				foreach( $field['options'] as $k) {
					$checked = false;
					if( $k == $data ) {
						$checked = true;
					}
					if($count%2){$html .= '<tr>';}
					if($k == 'category') {
						$v = 'Category (Recommended)';
					}
					else {
						$v = ucwords(str_replace("_", " ", $k));
					}
					$html .= '<td><label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label></td>';
					if(!$count%2){$html .= '</tr>';}
					$count++;
				}
				$html .= '</table>';
				$warning = '<p class="warning_info">For more info on Taxonomies, view official documentation <a href="https://wordpress.org/support/article/taxonomies/" target="_blank">here</a></p>';
				$html .= $warning;
				$html .= '</div>';
				break;

			case 'type_radiobuttons':
				$html .= '<div class="settings" style="margin-bottom:0;"><div id="rd_formheader">Post Type</div><table class="setting_option">';
				$count = 1;
				$exclusions = ['page', 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset'];
				foreach( $field['options'] as $k) {
					if(in_array($k, $exclusions)) {
						continue;
					}
					$checked = false;
					if( $k == $data ) {
						$checked = true;
					}
					if($count%2){$html .= '<tr>';}
					if($k == 'post') {
						$v = 'Post (Recommended)';
					}
					else {
						$v = ucwords(str_replace("_", " ", $k));
					}
					$html .= '<td><label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label></td>';
					if(!$count%2){$html .= '</tr>';}
					$count++;
				}
				$html .= '</table>';
				$warning = '<p class="warning_info">For more info on Post Types, view official documentation <a href="https://wordpress.org/support/article/post-types/" target="_blank">here</a></p>';
				$html .= $warning;
				$html .= '</div>';
				break;

			case 'status_radiobuttons':
				$html .= '<div class="settings" style="margin-top:20px;"><div id="rd_formheader">Post Status</div><table class="setting_option">';
				$count = 1;
				foreach( $field['options'] as $k => $v ) {
					$checked = false;
					if( $k == $data ) {
						$checked = true;
					}
					if($count%2){$html .= '<tr>';}
					$html .= '<td><label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label></td>';
					if(!$count%2){$html .= '</tr>';}
					$count++;
				}
				$html .= '</table>';
				$warning = '<p class="warning_info">Applied only for Online Reports. All other news feed types are ingested in "Draft" mode</p>';
				$html .= $warning;
				$html .= '</div>';
				break;

			case 'image_radiobuttons':
				$html .= '<div class="settings"><div id="rd_formheader">Image Rendition</div><table class="setting_option">';
				$count = 1;
				$high_res = $this->checkMemory();
				if(!$high_res) {
					// Removing high res option
					unset($field['options']['rend:filedImage']);
				}
				foreach( $field['options'] as $k => $v ) {
					$checked = false;
					if( $k == $data ) {
						$checked = true;
					}
					if($count%2){$html .= '<tr>';}
					$html .= '<td><label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label></td> ';
					if(!$count%2){$html .= '</tr>';}
					$count++;
				}
				$html .= '</table>';
				if(!$high_res){
					$warning = '<p class="warning_info">Your WordPress or PHP Post/Upload sizes are below the recomended values. Therefore Base JPEG Image rendition will not be availble.</p>';
					$html .= $warning;
				}
				$html .= '</div>';
				break;

			case 'author_radiobuttons':
				$html .= '<div class="settings"><div id="rd_formheader">Post Author</div><table class="setting_option">';
				$count = 1;
				foreach( $field['options'] as $k => $v ) {
					$checked = false;
					if( $k == $data ) {
						$checked = true;
					}
					if($count%2){$html .= '<tr>';}
					$html .= '<td><label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label></td> ';
					if(!$count%2){$html .= '</tr>';}
					$count++;
				}
				$html .= '</table></div>';
				break;

			case 'cronI_radiobuttons':
				$html .= '<div class="settings" style="margin-bottom:0;"><div id="rd_formheader">Cron Interval</div><table class="setting_option">';
				$count = 1;
				foreach( $field['options'] as $k => $v) {
					$checked = false;
					if( $k == $data ) {
						$checked = true;
					}
					if($count%2){$html .= '<tr>';}
					if($k == 'every_fifteen_minutes') {
						$v['display'] = 'Every 15 minutes (Default)';
					}
					$html .= '<td><label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v['display'] . '</label></td> ';
					if(!$count%2){$html .= '</tr>';}
					$count++;
				}
				$html .= '</table>';
				$warning = '<p class="warning_info">For optimal performance, support WP_Cron with server side cron.</p>';
				$html .= $warning;
				$html .= '</div>';
				break;

			case 'purgeI_radiobuttons':
				$html .= '<div class="settings"><div id="rd_formheader">Purge Interval</div><table class="setting_option">';
				$count = 1;
				foreach( $field['options'] as $k => $v ) {
					$checked = false;
					if( $k == $data ) {
						$checked = true;
					}
					if($count%2){$html .= '<tr>';}
					$html .= '<td><label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label></td> ';
					if(!$count%2){$html .= '</tr>';}
					$count++;
				}
				$html .= '</table>';
				$warning = '<p class="warning_info">Purges unpublished content older than the above set interval. Add tag "Archive" to skip any posts from purging.</p>';
				$html .= $warning;
				$html .= '</div>';
				break;
		}
		echo $html;
	}

	// FUNCTION TO LOAD THE SETTINGS PAGE
	public function settingsPage () {

		// Header
		$html = '<div class="wrap" id="Reuters_Direct_Settings"><div id="rd_header"><h1><span>REUTERS WORDPRESS DIRECT</span></h1>';
		$html .= '<span><a href="https://liaison.reuters.com/contact-us/" target="_blank">Contact Support</a></span>';

		// System Info
		$html .= '<form action="" method="post">';
		$html .= '<textarea readonly="readonly" name="system-info" hidden>' . $this->getSystemInfo() .'</textarea>';
		$html .= '<input name="Submit" type="submit" class="rd_button rd_button_alt" value="' . esc_attr( __( 'System Info' , 'reuters-direct' ) ) . '" />';
		$html .= '</form></div>';

		$this->user_token = $this->getToken();
		if($this->user_token)
		{
			// Settings
			$username = get_option('rd_username_field');
			$html .= '<div id="rd_subheader"><b><span>'.$username.'&nbsp;</span>|<a id="logout" href="?logoff">&nbsp;Logout</a></b></div><div id="rd_settings" class="rd_form"><form name="settings_form" method="post" action="options.php" enctype="multipart/form-data">';
			ob_start();
			settings_fields( 'Reuters_Direct_Settings' );
			do_settings_sections( 'Reuters_Direct_Settings' );
			$html .= ob_get_clean();
			$html .= '<input name="Submit" type="submit" class="rd_button" value="' . esc_attr( __( 'Save Settings' , 'reuters-direct' ) ) . '" /></form></div>';

			// Cron Job
			$this->setCronjob();

			// TRAAC Analytics
			$channels = array();
			$stored_channel = get_option('rd_channel_checkboxes');
			if(!empty($stored_channel)) {
				foreach( $stored_channel as $channel => $detail ) {
					$channel_detail = explode(':', $detail);
					$channel_name = $channel_detail[2];
					array_push($channels, $channel_name);
				}
			}
			$data_array = array(
				'name'       		=> get_option('blogname'),
			    'username'       	=> $username,
			    'version'           => get_option('Reuters_Direct_version'),
			    'urldecode(str)'	=> get_option('siteurl'),
			    'newsfeed'			=> $channels,
			    'category'			=> get_option('rd_category_checkboxes'),
			    'taxonomy'       	=> get_option('rd_taxonomy_radiobuttons'),
			    'post-type'       	=> get_option('rd_type_radiobuttons'),
			    'post-status'       => get_option('rd_status_radiobuttons'),
			    'image-rendition'   => get_option('rd_image_radiobuttons'),
			    'post-author'		=> get_option('rd_author_radiobuttons'),
			    'cron-interval'		=> get_option('rd_cronI_radiobuttons'),
			    'purge-interval'	=> get_option('rd_purgeI_radiobuttons')
			);
			$data_json = json_encode($data_array);
			$html .= "<script>TRAAC.track({'eventType': 'keyValue', 'eventName': 'Settings', 'kvp': $data_json });</script>";
		}
		else
		{
			// Login
			$html .= '<div id="rd_login" class="rd_form"><form name="login_form" method="post" action="options.php" enctype="multipart/form-data">';
			ob_start();
			settings_fields( 'Reuters_Direct_Login' );
			do_settings_sections( 'Reuters_Direct_Login' );
			$html .= ob_get_clean();
			$html .= '<input name="Submit" type="submit" class="rd_button" value="' . esc_attr( __( 'Validate & Save' , 'reuters-direct' ) ) . '" /></form></div>';
			$html .= '<script>jQuery("#setting-error-settings_updated").html("<p><strong>Login falied. Please try again with a valid username and password.</strong></p>");jQuery("#setting-error-settings_updated").css("border-color","#a00000");</script>';
		}

		// Footer
		$html .= '<div id="rd_footer"><p> © '.date("Y").' Thomson Reuters. All rights reserved.<span> | </span><a href="http://www.thomsonreuters.com/products_services/financial/privacy_statement/" target="_blank" class="privacy">Privacy Statement</a></p></div></div>';

		echo $html;
	}

	// FUNCTION TO GET TOKEN
	public function getToken(){
		$token = '';
		$username = get_option('rd_username_field');
		$password = get_option('rd_password_field');
	  	$token_url = "https://commerce.reuters.com/rmd/rest/xml/login?username=$username&password=$password";
	  	$response = wp_remote_get($token_url, array('timeout' => 10, 'sslverify'   => false));

	  	if (!is_wp_error($response)){
		   $response_xml = simplexml_load_string(wp_remote_retrieve_body($response));
		   if(!$response_xml->error)
		   		$token = $response_xml;
		}
		else{
		   $this->logger->error($response->get_error_message());
		}
	  	return $token;
	}

	// FUNCTION TO ADD DASHBOARD WIDGET
	public function addDashWidget() {
	    global $custom_dashboard_widgets;

	    foreach ( $custom_dashboard_widgets as $widget_id => $options ) {
	        wp_add_dashboard_widget(
	            $widget_id,
	            $options['title'],
	            $options['callback']
	        );
	    }
	}

	// FUNCTION TO REMOVE DASHBOARD WIDGET
	public function removeDashWidget() {
	    global $remove_defaults_widgets;

	    foreach ( $remove_defaults_widgets as $widget_id => $options ) {
	        remove_meta_box( $widget_id, $options['page'], $options['context'] );
	    }
	}

	// FUNCTION TO SET/UPDATE CRON JOB
	public function setCronJob() {
		$cronI = get_option('rd_cronI_radiobuttons');
		if(!wp_next_scheduled('rd_fetch')) {
			wp_schedule_event(time(), $cronI, 'rd_fetch');
			$this->logger->info("Cron scheduled to run " . $cronI);
		}
		else {
			$cronE = wp_get_schedule('rd_fetch');
			if($cronI != $cronE && $cronI != null) {
				wp_clear_scheduled_hook('rd_fetch');
				wp_schedule_event(time(), $cronI, 'rd_fetch');
				$this->logger->info("Cron updated to run " . $cronI);
			}
		}
	}

	// FUNCTION TO CHECK MEMORY SETTINGS
	public function checkMemory() {
		$WP_Max_file_size = wp_max_upload_size(); // read Wordpress  Max_file_size setting
        $maxFileSize = $this->convertBytes(ini_get('upload_max_filesize')); // read PHP upload_max_filesize setting
        $maxPostSize = $this->convertBytes(ini_get('post_max_size')); // read PHP post_max_size setting
        $maxMemoryLimit = $this->convertBytes(ini_get('memory_limit')); // read PHP memory limit
        $MB = 1024*1024*5; // Reuters Max Images size of 5 MB

        if (($maxFileSize < $MB) ||  ($maxPostSize < $MB) || ($WP_Max_file_size < $MB) || ($maxMemoryLimit < $MB*7)) {
        	return false;
        }
        return true;
	}

	// FUNCTION TO CONVERT BYTES
    public function convertBytes( $value ) {
	    if (is_numeric( $value )) {
	        return $value;
	    }
	    else {
	        $value_length = strlen($value);
	        $qty = substr( $value, 0, $value_length - 1 );
	        $unit = strtolower( substr( $value, $value_length - 1 ) );
	        switch($unit) {
	            case 'k':
	                $qty *= 1024;
	                break;
	            case 'm':
	                $qty *= 1048576;
	                break;
	            case 'g':
	                $qty *= 1073741824;
	                break;
	        }
	        return $qty;
	    }
    }

    // FUNCTION TO GENERATE SYSTEM INFO
    public function getSystemInfo() {
    	$info = '---------------------------' . "\n";
    	$info .= 'Cron Info' . "\n";
    	$info .= '---------------------------' . "\n";
    	$info .= 'WP_Cron: ' . (defined("DISABLE_WP_CRON")  ? ((DISABLE_WP_CRON) ? "No" : "Yes")  : "Yes") . "\n";
    	$info .= 'Alternate_WP_Cron: ' . (defined("ALTERNATE_WP_CRON") ? ((ALTERNATE_WP_CRON) ? "Yes" : "No" ) : "No") . "\n";
    	if(wp_next_scheduled('rd_fetch')) {
			$info .= 'rd_fetch cron is scheduled ' . wp_get_schedule('rd_fetch') . "\n";
		}
		if(wp_next_scheduled('rd_ping')) {
			$info .= 'rd_ping cron is scheduled ' . wp_get_schedule('rd_ping') . "\n";
		}
    	$info .= "\n";
    	$info .= '---------------------------' . "\n";
    	$info .= 'General Info' . "\n";
    	$info .= '---------------------------' . "\n";
    	$info .= 'Multisite: ' . (is_multisite() ? 'Yes' : 'No') . "\n";
    	$info .= 'Site_Url: ' . site_url() . "\n";
    	$info .= 'Home_Url: ' . home_url() . "\n";
    	$info .= 'WordPresss Version: ' . get_bloginfo( 'version' ) . "\n";
    	$info .= 'PHP Version: ' . PHP_VERSION . "\n";
    	$info .= 'MySQL Version: ' . mysqli_get_client_info() . "\n";
    	$info .= 'Web Server Info: ' . $_SERVER['SERVER_SOFTWARE'] . "\n";
    	$info .= 'WordPress Memory Limit: ' . WP_MEMORY_LIMIT . "\n";
    	$info .= 'PHP Safe Mode: ' . (ini_get( 'safe_mode' ) ? 'Yes' : 'No') . "\n";
    	$info .= 'PHP Memory Limit: ' . ini_get( 'memory_limit' ) . "\n";
    	$info .= 'PHP Upload Max Size: ' . ini_get( 'upload_max_filesize' ) . "\n";
    	$info .= 'PHP Post Max Size: ' . ini_get( 'post_max_size' ) . "\n";
    	$info .= 'PHP Uplaed Max Filesize: '. ini_get( 'upload_max_filesize' ) . "\n";
    	$info .= 'PHP Time Limit: ' . ini_get( 'max_execution_time' ) . "\n";
    	$info .= 'PHP Max Input Vars: ' . ini_get( 'max_input_vars' ) . "\n";
    	$info .= 'PHP Args Seperator: ' . ini_get( 'arg_separator.output' ) . "\n";
    	$info .= 'PHP Allow Url File Open: ' . (ini_get( 'allow_url_fopen' ) ? 'Yes' : 'No' ) . "\n";
    	$info .= 'WP_DEBUG: ' . (defined( 'WP_DEBUG' ) ? ((WP_DEBUG) ? 'Enabled' : 'Disabled') : 'Not set') . "\n";
    	$info .= "\n";
    	$info .= '---------------------------' . "\n";
    	$info .= 'Latest Logs' . "\n";
    	$info .= '---------------------------' . "\n";
    	$log = __DIR__.'/logs/log_'.date('Y-m-d').'.txt';
    	$handle = @fopen($log, "r");
    	if ($handle) {
            while (($buffer = fgets($handle, 4096)) !== false) {
            	$info .= $buffer;
            }
            if (!feof($handle)) {
                $info .= "Error: unexpected fgets() fail";
            }
            fclose($handle);
        }
    	return $info;
	}
}
?>